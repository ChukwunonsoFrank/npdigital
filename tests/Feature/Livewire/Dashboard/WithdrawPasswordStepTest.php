<?php

use App\Livewire\Dashboard\Withdraw;
use App\Livewire\Dashboard\WithdrawPasswordStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the withdraw form with the withdrawal password field', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->assertSuccessful()
        ->assertSet('withdrawal_password', '')
        ->assertSee('Withdrawal Password');
});

it('allows access to withdraw without a prior verification step', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->assertSuccessful();
});

it('redirects to withdraw after correct password', function () {
    $user = User::factory()->create([
        'withdrawal_password' => Hash::make('secret123'),
    ]);

    Livewire::actingAs($user)
        ->test(WithdrawPasswordStep::class)
        ->set('withdrawal_password', 'secret123')
        ->call('verifyWithdrawalPassword')
        ->assertRedirect(route('dashboard.withdraw'));
});

it('dispatches error when withdrawal password is incorrect', function () {
    $user = User::factory()->create([
        'withdrawal_password' => Hash::make('secret123'),
    ]);

    Livewire::actingAs($user)
        ->test(WithdrawPasswordStep::class)
        ->set('withdrawal_password', 'wrongpassword')
        ->call('verifyWithdrawalPassword')
        ->assertDispatched('change-error', message: 'Invalid withdrawal password.');
});

it('validates withdrawal password is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WithdrawPasswordStep::class)
        ->set('withdrawal_password', '')
        ->call('verifyWithdrawalPassword')
        ->assertHasErrors(['withdrawal_password' => 'required']);
});
