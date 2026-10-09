<?php

namespace App\Livewire\Dashboard;

use App\Models\Deposit;
use App\Models\Wallet as WalletModel;
use App\Models\Withdrawal;
use App\Support\Cryptocurrencies;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Wallet extends Component
{
    public bool $showWalletModal = false;

    public ?int $editingWalletId = null;

    public string $wallet_label = '';

    public string $wallet_cryptocurrency = '';

    public string $wallet_address = '';

    public function mount(): void
    {
        if ($toast = session('toast')) {
            $this->dispatch('toast', message: $toast);
        }
    }

    public function openWalletModal(): void
    {
        $this->showWalletModal = true;
    }

    public function closeWalletModal(): void
    {
        $this->showWalletModal = false;
        $this->editingWalletId = null;
        $this->reset('wallet_label', 'wallet_cryptocurrency', 'wallet_address');
        $this->resetValidation();
    }

    public function openEditWallet(int $walletId): void
    {
        $wallet = $this->findWallet($walletId);

        if ($wallet === null) {
            return;
        }

        $this->editingWalletId = $wallet->id;
        $this->wallet_label = $wallet->label;
        $this->wallet_cryptocurrency = $wallet->cryptocurrency;
        $this->wallet_address = $wallet->address;
        $this->resetValidation();
        $this->showWalletModal = true;
    }

    public function addWallet(): void
    {
        $this->validateWallet();

        auth()->user()->wallets()->create([
            'label' => $this->wallet_label,
            'cryptocurrency' => $this->wallet_cryptocurrency,
            'address' => $this->wallet_address,
        ]);

        $this->closeWalletModal();

        session()->flash('toast', 'Wallet added successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
    }

    public function updateWallet(): void
    {
        $wallet = $this->findWallet((int) $this->editingWalletId);

        if ($wallet === null) {
            $this->closeWalletModal();

            return;
        }

        $this->validateWallet();

        $wallet->update([
            'label' => $this->wallet_label,
            'cryptocurrency' => $this->wallet_cryptocurrency,
            'address' => $this->wallet_address,
        ]);

        $this->closeWalletModal();

        session()->flash('toast', 'Wallet updated successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
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
            'wallet_cryptocurrency' => ['required', 'string', Rule::in(Cryptocurrencies::codes())],
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
            'cryptocurrencies' => Cryptocurrencies::all(),
        ]);
    }
}
