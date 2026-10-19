<?php

use App\Livewire\Dashboard\Activities;
use App\Models\MembershipLevel;
use App\Models\UpgradeRequest;
use App\Models\User;
use App\Notifications\UpgradeRequested;
use App\Notifications\UpgradeReviewed;
use Database\Seeders\MembershipLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MembershipLevelSeeder::class);
    Notification::fake();
    config(['mail.support_address' => 'admin@example.test']);
});

it('submits from activities at the exact configured balance and notifies the admin once', function () {
    MembershipLevel::where('name', 'Silver')->update(['minimum_balance' => '412.37']);
    $user = User::factory()->create(['membership_level' => 'Normal', 'balance' => 41237]);

    Livewire::actingAs($user)->test(Activities::class)->assertSee('Request upgrade')
        ->call('requestUpgrade', 'Silver')->assertHasNoErrors()->assertSee('Upgrade pending')
        ->call('requestUpgrade', 'Gold')->assertHasErrors('upgrade')
        ->call('requestUpgrade', 'Silver')->assertHasErrors('upgrade');

    $request = UpgradeRequest::query()->sole();
    expect($request->user_id)->toBe($user->id)->and($request->from_level)->toBe('Normal')
        ->and($request->status)->toBe('pending')->and($request->membershipLevel->name)->toBe('Silver')
        ->and($user->fresh()->balance)->toBe(41237)->and($user->fresh()->membership_level)->toBe('Normal');
    Notification::assertSentOnDemand(UpgradeRequested::class, function ($notification, $channels, $notifiable) use ($request) {
        return $notifiable->routes['mail'] === 'admin@example.test'
            && $notification->requestId === $request->id && $notification->afterCommit === true
            && $notification->toMail($notifiable)->actionUrl === route('admin.upgrades');
    });
    Notification::assertCount(1);
});

it('rejects forged ineligible requests without creating a request or sending email', function (int $balance, string $current, string $target) {
    $user = User::factory()->create(['membership_level' => $current, 'balance' => $balance]);
    Livewire::actingAs($user)->test(Activities::class)->call('requestUpgrade', $target)->assertHasErrors('upgrade');
    expect(UpgradeRequest::count())->toBe(0);
    Notification::assertNothingSent();
})->with([
    'one cent short' => [39899, 'Normal', 'Silver'],
    'same level' => [1000000, 'Silver', 'Silver'],
    'lower level' => [1000000, 'Gold', 'Silver'],
    'unknown level' => [1000000, 'Normal', 'Not a level'],
]);

it('allows only one pending request even for different eligible levels', function () {
    $user = User::factory()->create(['membership_level' => 'Normal', 'balance' => 99900]);
    Livewire::actingAs($user)->test(Activities::class)
        ->call('requestUpgrade', 'Silver')->assertHasNoErrors()
        ->call('requestUpgrade', 'Gold')->assertHasErrors('upgrade');
    expect(UpgradeRequest::count())->toBe(1);
    Notification::assertCount(1);
});

it('approves or declines once and emails the outcome without changing the balance', function (bool $approve) {
    $user = User::factory()->create(['membership_level' => 'Normal', 'balance' => 52345, 'required_upgrade_level' => 'Silver', 'email' => 'user@example.test']);
    $admin = User::factory()->create(['is_admin' => true]);
    $request = UpgradeRequest::factory()->create(['user_id' => $user->id]);

    Volt::actingAs($admin)->test('admin.upgrades')
        ->call('review', $request->id, $approve)->assertHasNoErrors()
        ->call('review', $request->id, ! $approve)->assertHasErrors('upgrade');

    expect($request->fresh()->status)->toBe($approve ? 'approved' : 'rejected')
        ->and($request->fresh()->reviewed_by)->toBe($admin->id)->and($request->fresh()->reviewed_at)->not->toBeNull()
        ->and($user->fresh()->membership_level)->toBe($approve ? 'Silver' : 'Normal')
        ->and($user->fresh()->required_upgrade_level)->toBe($approve ? null : 'Silver')
        ->and($user->fresh()->balance)->toBe(52345);
    Notification::assertSentTo($user, UpgradeReviewed::class, function ($notification) use ($approve, $user) {
        $mail = $notification->toMail($user);

        return $notification->status === ($approve ? 'approved' : 'rejected')
            && $mail->subject === 'Membership Upgrade '.($approve ? 'Approved' : 'Rejected')
            && str_contains(implode(' ', $mail->introLines), 'Silver');
    });
    Notification::assertCount(1);
})->with([true, false]);

it('rechecks the balance and current level on approval but still permits declining', function (array $changes) {
    $user = User::factory()->create(['membership_level' => 'Normal', 'balance' => 99900]);
    $request = UpgradeRequest::factory()->create(['user_id' => $user->id]);
    $user->update($changes);
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.upgrades')->call('review', $request->id, true)->assertHasErrors('upgrade');
    expect($request->fresh()->status)->toBe('pending');
    Notification::assertNothingSent();
    Volt::actingAs($admin)->test('admin.upgrades')->call('review', $request->id, false)->assertHasNoErrors();
    expect($request->fresh()->status)->toBe('rejected');
})->with([
    'balance fell' => [['balance' => 39899]],
    'already upgraded higher' => [['membership_level' => 'Gold']],
]);

it('keeps a higher required level and allows another request after rejection', function () {
    $user = User::factory()->create(['membership_level' => 'Normal', 'balance' => 99900, 'required_upgrade_level' => 'Diamond']);
    $request = UpgradeRequest::factory()->create(['user_id' => $user->id]);
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.upgrades')->call('review', $request->id, false);
    Livewire::actingAs($user)->test(Activities::class)->call('requestUpgrade', 'Silver')->assertHasNoErrors();
    $newRequest = UpgradeRequest::query()->latest('id')->first();
    Volt::actingAs($admin)->test('admin.upgrades')->call('review', $newRequest->id, true)->assertHasNoErrors();
    expect($user->fresh()->membership_level)->toBe('Silver')->and($user->fresh()->required_upgrade_level)->toBe('Diamond');
});

it('lists and filters requests and points the sidebar to the new page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $pending = User::factory()->create(['name' => 'Pending Person', 'username' => 'pending_person']);
    $approved = User::factory()->create(['name' => 'Approved Person']);
    UpgradeRequest::factory()->create(['user_id' => $pending->id]);
    UpgradeRequest::factory()->create(['user_id' => $approved->id, 'status' => 'approved']);
    $this->actingAs($admin)->get('/admin/upgrades')->assertOk()->assertSee('Upgrade Requests')
        ->assertSee(route('admin.upgrades'))->assertSee('Pending Person')->assertDontSee('Approved Person');
    Volt::actingAs($admin)->test('admin.upgrades')->call('selectStatus', 'all')
        ->assertSee('Pending Person')->assertSee('Approved Person')
        ->set('search', 'pending_person')->assertSee('Pending Person')->assertDontSee('Approved Person')
        ->set('search', (string) $approved->id)->assertSee('Approved Person')
        ->set('search', '')->call('selectStatus', 'approved')->assertSee('Approved Person')->assertDontSee('Pending Person')
        ->call('selectStatus', 'rejected')->assertSee('No upgrade requests found');
});

it('denies guests and non-admins access to upgrade reviews', function () {
    $this->get('/admin/upgrades')->assertRedirect(route('login'));
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get('/admin/upgrades')->assertForbidden();
    Volt::actingAs($user)->test('admin.upgrades')->assertForbidden();
});
