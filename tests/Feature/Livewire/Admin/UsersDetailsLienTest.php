<?php

use App\Livewire\Admin\UsersDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows toggle as off when lien_status is off_hold', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'lien_status' => 'off_hold',
        'lien_amount' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->assertSee('Off Hold');
});

it('shows toggle as on when lien_status is on_hold and lien_amount is set', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'lien_status' => 'on_hold',
        'lien_amount' => '-5000',
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->assertSee('On Hold');
});

it('releases lien when toggle is switched off', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'lien_status' => 'on_hold',
        'lien_amount' => '-5000',
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->call('releaseLien')
        ->assertSee('Off Hold');

    $user->refresh();
    expect($user->lien_status)->toBe('off_hold');
    expect($user->lien_amount)->toBeNull();
});

it('shows toggle as off when lien_status is on_hold but lien_amount is null', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'lien_status' => 'on_hold',
        'lien_amount' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->assertSee('Off Hold');
});

it('loads training balance for user', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'training_balance' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('trainingBalanceAmount', 125.50)
        ->call('addTrainingBalance');

    expect($user->fresh()->training_balance)->toBe(12550);
});

it('marks first deposit as made when admin credits at ten dollar balance', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'balance' => 1000,
        'has_made_first_deposit' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('bonusAmount', 100)
        ->call('addBonus');

    $user->refresh();
    expect($user->balance)->toBe(11000);
    expect($user->has_made_first_deposit)->toBeTrue();
});

it('allows requesting a deposit before the first task batch', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'task_batch' => 0,
        'has_made_first_deposit' => false,
        'balance' => 1000,
        'lien_status' => 'off_hold',
        'lien_amount' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('depositAmount', 200)
        ->call('requestDeposit')
        ->assertDispatched('notify', message: 'Deposit request sent successfully', type: 'success');

    $user->refresh();
    expect($user->lien_status)->toBe('on_hold');
    expect($user->lien_amount)->toBe('21000');
});

it('allows requesting deposit after first task batch', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'task_batch' => 1,
        'has_made_first_deposit' => false,
        'balance' => 1000,
    ]);

    Livewire::actingAs($admin)
        ->test(UsersDetails::class, ['id' => $user->id])
        ->set('depositAmount', 200)
        ->call('requestDeposit')
        ->assertDispatched('notify', message: 'Deposit request sent successfully', type: 'success');

    $user->refresh();
    expect($user->lien_status)->toBe('on_hold');
    expect($user->lien_amount)->toBe('21000');
});
