<?php

namespace App\Livewire\Dashboard;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Support\CompletesTasks;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Optimize extends Component
{
    use CompletesTasks;

    public int $rating = 0;

    public function setRating(int $rating): void
    {
        $this->rating = ($rating === $this->rating) ? 0 : $rating;
    }

    public function submitTask(): void
    {
        if ($this->blockedByRequiredUpgrade(auth()->user())) {
            return;
        }

        $task = CompletedTask::query()
            ->where('id', session('pending_task_id'))
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $this->completeTask(auth()->user(), $task, $this->rating);

        $this->redirect(route('dashboard.start'));
    }

    public function render()
    {
        $taskId = session('pending_task_id');

        $task = CompletedTask::query()
            ->where('id', $taskId)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (! $task) {
            $this->redirect(route('dashboard.start'));

            return view('livewire.dashboard.optimize', ['task' => null]);
        }

        $user = auth()->user();
        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        $commission = (float) $task->cost * (($task->commission_percentage ?? $membershipLevel?->percentage ?? 0) / 100);

        return view('livewire.dashboard.optimize', [
            'task' => $task,
            'commission' => $commission,
        ]);
    }
}
