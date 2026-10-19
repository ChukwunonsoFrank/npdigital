<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Record;
use App\Livewire\Dashboard\Start;
use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 1]);
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => 1.5]);
});

it('lists only training users and links the training navigation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['is_training' => true, 'name' => 'Lina', 'balance' => 113400]);
    User::factory()->create(['name' => 'Real Account']);
    User::factory()->create(['is_admin' => true, 'is_training' => true, 'name' => 'Other Admin']);

    $this->actingAs($admin)->get('/admin/training')->assertOk()
        ->assertSee('Training Accounts')->assertSee('Lina')->assertSee('$1134.00')
        ->assertSee('Delete All (1)')->assertSee(route('admin.training'))
        ->assertDontSee('Real Account')->assertDontSee('Other Admin');
});

it('requires an administrator for the page and component actions', function () {
    $this->get('/admin/training')->assertRedirect(route('login'));
    $user = User::factory()->create(['is_admin' => false]);
    $this->withoutExceptionHandling();
    $this->actingAs($user);
    expect(fn () => $this->get('/admin/training'))->toThrow(HttpException::class, 'Unauthorized');
    expect(fn () => Volt::actingAs($user)->test('admin.training'))->toThrow(HttpException::class);
});

it('creates a training account that can log in and submit a normal task', function () {
    Notification::fake();
    config(['services.recaptcha.enabled' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $referrer = User::factory()->create();

    Volt::actingAs($admin)->test('admin.training')->call('createAccount')
        ->set('name', 'Lina')->set('username', 'Lina2025')->set('password', 'training-pass')
        ->set('transactionPassword', '12345678')->call('nextStep')->assertHasNoErrors()
        ->assertSee('Step 2 of 2 — Set account details.')->set('balance', '1134.27')
        ->set('membershipLevel', 'Diamond')->set('referredBy', $referrer->referral_code)
        ->set('referralCommissionEnabled', true)
        ->call('saveAccount')->assertHasNoErrors()->assertSet('showForm', false)->assertSee('$1134.27');

    $user = User::query()->where('username', 'Lina2025')->sole();
    expect($user->is_training)->toBeTrue()->and((bool) $user->is_admin)->toBeFalse()
        ->and($user->balance)->toBe(113427)->and($user->email)->toBe('lina2025@training.local')
        ->and($user->referred_by)->toBe($referrer->referral_code)
        ->and($user->training_referral_commission_enabled)->toBeTrue()
        ->and($user->referral_code)->toMatch('/^SH[A-Z0-9]{6}$/')
        ->and(Hash::check('training-pass', $user->password))->toBeTrue()
        ->and(Hash::check('12345678', $user->withdrawal_password))->toBeTrue();

    Auth::logout();
    Livewire::test(Login::class)->set('username', 'Lina2025')->set('password', 'training-pass')
        ->call('login')->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    $this->get('/dashboard')->assertOk();

    Task::create(['title' => 'Training Product', 'cost' => 20, 'task_image_path' => 'test.jpg', 'task_level' => 'Diamond']);
    Livewire::actingAs($user)->test(Start::class)->call('startTask')
        ->assertSee('Training Product')->call('submitTask');
    expect($user->fresh()->tasks_completed)->toBe(1)
        ->and($user->fresh()->balance)->toBe(113457)
        ->and($referrer->fresh()->balance)->toBe(6)
        ->and($user->tasks()->sole()->status)->toBe('approved');
});

it('edits training details without changing blank passwords or the referral code', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_training' => true, 'membership_level' => 'Normal', 'balance' => 12345, 'unhashed_withdrawal_password' => '112233']);
    $password = $user->password;

    $component = Volt::actingAs($admin)->test('admin.training')->call('editAccount', $user->id)
        ->assertSet('balance', '123.45')->assertSet('password', '')
        ->assertSet('transactionPassword', '112233')
        ->assertSee('Update training account details.')
        ->assertSee('New Password (leave empty to keep current)')
        ->assertSee('Invitation Code')->assertSee($user->referral_code)
        ->assertDontSee('Step 1 of 2')->assertDontSee('Cancel')
        ->set('transactionPassword', '')
        ->set('name', 'Updated Lina')->set('username', 'updated_lina')->set('balance', '45.67')
        ->set('membershipLevel', 'Diamond')->call('saveAccount')->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated Lina')->and($user->fresh()->balance)->toBe(4567)
        ->and($user->fresh()->password)->toBe($password)
        ->and($user->fresh()->referral_code)->toBe($user->referral_code)
        ->and($user->fresh()->membership_level)->toBe('Diamond');

    $component->call('editAccount', $user->id)->set('password', 'replacement-pass')->call('saveAccount')->assertHasNoErrors();
    expect(Hash::check('replacement-pass', $user->fresh()->password))->toBeTrue();
});

it('validates account data before creating a user', function (string $field, string $value, string $rule) {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['username' => 'taken']);
    Volt::actingAs($admin)->test('admin.training')->call('createAccount')
        ->set('name', 'Lina')->set('username', 'Lina2025')->set('password', 'training-pass')
        ->set('transactionPassword', '123456')
        ->set($field, $value)->call('saveAccount')->assertHasErrors([$field => $rule]);
    expect(User::query()->where('is_training', true)->count())->toBe(0);
})->with([
    ['name', '', 'required'],
    ['username', 'taken', 'unique'],
    ['username', 'not an email@', 'regex'],
    ['password', '123', 'min'],
    ['password', '', 'required'],
    ['transactionPassword', '', 'required'],
    ['transactionPassword', '12', 'min'],
    ['email', 'not-an-email', 'email'],
    ['balance', '-0.01', 'min'],
    ['balance', '1.001', 'decimal'],
    ['balance', 'abc', 'numeric'],
    ['membershipLevel', 'Unknown', 'exists'],
    ['referredBy', 'Unknown', 'exists'],
]);

