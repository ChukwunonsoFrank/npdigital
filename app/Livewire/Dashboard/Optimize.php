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

    public function submitTask(int $rating = 0): void
    {
        if ($this->blockedByRequiredUpgrade(auth()->user())) {
            return;
        }

        $task = CompletedTask::query()
            ->where('id', session('pending_task_id'))
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $this->completeTask(auth()->user(), $task, max(0, min(5, $rating)));

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

        $commission = $task->profit((float) ($membershipLevel?->percentage ?? 0));

        return view('livewire.dashboard.optimize', [
            'task' => $task,
            'commission' => $commission,
        ]);
    }
}
