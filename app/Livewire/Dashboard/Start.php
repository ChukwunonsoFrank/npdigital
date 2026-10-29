<?php

namespace App\Livewire\Dashboard;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\Task;
use App\Models\User;
use App\Support\CompletesTasks;
use App\Support\HasTaskModal;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Start extends Component
{
    use CompletesTasks;
    use HasTaskModal;

    /**
     * The wallet that funds tasks: training accounts spend their loaded training
     * balance, everyone else spends the balance shown on the dashboard.
     */
    private function availableTaskBalanceInCents(User $user): int
    {
        if ($user->is_training && (int) $user->training_balance > 0) {
            return (int) $user->training_balance;
        }

        return (int) $user->balance;
    }

    public function mount(): void
    {
        if (session('task_completed')) {
            $this->dispatch('task-completed', message: session('task_completed'))->self();
        }
    }

    public function startTask(): void
    {
        $user = auth()->user();

        if ($this->blockedByRequiredUpgrade($user)) {
            return;
        }

        if (! $user->is_training && ! $user->has_made_first_deposit) {
            $this->dispatch(
                'deposit-required',
                message: 'You need to make your first deposit of $100 before you can start tasks.',
            )->self();

            return;
        }

        $activeTaskBalanceInCents = $this->availableTaskBalanceInCents($user);
        $productsPerSet = $user->productsPerSet();

        if ($user->task_batch >= User::DAILY_TASK_SETS) {
            $this->dispatch('task-limit-reached', message: 'Task limit reached. Come back tommorrow for more tasks.')->self();

            return;
        }

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        if (
            $membershipLevel
            && $membershipLevel->minimum_balance !== null
        ) {
            $minimumBalanceValue = str_replace(',', '', (string) $membershipLevel->minimum_balance);
            $minimumBalanceInCents = str_contains($minimumBalanceValue, '.')
                ? (int) round(((float) $minimumBalanceValue) * 100)
                : (int) $minimumBalanceValue;

            if ($activeTaskBalanceInCents < $minimumBalanceInCents) {
                $this->dispatch(
                    'minimum-balance-required',
                    message: 'The minimum balance for this level is '.number_format($minimumBalanceInCents / 100, 2).' USDT',
                )->self();

                return;
            }
        }

        if ($user->tasks_completed >= $productsPerSet && $user->lien_status === 'on_hold' && $user->lien_amount !== null) {
            $this->dispatch('task-limit-reached', message: 'You need to make an extra deposit to proceed with completion of tasks.')->self();

            return;
        }

        if ($user->tasks_completed >= $productsPerSet) {
            $this->dispatch('task-limit-reached', message: 'Task limit reached. Come back tommorrow for more tasks.')->self();

            return;
        }

        $hasPendingTask = CompletedTask::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingTask) {
            $this->redirect(route('dashboard.record'));

            return;
        }

        $queuedTask = CompletedTask::query()
            ->where('user_id', $user->id)
            ->where('status', 'queued')
            ->where(function ($query) use ($user): void {
                $query->whereNull('available_after_tasks')
                    ->orWhere('available_after_tasks', '<=', $user->tasks_completed);
            })
            ->orderBy('id')
            ->first();

        $group = $user->warehouseGroup();
        $warehouseProduct = null;

        if (! $queuedTask && $group) {
            $warehouseProduct = $user->remainingWarehouseProducts($group['day'], $group['group'])[0] ?? null;
            $task = $warehouseProduct ? new Task([
                'title' => $warehouseProduct['name'],
                'cost' => $warehouseProduct['price'],
                'task_image_path' => $warehouseProduct['image'] ?? '',
                'brand' => $warehouseProduct['brand'],
            ]) : null;
        } else {
            $task = $queuedTask ?? Task::query()
                ->where('task_level', $user->membership_level)
                ->inRandomOrder()
                ->first();
        }

        if (! $task) {
            $this->dispatch('task-limit-reached', message: $group
                ? 'No products remaining in your assigned warehouse group. Contact support for a new group.'
                : 'No tasks available for your membership level at this time.')->self();

            return;
        }

        $taskCostInCents = (int) round((float) $task->cost * 100);
        $isMerge = $task instanceof CompletedTask && $task->is_merge;

        if ($isMerge) {
            $user->update([
                'negative_balance' => $task->merge_negative_balance !== null
                    ? (int) $task->merge_negative_balance
                    : max(0, $taskCostInCents - $activeTaskBalanceInCents),
            ]);
        } elseif ($activeTaskBalanceInCents < $taskCostInCents) {
            $shortfallInCents = $taskCostInCents - $activeTaskBalanceInCents;

            $this->dispatch(
                'insufficient-balance',
                message: 'Insufficient balance to complete task. Top up at least $'.number_format($shortfallInCents / 100, 2).' to continue.',
            )->self();

            return;
        }

        if ($queuedTask) {
            $activated = CompletedTask::query()
                ->whereKey($queuedTask->id)
                ->where('status', 'queued')
                ->update(['status' => 'pending']);

            if (! $activated) {
                $this->redirect(route('dashboard.record'));

                return;
            }

            $completedTask = $queuedTask;
        } else {
            $completedTask = CompletedTask::create([
                'user_id' => $user->id,
                'title' => $task->title,
                'cost' => $task->cost,
                'task_image_path' => $task->task_image_path,
                'brand' => $task->brand,
                'warehouse_product_id' => $warehouseProduct['id'] ?? null,
                'rating' => '0',
                'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
                'status' => 'pending',
            ]);
        }

        session(['pending_task_id' => $completedTask->id]);

        $this->openTaskModal();
    }

    public function render()
    {
        $user = auth()->user();

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        $pendingTask = $this->pendingTask();
        $commissionPercentage = (float) ($pendingTask?->commission_percentage ?? $membershipLevel?->percentage ?? 0);

        if ($pendingTask?->commission_amount !== null && (float) $pendingTask->cost > 0) {
            $commissionPercentage = round(((int) $pendingTask->commission_amount / 100) / (float) $pendingTask->cost * 100, 2);
        }

        $commission = $pendingTask ? $pendingTask->profit((float) ($membershipLevel?->percentage ?? 0)) : 0;

        return view('livewire.dashboard.start', [
            'user' => $user,
            'products' => $this->products(),
            'pendingTask' => $pendingTask,
            'commissionPercentage' => $commissionPercentage,
            'commission' => $commission,
            'topUpAmount' => $pendingTask?->is_merge ? (int) $user->negative_balance : 0,
            'setsCompleted' => min((int) $user->task_batch, User::DAILY_TASK_SETS),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function products(): array
    {
        return [
            [
                'title' => 'Premium Wireless Headphones',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200&h=200&fit=crop',
                'promo' => 'BOGO50',
                'brand' => 'amazon',
                'rating' => '4.8',
                'reviews' => '12.5k',
                'likes' => '8.9k',
                'sold' => '45.2k',
                'price' => '$39.99',
            ],
            [
                'title' => 'Organic Skincare Night Cream',
                'image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=200&h=200&fit=crop',
                'promo' => 'NEW',
                'brand' => 'etsy',
                'rating' => '4.6',
                'reviews' => '8.7k',
                'likes' => '6.5k',
                'sold' => '32.1k',
                'price' => '$29.99',
            ],
            [
                'title' => 'Running Performance Sneakers',
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&h=200&fit=crop',
                'promo' => 'HOT',
                'brand' => 'bestbuy',
                'rating' => '4.9',
                'reviews' => '21.5k',
                'likes' => '15.8k',
                'sold' => '67.8k',
                'price' => '$49.99',
            ],
            [
                'title' => 'Smart Home Speaker System',
                'image' => 'https://images.unsplash.com/photo-1558089687-f282ffcbc126?w=200&h=200&fit=crop',
                'promo' => 'SALE',
                'brand' => 'walmart',
                'rating' => '4.5',
                'reviews' => '6.3k',
                'likes' => '4.2k',
                'sold' => '28.9k',
                'price' => '$34.99',
            ],
            [
                'title' => 'Luxury Scented Candle Set',
                'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=200&h=200&fit=crop',
                'promo' => 'BOGO50',
                'brand' => 'target',
                'rating' => '4.7',
                'reviews' => '9.9k',
                'likes' => '7.7k',
                'sold' => '38.4k',
                'price' => '$24.99',
            ],
            [
                'title' => 'Minimalist Leather Jacket',
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=200&h=200&fit=crop',
                'promo' => 'NEW',
                'brand' => 'ebay',
                'rating' => '4.8',
                'reviews' => '15.2k',
                'likes' => '11.3k',
                'sold' => '52.1k',
                'price' => '$59.99',
            ],
            [
                'title' => 'Vintage Wristwatch Collection',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=200&h=200&fit=crop',
                'promo' => 'HOT',
                'brand' => 'costco',
                'rating' => '4.4',
                'reviews' => '7.5k',
                'likes' => '5.1k',
                'sold' => '19.8k',
                'price' => '$44.99',
            ],
            [
                'title' => 'Designer Sunglasses Pack',
                'image' => 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=200&h=200&fit=crop',
                'promo' => 'SALE',
                'brand' => 'lowes',
                'rating' => '4.6',
                'reviews' => '11.2k',
                'likes' => '8.9k',
                'sold' => '41.5k',
                'price' => '$19.99',
            ],
        ];
    }
}