it('rejects a duplicate generated email and self referrals', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_training' => true, 'membership_level' => 'Normal']);
    User::factory()->create(['email' => 'lina2025@training.local']);
    $component = Volt::actingAs($admin)->test('admin.training')->call('editAccount', $user->id)
        ->set('name', 'Lina')->set('username', 'Lina2025')->call('saveAccount')->assertHasErrors(['username']);
    $component->set('username', $user->username)->set('referredBy', $user->referral_code)
        ->call('saveAccount')->assertHasErrors(['referredBy']);
    expect($user->fresh()->referred_by)->toBeNull();
});

it('refuses to edit or delete normal users and administrators', function (bool $isAdmin, bool $isTraining) {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => $isAdmin, 'is_training' => $isTraining]);
    expect(fn () => Volt::actingAs($admin)->test('admin.training')->call('editAccount', $user->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => Volt::actingAs($admin)->test('admin.training')->call('deleteAccount', $user->id))->toThrow(ModelNotFoundException::class);
    expect($user->fresh())->not->toBeNull();
})->with([[false, false], [true, true]]);

it('deletes training accounts and dependent records without touching other users', function (bool $deleteAll) {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/training.jpg', 'image');
    $admin = User::factory()->create(['is_admin' => true, 'is_training' => true]);
    $realUser = User::factory()->create();
    $training = User::factory()->create(['is_training' => true, 'profile_image_path' => 'avatars/training.jpg']);
    $otherTraining = User::factory()->create(['is_training' => true]);
    foreach ([$training, $realUser] as $owner) {
        CompletedTask::create(['user_id' => $owner->id, 'title' => 'Product', 'cost' => 20, 'task_image_path' => 'test.jpg', 'rating' => '', 'rating_id' => '', 'status' => 'approved']);
        DB::table('referrals')->insert(['user_id' => $owner->id, 'referral_code' => $owner->referral_code, 'referred_by' => 'REFERRER', 'amount' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }
    $component = Volt::actingAs($admin)->test('admin.training');
    if ($deleteAll) {
        $component->call('deleteAll')->assertSee('No training accounts yet');
    } else {
        $component->call('deleteAccount', $training->id);
    }

    expect($training->fresh())->toBeNull()->and($realUser->fresh())->not->toBeNull()
        ->and($admin->fresh())->not->toBeNull()
        ->and($otherTraining->fresh() === null)->toBe($deleteAll)
        ->and(CompletedTask::query()->where('user_id', $training->id)->count())->toBe(0)
        ->and($realUser->tasks()->count())->toBe(1)
        ->and(DB::table('referrals')->where('user_id', $training->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('avatars/training.jpg');
})->with([false, true]);

it('cancels the form without saving an account', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.training')->call('createAccount')->set('username', 'unsaved')
        ->call('closeForm')->assertSet('showForm', false)->assertSet('username', '');
    expect(User::query()->where('is_training', true)->exists())->toBeFalse();
});

it('validates the credentials step and preserves both steps when going back', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.training')->call('createAccount')
        ->assertSee('Step 1 of 2 — Enter login credentials.')->assertDontSee('Email (optional)')
        ->call('nextStep')->assertHasErrors(['name', 'username', 'password', 'transactionPassword'])
        ->assertSee('Step 1 of 2 — Enter login credentials.')
        ->set('name', 'Step User')->set('username', 'step_user')->set('password', 'training-pass')
        ->set('transactionPassword', '123456')->call('nextStep')->assertHasNoErrors()
        ->assertSee('Email (optional)')->assertDontSee('Full Name')
        ->set('email', 'step@example.com')->set('balance', '14.23')
        ->call('previousStep')->assertSee('Step 1 of 2 — Enter login credentials.')
        ->assertSet('name', 'Step User')->assertSet('password', 'training-pass')
        ->call('nextStep')->assertSet('email', 'step@example.com')->assertSet('balance', '14.23');

    expect(User::query()->where('is_training', true)->exists())->toBeFalse();
    $component->call('saveAccount')->assertHasNoErrors();
    $user = User::query()->where('username', 'step_user')->sole();
    expect($user->email)->toBe('step@example.com')->and($user->balance)->toBe(1423)
        ->and($user->training_referral_commission_enabled)->toBeFalse();
});

it('requires a valid referrer when commission is enabled and rejects duplicate optional emails', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $referrer = User::factory()->create(['email' => 'taken@example.com']);
    $component = Volt::actingAs($admin)->test('admin.training')->call('createAccount')
        ->set('name', 'Lina')->set('username', 'Lina2025')->set('password', 'training-pass')
        ->set('transactionPassword', '123456')->call('nextStep')
        ->set('referralCommissionEnabled', true)->call('saveAccount')->assertHasErrors(['referredBy' => 'required'])
        ->set('referredBy', $referrer->referral_code)->set('email', 'taken@example.com')
        ->call('saveAccount')->assertHasErrors(['email' => 'unique']);
    expect(User::query()->where('is_training', true)->exists())->toBeFalse();
    $component->set('email', 'new@example.com')->call('saveAccount')->assertHasNoErrors();
    $user = User::query()->where('username', 'Lina2025')->sole();
    $component->call('editAccount', $user->id)->assertSet('referralCommissionEnabled', true)
        ->assertSet('email', 'new@example.com')->set('referralCommissionEnabled', false)
        ->call('saveAccount')->assertHasNoErrors();
    expect($user->fresh()->training_referral_commission_enabled)->toBeFalse();
});

it('credits only opted-in training referrals from earned profit once', function (bool $isTraining, bool $enabled, string $cost, int $profit, int $referralProfit) {
    $referrer = User::factory()->create(['balance' => 700, 'daily_commission' => 12, 'total_commission' => 24]);
    $user = User::factory()->create([
        'is_training' => $isTraining, 'training_referral_commission_enabled' => $enabled,
        'referred_by' => $referrer->referral_code, 'membership_level' => 'Diamond', 'balance' => 10000,
    ]);
    $task = CompletedTask::create([
        'user_id' => $user->id, 'title' => 'Referral Product', 'cost' => $cost,
        'commission_percentage' => 6.9, 'task_image_path' => 'test.jpg',
        'rating' => '', 'rating_id' => '', 'status' => 'pending',
    ]);
    Livewire::actingAs($user)->test(Record::class)->call('openSubmission', $task->id)
        ->call('submitTask')->call('openSubmission', $task->id)->call('submitTask');

    expect($user->fresh()->balance)->toBe(10000 + $profit)
        ->and($referrer->fresh()->balance)->toBe(700 + $referralProfit)
        ->and($referrer->fresh()->daily_commission)->toBe(12 + $referralProfit)
        ->and($referrer->fresh()->total_commission)->toBe(24 + $referralProfit)
        ->and(DB::table('referrals')->where('user_id', $referrer->id)->sum('amount'))->toBe($referralProfit)
        ->and(DB::table('referrals')->count())->toBe($referralProfit > 0 ? 1 : 0);
})->with([
    'enabled training' => [true, true, '19.87', 137, 27],
    'disabled training' => [true, false, '19.87', 137, 0],
    'normal user' => [false, true, '19.87', 137, 0],
    'rounds below one cent' => [true, true, '0.20', 1, 0],
]);
