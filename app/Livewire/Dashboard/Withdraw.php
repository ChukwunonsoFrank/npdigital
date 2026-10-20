<?php

namespace App\Livewire\Dashboard;

use App\CryptoAssets;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\TransactionOccured;
use App\Notifications\WithdrawalInitiated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Withdraw extends Component
{
    public string $cryptocurrency = '';

    public $wallet_id = '';

    public $amount = '';

    public string $withdrawal_password = '';

    public function submitWithdrawal(): void
    {
        $user = auth()->user();

        if (! $user->has_made_first_deposit) {
            $this->dispatch('change-error', message: 'You need to make your first deposit of $100 before making withdrawals');

            return;
        }

        if ($this->amount <= 0) {
            $this->dispatch('change-error', message: 'Invalid withdrawal amount');

            return;
        }

        $withdrawalPassword = $user->getRawOriginal('withdrawal_password');

        if ($withdrawalPassword && ! Hash::check($this->withdrawal_password, $withdrawalPassword)) {
            $this->dispatch('change-error', message: 'Invalid withdrawal password');

            return;
        }

        [$address, $method] = $this->resolveDestination($user);

        if (! $address) {
            $this->dispatch('change-error', message: 'You need to bind a wallet address to your account to process withdrawals');

            return;
        }

        $amountInCents = (int) round($this->amount * 100);

        if ($user->balance < $amountInCents) {
            $this->dispatch('change-error', message: 'Insufficient balance');

            return;
        }

        $hasPending = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            $this->dispatch('change-error', message: 'You have 1 or more processing transactions. Please wait until processing is complete');

            return;
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $amountInCents,
            'payment_method' => $method,
            'address' => $address,
            'status' => 'pending',
        ]);

        $user->increment('processing_amount', $amountInCents);

        $user->notify(
            new WithdrawalInitiated(
                $user->username,
                strval($amountInCents / 100),
            ),
        );

        Notification::route('mail', config('mail.support_address'))->notify(
            new TransactionOccured(
                'withdrawal',
                $user->username,
                strval($amountInCents / 100),
            ),
        );

        $this->dispatch('change-success', message: 'Withdrawal initiated successfully');

        $this->reset('amount', 'withdrawal_password');

        session()->flash('toast', 'Withdrawal request submitted successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveDestination(User $user): array
    {
        if ($this->wallet_id) {
            $wallet = $user->wallets()->find($this->wallet_id);

            if ($wallet) {
                return [$wallet->address, $wallet->cryptocurrency];
            }
        }

        $address = $user->getRawOriginal('withdrawal_address');

        if ($address) {
            return [$address, $user->getRawOriginal('withdrawal_address_type') ?? $this->cryptocurrency];
        }

        if ($this->cryptocurrency) {
            return [null, $this->cryptocurrency];
        }

        return [null, null];
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard.withdraw', [
            'cryptocurrencies' => CryptoAssets::all(),
            'wallets' => $user->wallets()->latest()->get(),
            'availableBalance' => ((int) $user->balance) / 100,
        ]);
    }
}
