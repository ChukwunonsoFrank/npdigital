<?php

use App\Livewire\Dashboard\Account;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    MembershipLevel::create([
        'name' => 'VIP0',
        'percentage' => 0.5,
    ]);
});

test('account page shows lien amount instead of balance when on hold', function () {
    $user = User::factory()->create([
        'membership_level' => 'VIP0',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_pole' => 35,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
        'lien_status' => 'on_hold',
        'lien_amount' => '750000',
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->assertSee('-7,500.00');
});

test('account page shows balance when not on hold', function () {
    $user = User::factory()->create([
        'membership_level' => 'VIP0',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_pole' => 35,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->assertSee('500.00');
});

test('account page shows stored daily commission in usd', function () {
    $user = User::factory()->create([
        'membership_level' => 'VIP0',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_pole' => 35,
        'daily_commission' => 375,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->assertSee('3.75');
});

test('account page shows the profile sections', function () {
    $user = User::factory()->create([
        'membership_level' => 'VIP0',
        'referral_code' => 'SH3A5635',
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->assertSee('Account Details')
        ->assertSee('Referral Code')
        ->assertSee('SH3A5635')
        ->assertSee('Daily Events')
        ->assertSee('Date Joined')
        ->assertSee('Membership Level')
        ->assertSee('Credit Score')
        ->assertSee('Logout');
});

test('account page renders inside the dashboard shell without legacy styles', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/dashboard/account')
        ->assertOk()
        ->assertSee('np-shell', false)
        ->assertSee('Account Details')
        ->assertDontSee('assets/css/style.css', false);
});

test('account page can update the full name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'name')
        ->set('editValue', 'John Doe')
        ->call('saveField')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('John Doe');
});

test('account page can update the username', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'username')
        ->set('editValue', 'new_username')
        ->call('saveField')
        ->assertHasNoErrors();

    expect($user->fresh()->username)->toBe('new_username');
});

test('account page rejects a duplicate username', function () {
    User::factory()->create(['username' => 'taken']);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'username')
        ->set('editValue', 'taken')
        ->call('saveField')
        ->assertHasErrors('editValue');

    expect($user->fresh()->username)->not->toBe('taken');
});

test('account page can update the email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'email')
        ->set('editValue', 'jane@example.com')
        ->call('saveField')
        ->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('jane@example.com');
});

test('account page can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->set('avatar', UploadedFile::fake()->image('avatar.jpg'))
        ->assertHasNoErrors();

    $path = $user->fresh()->profile_image_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('account page shows a prefilled password field with a visibility toggle when editing', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
        'unhashed_password' => 'oldpassword',
        'withdrawal_password' => Hash::make('transactionpass'),
        'unhashed_withdrawal_password' => 'transactionpass',
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'login_password')
        ->assertSet('editValue', 'oldpassword')
        ->assertSee('Toggle password visibility', false)
        ->call('startEdit', 'transaction_password')
        ->assertSet('editValue', 'transactionpass')
        ->assertSee('Toggle password visibility', false);
});

test('account page can update the login password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
        'unhashed_password' => 'oldpassword',
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'login_password')
        ->set('editValue', 'newpassword')
        ->call('saveLoginPassword')
        ->assertHasNoErrors();

    $user->refresh();

    expect(Hash::check('newpassword', $user->password))->toBeTrue()
        ->and($user->unhashed_password)->toBe('newpassword');
});

test('account page rejects a short login password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
        'unhashed_password' => 'oldpassword',
    ]);

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'login_password')
        ->set('editValue', '123')
        ->call('saveLoginPassword')
        ->assertHasErrors('editValue');

    expect(Hash::check('oldpassword', $user->fresh()->password))->toBeTrue();
});

test('account page can update the transaction password', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'transaction_password')
        ->set('editValue', 'transactionpass')
        ->call('saveTransactionPassword')
        ->assertHasNoErrors();

    $user->refresh();

    expect(Hash::check('transactionpass', $user->withdrawal_password))->toBeTrue()
        ->and($user->unhashed_withdrawal_password)->toBe('transactionpass');
});

test('account page rejects a short transaction password', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Account::class)
        ->call('startEdit', 'transaction_password')
        ->set('editValue', '123')
        ->call('saveTransactionPassword')
        ->assertHasErrors('editValue');

    expect($user->fresh()->withdrawal_password)->toBeNull();
});
