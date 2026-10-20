<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Wallet;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet as WalletModel;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the wallet page with balances and empty states', function () {
    $user = User::factory()->create([
        'balance' => 12345,
        'processing_amount' => 678,
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->assertSuccessful()
        ->assertSee('Wallet')
        ->assertSee('Manage your crypto deposits and withdrawals')
        ->assertSee('Available Balance')
        ->assertSee('On Hold Balance')
        ->assertSee('$123.45')
        ->assertSee('$6.78')
        ->assertSee('Deposit')
        ->assertSee('Withdraw')
        ->assertSee('Saved Wallets')
        ->assertSee('No saved wallets yet.')
        ->assertSee('Transaction History')
        ->assertSee('No transactions yet.');
});

it('adds a saved wallet and redirects back to the wallet page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->call('openWalletModal')
        ->assertSet('showWalletModal', true)
        ->set('wallet_label', 'My BTC Wallet')
        ->set('wallet_cryptocurrency', 'BTC')
        ->set('wallet_address', 'bc1qexamplewalletaddress')
        ->call('addWallet')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.wallet'));

    expect(WalletModel::where('user_id', $user->id)
        ->where('label', 'My BTC Wallet')
        ->where('cryptocurrency', 'BTC')
        ->where('address', 'bc1qexamplewalletaddress')
        ->exists())->toBeTrue();
});

it('validates the add wallet form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->set('wallet_label', '')
        ->set('wallet_cryptocurrency', '')
        ->set('wallet_address', '')
        ->call('addWallet')
        ->assertHasErrors([
            'wallet_label' => 'required',
            'wallet_cryptocurrency' => 'required',
            'wallet_address' => 'required',
        ]);

    expect(WalletModel::count())->toBe(0);
});

it('lists saved wallets and merged transaction history', function () {
    $user = User::factory()->create();

    WalletModel::create([
        'user_id' => $user->id,
        'label' => 'My ETH Wallet',
        'cryptocurrency' => 'ETH',
        'address' => '0xabc',
    ]);

    Deposit::create([
        'user_id' => $user->id,
        'cryptocurrency' => 'BTC',
        'amount' => 10000,
        'status' => 'pending',
    ]);

    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => 5000,
        'payment_method' => 'USDT-TRC20',
        'address' => 'Taddr',
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->assertSee('My ETH Wallet')
        ->assertSee('Deposit')
        ->assertSee('Withdrawal')
        ->assertSee('Bitcoin (BTC)')
        ->assertSee('USDT (TRC20)')
        ->assertDontSee('No transactions yet.');
});

it('preserves legacy transaction currency labels', function () {
    $user = User::factory()->create();
    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => 5000,
        'payment_method' => 'TRC 20',
        'address' => 'legacy-address',
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)->test(Wallet::class)
        ->assertSuccessful()
        ->assertSee('TRC 20');
});

it('opens the edit modal with prefilled wallet details', function () {
    $user = User::factory()->create();

    $wallet = WalletModel::create([
        'user_id' => $user->id,
        'label' => 'My Trust Wallet',
        'cryptocurrency' => 'USDT-TRC20',
        'address' => 'TQn9Y2khDD95J42FQtQTdwVVRZq5',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->assertSet('showWalletModal', false)
        ->call('openEditWallet', $wallet->id)
        ->assertSet('showWalletModal', true)
        ->assertSet('editingWalletId', $wallet->id)
        ->assertSet('wallet_label', 'My Trust Wallet')
        ->assertSet('wallet_cryptocurrency', 'USDT-TRC20')
        ->assertSet('wallet_address', 'TQn9Y2khDD95J42FQtQTdwVVRZq5')
        ->assertSee('Edit Wallet');
});

it('updates a saved wallet from the edit modal', function () {
    $user = User::factory()->create();

    $wallet = WalletModel::create([
        'user_id' => $user->id,
        'label' => 'My Trust Wallet',
        'cryptocurrency' => 'USDT-TRC20',
        'address' => 'TQn9Y2khDD95J42FQtQTdwVVRZq5',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->call('openEditWallet', $wallet->id)
        ->set('wallet_label', 'Renamed Wallet')
        ->set('wallet_cryptocurrency', 'BTC')
        ->set('wallet_address', 'bc1qupdatedwalletaddress')
        ->call('updateWallet')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.wallet'));

    expect($wallet->refresh()->label)->toBe('Renamed Wallet')
        ->and($wallet->cryptocurrency)->toBe('BTC')
        ->and($wallet->address)->toBe('bc1qupdatedwalletaddress');
});

it('validates the edit wallet form', function () {
    $user = User::factory()->create();

    $wallet = WalletModel::create([
        'user_id' => $user->id,
        'label' => 'My Trust Wallet',
        'cryptocurrency' => 'USDT-TRC20',
        'address' => 'TQn9Y2khDD95J42FQtQTdwVVRZq5',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->call('openEditWallet', $wallet->id)
        ->set('wallet_label', '')
        ->set('wallet_cryptocurrency', '')
        ->set('wallet_address', '')
        ->call('updateWallet')
        ->assertHasErrors([
            'wallet_label' => 'required',
            'wallet_cryptocurrency' => 'required',
            'wallet_address' => 'required',
        ]);

    expect($wallet->refresh()->label)->toBe('My Trust Wallet');
});

it('cannot edit another users wallet', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $wallet = WalletModel::create([
        'user_id' => $other->id,
        'label' => 'Other Wallet',
        'cryptocurrency' => 'BTC',
        'address' => 'bc1qexamplewalletaddress',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->call('openEditWallet', $wallet->id)
        ->assertSet('showWalletModal', false)
        ->assertSet('editingWalletId', null);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->set('editingWalletId', $wallet->id)
        ->set('wallet_label', 'Hacked Wallet')
        ->set('wallet_cryptocurrency', 'BTC')
        ->set('wallet_address', 'bc1qexamplewalletaddress')
        ->call('updateWallet')
        ->assertHasNoErrors();

    expect($wallet->refresh()->label)->toBe('Other Wallet');
});

it('only shows the authenticated users wallets', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    WalletModel::create([
        'user_id' => $other->id,
        'label' => 'Other Wallet',
        'cryptocurrency' => 'BTC',
        'address' => 'x',
    ]);

    Livewire::actingAs($user)
        ->test(Wallet::class)
        ->assertDontSee('Other Wallet')
        ->assertSee('No saved wallets yet.');
});
