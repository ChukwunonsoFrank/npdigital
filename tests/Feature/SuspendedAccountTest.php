<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out a suspended user and blocks access to their account', function () {
    $user = User::factory()->create(['account_status' => 'suspended', 'is_admin' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Your account has been suspended. Contact support at '.config('mail.support_address').'.');

    $this->assertGuest();
});

it('blocks account actions from a suspended user', function () {
    $user = User::factory()->create(['account_status' => 'suspended', 'is_admin' => false]);

    $this->actingAs($user)
        ->post('/livewire/update')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('does not block an active user', function () {
    $this->actingAs(User::factory()->create(['account_status' => 'active', 'is_admin' => false, 'is_banned' => false]))
        ->get(route('dashboard'))
        ->assertOk();
});
