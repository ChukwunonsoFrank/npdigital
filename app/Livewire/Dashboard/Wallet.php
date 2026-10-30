<?php

namespace App\Livewire\Dashboard;

use App\Models\Deposit;
use App\Models\Wallet as WalletModel;
use App\Models\Withdrawal;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Wallet extends Component
{
    private const CRYPTOCURRENCIES = [
        'BTC' => 'Bitcoin (BTC)',
        'ETH' => 'Ethereum (ETH)',
        'USDT-TRC20' => 'USDT (TRC20)',
        'USDT-ERC20' => 'USDT (ERC20)',
        'BNB' => 'BNB (BSC)',
        'SOL' => 'Solana (SOL)',
        'XRP' => 'XRP',
        'DOGE' => 'Dogecoin (DOGE)',
        'LTC' => 'Litecoin (LTC)',
        'TRX' => 'Tron (TRX)',
    ];

    public string $wallet_label = '';

    public string $wallet_cryptocurrency = '';

    public string $wallet_address = '';

    public function mount(): void
    {
        if ($toast = session('toast')) {
            $this->dispatch('toast', message: $toast);
        }
    }

    public function addWallet(string $label, string $cryptocurrency, string $address): void
    {
        $this->fillWalletForm($label, $cryptocurrency, $address);
        $this->validateWallet();

        auth()->user()->wallets()->create([
            'label' => $this->wallet_label,
            'cryptocurrency' => $this->wallet_cryptocurrency,
            'address' => $this->wallet_address,
        ]);

        $this->resetWalletForm();
        $this->dispatch('close-wallet-modal');

        session()->flash('toast', 'Wallet added successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
    }

    public function updateWallet(int $walletId, string $label, string $cryptocurrency, string $address): void
    {
        $wallet = $this->findWallet($walletId);

        if ($wallet === null) {
            $this->resetWalletForm();
            $this->dispatch('close-wallet-modal');

            return;
        }

        $this->fillWalletForm($label, $cryptocurrency, $address);
        $this->validateWallet();

        $wallet->update([
            'label' => $this->wallet_label,
            'cryptocurrency' => $this->wallet_cryptocurrency,
            'address' => $this->wallet_address,
        ]);

        $this->resetWalletForm();
        $this->dispatch('close-wallet-modal');

        session()->flash('toast', 'Wallet updated successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
    }

    private function fillWalletForm(string $label, string $cryptocurrency, string $address): void
    {
        $this->wallet_label = $label;
        $this->wallet_cryptocurrency = $cryptocurrency;
        $this->wallet_address = $address;
    }

    private function resetWalletForm(): void
    {
        $this->reset('wallet_label', 'wallet_cryptocurrency', 'wallet_address');
        $this->resetValidation();
    }

    private function findWallet(int $walletId): ?WalletModel
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        return $user->wallets()->whereKey($walletId)->first();
    }

    private function validateWallet(): void
    {
        $this->validate([
            'wallet_label' => ['required', 'string', 'max:255'],
            'wallet_cryptocurrency' => ['required', 'string', Rule::in(array_keys(self::CRYPTOCURRENCIES))],
            'wallet_address' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * @return Collection<int, array{type: string, cryptocurrency: string|null, amount: int, status: string, date: \Illuminate\Support\Carbon|null}>
     */
    private function transactions(): Collection
    {
        $user = auth()->user();

        $deposits = $user->deposits()->latest()->get()->map(fn (Deposit $deposit) => [
            'type' => 'Deposit',
            'cryptocurrency' => $deposit->cryptocurrency,
            'amount' => $deposit->amount,
            'status' => $deposit->status,
            'date' => $deposit->created_at,
        ]);

        $withdrawals = $user->withdrawals()->latest()->get()->map(fn (Withdrawal $withdrawal) => [
            'type' => 'Withdrawal',
            'cryptocurrency' => $withdrawal->payment_method,
            'amount' => $withdrawal->amount,
            'status' => $withdrawal->status,
            'date' => $withdrawal->created_at,
        ]);

        return $deposits->concat($withdrawals)->sortByDesc('date')->take(10)->values();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard.wallet', [
            'availableBalance' => ((int) $user->balance) / 100,
            'onHoldBalance' => ((int) $user->processing_amount) / 100,
            'wallets' => $user->wallets()->latest()->get(),
            'transactions' => $this->transactions(),
            'cryptocurrencies' => self::CRYPTOCURRENCIES,
        ]);
    }
}
