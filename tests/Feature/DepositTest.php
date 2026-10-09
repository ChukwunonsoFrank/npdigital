<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Deposit;
use App\Models\Deposit as DepositModel;
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
