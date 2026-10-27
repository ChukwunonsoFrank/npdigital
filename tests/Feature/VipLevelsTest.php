<?php

use App\Livewire\Dashboard\Start;
use App\Livewire\Dashboard\VipLevels;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders membership levels in detail with the current level highlighted', function () {
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 0.5, 'minimum_balance' => '0']);
    MembershipLevel::create(['name' => 'Silver', 'percentage' => 0.75, 'minimum_balance' => '399']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => 1, 'minimum_balance' => '999']);
    $user = User::factory()->create([
        'is_admin' => false, 'is_banned' => false, 'membership_level' => 'Silver', 'balance' => 50000,
    ]);

    $this->actingAs($user)->get(route('dashboard.vip-levels'))
        ->assertOk()
        ->assertSee('VIP Levels')
        ->assertSee('Your current level')
        ->assertSee('30')
        ->assertSee('Normal')
        ->assertSee('Silver')
        ->assertSee('Gold')
        ->assertSee('1% commission')
        ->assertSee('0.75% commission')
        ->assertSee('Current');

    Livewire::actingAs($user)->test(VipLevels::class)
        ->assertSee('3 daily sets of 30 products')
        ->assertSee('$999')
        ->assertSee('Request upgrade');
});

it('links the start page tiles to the bonus center and vip levels pages', function () {
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 0.5]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000,
    ]);

    Livewire::actingAs($user)->test(Start::class)
        ->assertSee('Bonus Center')
        ->assertSee('Daily sign-in rewards')
        ->assertSee('VIP Levels')
        ->assertSee('View all levels')
        ->assertSee(route('dashboard.bonus-center'))
        ->assertSee(route('dashboard.vip-levels'));
});

it('shows daily set progress rather than product count in the start page heading', function () {
    MembershipLevel::create(['name' => 'Normal', 'percentage' => 0.5]);
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'balance' => 10000,
        'task_batch' => 1, 'tasks_completed' => 30, 'has_made_first_deposit' => true,
    ]);

    Livewire::actingAs($user)->test(Start::class)
        ->assertSee('1/3 sets completed today')
        ->assertDontSee('1/30 sets')
        ->assertSee('Set 2')
        ->assertSee('30 / 30 products');
});
