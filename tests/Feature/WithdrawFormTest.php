<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Withdraw;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the withdraw crypto form with an inline password field', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->assertSuccessful()
        ->assertSee('Withdraw Crypto')
        ->assertSee('Select Cryptocurrency')
        ->assertSee('Select Saved Wallet')
        ->assertSee('Amount (USD)')
        ->assertSee('Withdrawal Password')
        ->assertSee('Submit Withdrawal Request')
        ->assertSee('Min: $10.00');
});

it('redirects to the wallet page after a successful withdrawal', function () {
    Notification::fake();

    $user = User::factory()->create([
        'balance' => 50000,
        'withdrawal_address' => 'TAddr123abc',
        'withdrawal_address_type' => 'TRC 20',
        'has_made_first_deposit' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->set('amount', 100)
        ->call('submitWithdrawal')
        ->assertRedirect(route('dashboard.wallet'));

    expect(Withdrawal::where('user_id', $user->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('rejects an invalid withdrawal password when one is set', function () {
    $user = User::factory()->create([
        'balance' => 50000,
        'withdrawal_address' => 'TAddr123abc',
        'withdrawal_address_type' => 'TRC 20',
        'has_made_first_deposit' => true,
        'withdrawal_password' => Hash::make('secret123'),
    ]);

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->set('amount', 100)
        ->set('withdrawal_password', 'wrongpassword')
        ->call('submitWithdrawal')
        ->assertDispatched('change-error', message: 'Invalid withdrawal password');

    expect(Withdrawal::count())->toBe(0);
});

it('accepts a correct withdrawal password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'balance' => 50000,
        'withdrawal_address' => 'TAddr123abc',
        'withdrawal_address_type' => 'TRC 20',
        'has_made_first_deposit' => true,
        'withdrawal_password' => Hash::make('secret123'),
    ]);

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->set('amount', 100)
        ->set('withdrawal_password', 'secret123')
        ->call('submitWithdrawal')
        ->assertRedirect(route('dashboard.wallet'));

    expect(Withdrawal::where('user_id', $user->id)->exists())->toBeTrue();
});

it('withdraws to a selected saved wallet', function () {
    Notification::fake();

    $user = User::factory()->create([
        'balance' => 50000,
        'has_made_first_deposit' => true,
    ]);

    $wallet = Wallet::create([
        'user_id' => $user->id,
        'label' => 'My BTC Wallet',
        'cryptocurrency' => 'BTC',
        'address' => 'bc1qwalletaddress',
    ]);

    Livewire::actingAs($user)
        ->test(Withdraw::class)
        ->set('cryptocurrency', 'BTC')
        ->set('wallet_id', $wallet->id)
        ->set('amount', 100)
        ->call('submitWithdrawal')
        ->assertRedirect(route('dashboard.wallet'));

    $withdrawal = Withdrawal::where('user_id', $user->id)->first();

    expect($withdrawal)->not->toBeNull();
    expect($withdrawal->address)->toBe('bc1qwalletaddress');
    expect($withdrawal->payment_method)->toBe('BTC');
});
