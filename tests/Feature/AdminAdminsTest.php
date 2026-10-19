<?php

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('lists real admin details and links the navigation', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Super Admin', 'email' => 'admin@example.com', 'balance' => 12345, 'membership_level' => 'Normal']);
    User::factory()->create(['is_admin' => true, 'name' => 'Second Admin']);
    User::factory()->create(['name' => 'Regular Account']);

    $this->actingAs($admin)->get('/admin/admins')->assertOk()
        ->assertSee('Manage Admins')->assertSee('Super Administrator — Cannot be deleted')
        ->assertSee('Second Admin')->assertSee('admin@example.com')->assertSee('$123.45')
        ->assertSee($admin->referral_code)->assertSee(route('admin.admins'))
        ->assertDontSee('Regular Account');
});

it('requires admin access on the page and every component request', function () {
    $this->get('/admin/admins')->assertRedirect(route('login'));
    $user = User::factory()->create(['is_admin' => false]);
    $this->withoutExceptionHandling();
    $this->actingAs($user);
    expect(fn () => $this->get('/admin/admins'))->toThrow(HttpException::class);
    expect(fn () => Volt::actingAs($user)->test('admin.admins'))->toThrow(HttpException::class);

    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.admins');
    $admin->update(['is_admin' => false]);
    expect(fn () => $component->call('promote', $user->id))->toThrow(HttpException::class);
    expect((bool) $user->fresh()->is_admin)->toBeFalse();
});

it('updates admin details while preserving blank passwords and unrelated account data', function () {
    $actor = User::factory()->create(['is_admin' => true]);
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Other Admin', 'email' => 'other@example.com', 'balance' => 98765]);
    $hash = $admin->password;
    $component = Volt::actingAs($actor)->test('admin.admins')->call('editAdmin', $admin->id)
        ->assertSee('Edit Admin')->assertSet('password', '')
        ->set('name', 'Updated Admin')->set('username', 'updated_admin')->set('email', 'updated@example.com')
        ->call('saveAdmin')->assertHasNoErrors()->assertSet('editingId', null)->assertSee('Updated Admin');

    expect($admin->fresh()->name)->toBe('Updated Admin')->and($admin->fresh()->username)->toBe('updated_admin')
        ->and($admin->fresh()->email)->toBe('updated@example.com')->and($admin->fresh()->password)->toBe($hash)
        ->and($admin->fresh()->balance)->toBe(98765)->and($admin->fresh()->referral_code)->toBe($admin->referral_code)
        ->and((bool) $admin->fresh()->is_admin)->toBeTrue();

    $component->call('editAdmin', $admin->id)->set('password', 'replacement-pass')->call('saveAdmin')->assertHasNoErrors();
    expect(Hash::check('replacement-pass', $admin->fresh()->password))->toBeTrue()
        ->and($admin->fresh()->remember_token)->not->toBe($admin->remember_token);
});

it('validates credentials before updating an admin', function (string $field, string $value, string $rule) {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Original', 'email' => 'original@example.com']);
    User::factory()->create(['username' => 'taken', 'email' => 'taken@example.com']);
    Volt::actingAs($admin)->test('admin.admins')->call('editAdmin', $admin->id)
        ->set($field, $value)->call('saveAdmin')->assertHasErrors([$field => $rule]);
    expect($admin->fresh()->name)->toBe('Original')->and($admin->fresh()->email)->toBe('original@example.com');
})->with([
    ['name', '', 'required'],
    ['username', 'taken', 'unique'],
    ['username', 'invalid user', 'regex'],
    ['email', 'bad-email', 'email'],
    ['email', 'taken@example.com', 'unique'],
    ['password', 'short', 'min'],
]);

it('searches by name username and email and promotes users without changing their account data', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Daniella Dom', 'username' => 'dani_user', 'email' => 'dani@example.com', 'balance' => 12345]);
    User::factory()->create(['name' => 'Unmatched Person']);
    $component = Volt::actingAs($admin)->test('admin.admins')->call('openPromote')->assertSee('Daniella Dom')->assertSee('Unmatched Person');
    foreach (['Daniella', 'dani_user', 'dani@example.com'] as $search) {
        $component->set('search', $search)->assertSee('Daniella Dom')->assertDontSee('Unmatched Person');
    }
    $component->set('search', 'no-match-at-all')->assertSee('No users found.')
        ->set('search', 'Daniella')->call('promote', $user->id)->assertSee('Daniella Dom')->assertSee('No users found.');
    expect((bool) $user->fresh()->is_admin)->toBeTrue()->and($user->fresh()->balance)->toBe(12345)
        ->and($user->fresh()->password)->toBe($user->password)->and($user->fresh()->referral_code)->toBe($user->referral_code);
    $this->actingAs($user->fresh())->get('/admin/admins')->assertOk();
});

it('paginates candidates and resets the page when searching', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(21)->create();
    $last = User::query()->latest('id')->first();
    Volt::actingAs($admin)->test('admin.admins')->call('openPromote')->assertDontSee($last->username)
        ->call('gotoPage', 2)->assertSee($last->username)
        ->set('search', $last->username)->assertSee($last->username)->assertDontSee('No users found.');
});

it('unpromotes an admin without deleting their account and removes admin access', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Super Admin']);
    $user = User::factory()->create(['name' => 'Promoted User', 'balance' => 45987]);
    $component = Volt::actingAs($admin)->test('admin.admins')->call('promote', $user->id)
        ->assertSee('Unpromote Promoted User')->assertDontSee('Unpromote Super Admin');

    $component->call('unpromote', $user->id)->assertDontSee('Promoted User')
        ->call('openPromote')->assertSee('Promoted User');

    expect((bool) $user->fresh()->is_admin)->toBeFalse()
        ->and($user->fresh()->balance)->toBe(45987)
        ->and($user->fresh()->password)->toBe($user->password)
        ->and($user->fresh()->referral_code)->toBe($user->referral_code);

    $this->withoutExceptionHandling();
    $this->actingAs($user->fresh());
    expect(fn () => $this->get('/admin/admins'))->toThrow(HttpException::class);
    expect(fn () => Volt::actingAs($user->fresh())->test('admin.admins'))->toThrow(HttpException::class);
});

it('protects the super admin from direct unpromote requests', function () {
    $superAdmin = User::factory()->create(['is_admin' => true]);
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.admins')->call('unpromote', $superAdmin->id)->assertForbidden();
    expect((bool) $superAdmin->fresh()->is_admin)->toBeTrue();
});

it('redirects an admin who unpromotes themselves to their user dashboard', function () {
    User::factory()->create(['is_admin' => true]);
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.admins')->call('unpromote', $admin->id)
        ->assertRedirect(route('dashboard'));
    expect((bool) $admin->fresh()->is_admin)->toBeFalse();
});

it('does not allow regular accounts to be edited as admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $component = Volt::actingAs($admin)->test('admin.admins');
    expect(fn () => $component->call('editAdmin', $user->id))->toThrow(ModelNotFoundException::class);
});

it('discards edits and clears password and search state on cancellation', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Original']);
    Volt::actingAs($admin)->test('admin.admins')->call('editAdmin', $admin->id)
        ->set('name', 'Unsaved')->set('password', 'unsaved-password')->call('closeModal')
        ->assertSet('editingId', null)->assertSet('password', '')
        ->call('openPromote')->set('search', 'unsaved')->call('closeModal')
        ->assertSet('showPromote', false)->assertSet('search', '');
    expect($admin->fresh()->name)->toBe('Original');
});
