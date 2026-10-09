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

    private function usesTrainingBalance(User $user): bool
    {
        return $user->task_batch === 0
            && ! $user->has_made_first_deposit
            && $user->training_balance > 0;
    }

    private function getActiveTaskBalanceInCents(User $user): int
    {
        if ($this->usesTrainingBalance($user)) {
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
        $activeTaskBalanceInCents = $this->getActiveTaskBalanceInCents($user);

        if ($user->task_batch >= 3) {
            $this->dispatch('task-limit-reached', message: 'Task limit reached. Come back tommorrow for more tasks.')->self();

            return;
        }

        if ($user->task_batch >= 1 && ! $user->has_made_first_deposit) {
            $this->dispatch('task-limit-reached', message: 'You need to make your first deposit of $100 to continue with tasks.')->self();

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

        if ($user->tasks_completed >= $user->task_pole && $user->lien_status === 'on_hold' && $user->lien_amount !== null) {
            $this->dispatch('task-limit-reached', message: 'You need to make an extra deposit to proceed with completion of tasks.')->self();

            return;
        }

        if ($user->tasks_completed >= $user->task_pole) {
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

        $task = Task::query()
            ->where('task_level', $user->membership_level)
            ->inRandomOrder()
            ->first();

        if (! $task) {
            $this->dispatch('task-limit-reached', message: 'No tasks available for your membership level at this time.')->self();

            return;
        }

        $taskCostInCents = (int) round((float) $task->cost * 100);

        if ($activeTaskBalanceInCents < $taskCostInCents) {
            $this->dispatch('insufficient-balance', message: 'Insufficient balance to complete task. Top up to continue.')->self();

            return;
        }

        $completedTask = CompletedTask::create([
            'user_id' => $user->id,
            'title' => $task->title,
            'cost' => $task->cost,
            'task_image_path' => $task->task_image_path,
            'rating' => '0',
            'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
            'status' => 'pending',
        ]);

        session(['pending_task_id' => $completedTask->id]);

        $this->openTaskModal();
    }

    public function render()
    {
        $user = auth()->user();

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        $commissionPercentage = (float) ($membershipLevel?->percentage ?? 0);
        $pendingTask = $this->pendingTask();
        $commission = $pendingTask ? (float) $pendingTask->cost * ($commissionPercentage / 100) : 0;

        return view('livewire.dashboard.start', [
            'user' => $user,
            'products' => $this->products(),
            'pendingTask' => $pendingTask,
            'commissionPercentage' => $commissionPercentage,
            'commission' => $commission,
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
                'brand' => 'Amazon',
                'brand_color' => 'rgb(255, 140, 0)',
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
                'brand' => 'SHEIN',
                'brand_color' => 'rgb(26, 26, 26)',
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
                'brand' => 'Nike',
                'brand_color' => 'rgb(38, 38, 38)',
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
                'brand' => 'Walmart',
                'brand_color' => 'rgb(23, 115, 207)',
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
                'brand' => 'Target',
                'brand_color' => 'rgb(219, 36, 36)',
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
                'brand' => 'Zara',
                'brand_color' => 'rgb(26, 26, 26)',
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
                'brand' => 'Alibaba',
                'brand_color' => 'rgb(243, 114, 22)',
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
                'brand' => 'eBay',
                'brand_color' => 'rgb(219, 36, 36)',
                'rating' => '4.6',
                'reviews' => '11.2k',
                'likes' => '8.9k',
                'sold' => '41.5k',
                'price' => '$19.99',
            ],
        ];
    }
}
