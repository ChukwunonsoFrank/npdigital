<?php

use App\Livewire\Dashboard\GoldenEgg;
use App\Models\GoldenEgg as GoldenEggModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows a pending golden egg on the dashboard and credits it once when cracked', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false, 'balance' => 1000]);
    $egg = GoldenEggModel::create([
        'user_id' => $user->id, 'amount' => 1500, 'status' => 'pending',
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Golden Egg')
        ->assertSee('Tap to crack');

    $component = Livewire::actingAs($user)->test(GoldenEgg::class)
        ->assertSee('A gift is waiting for you')
        ->call('claim')
        ->assertSet('showCelebration', true)
        ->assertSet('claimedAmount', 1500)
        ->assertSee('Golden Egg cracked!')
        ->assertSee('+$15.00');

    expect($egg->fresh()->status)->toBe('claimed')
        ->and($egg->fresh()->claimed_at)->not->toBeNull()
        ->and((int) $user->fresh()->balance)->toBe(2500);

    $component->call('dismiss')->assertSet('showCelebration', false);

    Livewire::actingAs($user->fresh())->test(GoldenEgg::class)
        ->assertDontSee('Tap to crack')
        ->call('claim')
        ->assertDispatched('toast', message: 'There is no golden egg waiting right now.');

    expect((int) $user->fresh()->balance)->toBe(2500);
});

it('shows no golden egg widget when the user has none pending', function () {
    $user = User::factory()->create(['is_admin' => false]);
    GoldenEggModel::create(['user_id' => $user->id, 'amount' => 500, 'status' => 'claimed']);

    Livewire::actingAs($user)->test(GoldenEgg::class)
        ->assertDontSee('Tap to crack')
        ->call('claim')
        ->assertDispatched('toast', message: 'There is no golden egg waiting right now.');
});

it('blocks claiming a golden egg while an admin is previewing the account', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'balance' => 0]);
    GoldenEggModel::create(['user_id' => $user->id, 'amount' => 500, 'status' => 'pending']);

    $this->actingAs($user);
    session()->put('view_as_admin_id', $admin->id);

    Livewire::test(GoldenEgg::class)->call('claim')->assertForbidden();
});
