<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Deposit;
use App\Models\Deposit as DepositModel;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the deposit crypto form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Deposit::class)
        ->assertSuccessful()
        ->assertSee('Deposit Crypto')
        ->assertSee('Select Cryptocurrency')
        ->assertSee('Amount (USD equivalent)')
        ->assertSee('Proof of Payment')
        ->assertSee('Submit Deposit Request');
});

it('shows only the selected platform address and its network disclaimer', function () {
    $user = User::factory()->create();
    $user->wallets()->create(['label' => 'Personal wallet', 'cryptocurrency' => 'SOL', 'address' => 'personal-solana-address']);
    PlatformSetting::factory()->create(['deposit_wallet_addresses' => [
        'USDT-TRC20' => 'platform-trc20-address',
        'USDT-ERC20' => 'platform-erc20-address',
        'SOL' => 'platform-solana-address',
    ]]);

    $component = Livewire::actingAs($user)->test(Deposit::class)
        ->assertDontSee('Copy deposit address')
        ->set('cryptocurrency', 'USDT-TRC20')
        ->assertSee('platform-trc20-address')->assertDontSee('platform-erc20-address')
        ->assertSeeHtml('Send only <strong>USDT (TRC20)</strong> to this address.')
        ->assertSee('Copy deposit address')
        ->set('cryptocurrency', 'USDT-ERC20')
        ->assertSee('platform-erc20-address')->assertDontSee('platform-trc20-address')
        ->assertSeeHtml('Send only <strong>USDT (ERC20)</strong> to this address.')
        ->set('cryptocurrency', 'SOL')
        ->assertSee('platform-solana-address')->assertDontSee('platform-erc20-address')
        ->assertDontSee('personal-solana-address')
        ->assertSeeHtml('Send only <strong>Solana (SOL)</strong> to this address.');

    PlatformSetting::current()->update(['deposit_wallet_addresses' => ['SOL' => 'updated-solana-address']]);
    $component->call('$refresh')->assertSee('updated-solana-address')->assertDontSee('platform-solana-address')
        ->set('cryptocurrency', 'BTC')->assertSee('Deposit address not configured.')
        ->assertDontSee('updated-solana-address')->assertDontSee('Copy deposit address')->assertDontSee('Send only')
        ->set('cryptocurrency', '')->assertDontSee('Deposit address not configured.')->assertDontSee('Copy deposit address');
});

it('creates a pending deposit and redirects to the wallet page', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Deposit::class)
        ->set('cryptocurrency', 'BTC')
        ->set('amount', 100)
        ->set('screenshot', UploadedFile::fake()->image('proof.png'))
        ->call('submitDeposit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.wallet'));

    $deposit = DepositModel::where('user_id', $user->id)->first();

    expect($deposit)->not->toBeNull();
    expect($deposit->cryptocurrency)->toBe('BTC');
    expect($deposit->amount)->toBe(10000);
    expect($deposit->status)->toBe('pending');
    expect($deposit->proof_of_payment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($deposit->proof_of_payment_path);
});

it('validates the deposit form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Deposit::class)
        ->set('cryptocurrency', '')
        ->set('amount', '')
        ->call('submitDeposit')
        ->assertHasErrors([
            'cryptocurrency' => 'required',
            'amount' => 'required',
            'screenshot' => 'required',
        ]);

    expect(DepositModel::count())->toBe(0);
});

it('rejects an unsupported cryptocurrency', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Deposit::class)
        ->set('cryptocurrency', 'FAKE')
        ->set('amount', 100)
        ->call('submitDeposit')
        ->assertHasErrors(['cryptocurrency' => 'in']);
});
