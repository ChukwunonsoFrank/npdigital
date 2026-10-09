<?php

use App\Livewire\Dashboard\Alert;
use App\Models\Alert as AlertModel;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated user can see their notifications', function () {
    $user = User::factory()->create();

    $alerts = AlertModel::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->assertStatus(200)
        ->assertSee($alerts[0]->title)
        ->assertSee($alerts[1]->title)
        ->assertSee($alerts[2]->title);
});

test('user does not see notifications belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    AlertModel::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Other User Alert',
    ]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->assertStatus(200)
        ->assertDontSee('Other User Alert')
        ->assertSee('No notifications yet.');
});

test('notifications are displayed latest first', function () {
    $user = User::factory()->create();

    AlertModel::factory()->create([
        'user_id' => $user->id,
        'title' => 'Oldest Alert',
        'created_at' => now()->subDays(2),
    ]);

    AlertModel::factory()->create([
        'user_id' => $user->id,
        'title' => 'Newest Alert',
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->assertStatus(200)
        ->assertSeeInOrder(['Newest Alert', 'Oldest Alert']);
});

test('unread notifications are counted and highlighted', function () {
    $user = User::factory()->create();

    AlertModel::factory()->count(2)->create([
        'user_id' => $user->id,
        'read_status' => 'unread',
    ]);

    AlertModel::factory()->create([
        'user_id' => $user->id,
        'read_status' => 'read',
    ]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->assertSee('2 unread')
        ->assertSee('border-primary/30', false);
});

test('clicking a notification marks it as read', function () {
    $user = User::factory()->create();
    $alert = AlertModel::factory()->create([
        'user_id' => $user->id,
        'read_status' => 'unread',
    ]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->assertSee('1 unread')
        ->call('markAsRead', $alert->id)
        ->assertSee('0 unread');

    expect($alert->fresh()->read_status)->toBe('read');
});

test('a user cannot mark another users notification as read', function () {
    $user = User::factory()->create();
    $otherAlert = AlertModel::factory()->create(['read_status' => 'unread']);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->call('markAsRead', $otherAlert->id);

    expect($otherAlert->fresh()->read_status)->toBe('unread');
});

test('a user can delete their own notification', function () {
    $user = User::factory()->create();
    $alert = AlertModel::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->call('delete', $alert->id);

    expect(AlertModel::find($alert->id))->toBeNull();
});

test('a user cannot delete another users notification', function () {
    $user = User::factory()->create();
    $otherAlert = AlertModel::factory()->create();

    Livewire::actingAs($user)
        ->test(Alert::class)
        ->call('delete', $otherAlert->id);

    expect(AlertModel::find($otherAlert->id))->not->toBeNull();
});

test('authenticated users can view the notifications page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.notifications'))
        ->assertOk()
        ->assertSee('Notifications')
        ->assertSee('Stay updated with announcements and alerts');
});
