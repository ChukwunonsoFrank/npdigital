<?php

namespace App\Livewire\Admin;

use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Notifications\TransactionReviewed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Payments extends Component
{
    public string $search = '';

    public string $status = 'new';

    public string $historyType = 'deposits';

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function review(string $type, int $id, bool $approve): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(in_array($type, ['Deposit', 'Withdrawal'], true), 404);
        $this->resetValidation();

        $model = $type === 'Deposit' ? new Deposit : new Withdrawal;

        $model->getConnection()->transaction(function () use ($model, $type, $id, $approve): void {
            $transaction = $model->newQuery()->lockForUpdate()->findOrFail($id);
            $user = $transaction->user()->lockForUpdate()->firstOrFail();
            abort_if($user->is_admin, 403);

            if ($transaction->status !== 'pending') {
                throw ValidationException::withMessages(['transaction' => 'This transaction has already been reviewed.']);
            }

            $amount = (int) $transaction->amount;

            if ($type === 'Deposit' && $approve) {
                $user->balance += $amount;
                $user->applyNegativeBalancePayment($amount);

                if ($amount >= 100_00) {
                    $user->has_made_first_deposit = true;
                }
            }

            if ($type === 'Withdrawal') {
                if ($approve && $user->balance < $amount) {
                    throw ValidationException::withMessages(['transaction' => 'Insufficient balance to approve this withdrawal.']);
                }

                if ($approve) {
                    $user->balance -= $amount;
                }

                $user->processing_amount = max(0, $user->processing_amount - $amount);
            }

            $user->save();
            $transaction->update([
                'status' => $approve ? ($type === 'Deposit' ? 'confirmed' : 'completed') : 'declined',
            ]);

            $user->notify(new TransactionReviewed(
                $type,
                $transaction->id,
                $amount,
                $type === 'Deposit' ? $transaction->cryptocurrency : $transaction->payment_method,
                $approve,
            ));
        }, 3);

        $this->dispatch('notify', message: $type.($approve ? ' approved.' : ' declined.'), type: 'success');
    }

    public function selectStatus(string $status): void
    {
        if (! in_array($status, ['new', 'completed'], true)) {
            return;
        }

        $this->status = $status;
    }

    public function selectHistoryType(string $historyType): void
    {
        if (! in_array($historyType, ['deposits', 'withdrawals'], true)) {
            return;
        }

        $this->historyType = $historyType;
    }

    public function render(): View
    {
        $pendingDeposits = $this->depositQuery('pending')->count();
        $pendingWithdrawals = $this->withdrawalQuery('pending')->count();
        $completedDeposits = $this->depositQuery('completed')->count();
        $completedWithdrawals = $this->withdrawalQuery('completed')->count();

        return view('livewire.admin.payments', [
            'newCount' => $pendingDeposits + $pendingWithdrawals,
            'completedCount' => $completedDeposits + $completedWithdrawals,
            'transactions' => $this->transactions(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Deposit>
     */
    private function depositQuery(string $status): \Illuminate\Database\Eloquent\Builder
    {
        return Deposit::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('is_admin', false))
            ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'completed', fn ($query) => $query->where('status', '!=', 'pending'))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('id', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('username', 'like', $search));
                });
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Withdrawal>
     */
    private function withdrawalQuery(string $status): \Illuminate\Database\Eloquent\Builder
    {
        return Withdrawal::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('is_admin', false))
            ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'completed', fn ($query) => $query->where('status', '!=', 'pending'))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('id', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('username', 'like', $search));
                });
            });
    }

    /**
     * @return Collection<int, array{id: int, type: string, user: string, method: string, amount: int, status: string, created_at: \Illuminate\Support\Carbon}>
     */
    private function transactions(): Collection
    {
        $status = $this->status === 'new' ? 'pending' : 'completed';

        if ($this->status === 'completed' && $this->historyType === 'withdrawals') {
            return $this->withdrawalQuery($status)
                ->latest()
                ->get()
                ->map(fn (Withdrawal $withdrawal): array => [
                    'id' => $withdrawal->id,
                    'type' => 'Withdrawal',
                    'user' => $withdrawal->user->name ?: $withdrawal->user->username,
                    'method' => $withdrawal->payment_method,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at,
                ]);
        }

        $deposits = $this->depositQuery($status)
            ->latest()
            ->get()
            ->map(fn (Deposit $deposit): array => [
                'id' => $deposit->id,
                'type' => 'Deposit',
                'user' => $deposit->user->name ?: $deposit->user->username,
                'method' => $deposit->cryptocurrency,
                'amount' => $deposit->amount,
                'status' => $deposit->status,
                'created_at' => $deposit->created_at,
            ]);

        if ($this->status === 'completed') {
            return $deposits;
        }

        return $deposits
            ->concat(
                $this->withdrawalQuery($status)
                    ->latest()
                    ->get()
                    ->map(fn (Withdrawal $withdrawal): array => [
                        'id' => $withdrawal->id,
                        'type' => 'Withdrawal',
                        'user' => $withdrawal->user->name ?: $withdrawal->user->username,
                        'method' => $withdrawal->payment_method,
                        'amount' => $withdrawal->amount,
                        'status' => $withdrawal->status,
                        'created_at' => $withdrawal->created_at,
                    ]),
            )
            ->sortByDesc('created_at')
            ->values();
    }
}
