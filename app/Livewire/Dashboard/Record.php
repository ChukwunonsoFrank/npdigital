<?php

namespace App\Livewire\Dashboard;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Support\CompletesTasks;
use App\Support\HasTaskModal;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
class Record extends Component
{
    use CompletesTasks;
    use HasTaskModal;
    use WithPagination;

    public function mount(): void
    {
        if (session('pending_task_warning')) {
            $this->dispatch('pending-task', message: session('pending_task_warning'))->self();
        }
    }

    public function openSubmission(int $taskId): void
    {
        $task = CompletedTask::query()
            ->where('id', $taskId)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (! $task) {
            return;
        }

        session(['pending_task_id' => $task->id]);

        $this->openTaskModal(pendingAlreadyExists: true);
    }

    public function render()
    {
        $user = auth()->user();

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        $commissionPercentage = (float) ($membershipLevel?->percentage ?? 0);

        $pendingTasks = CompletedTask::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $submittedTasks = CompletedTask::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate(10);

        $pendingTask = $this->pendingTask();
        $pendingCommissionPercentage = (float) ($pendingTask?->commission_percentage ?? $commissionPercentage);

        if ($pendingTask?->commission_amount !== null && (float) $pendingTask->cost > 0) {
            $pendingCommissionPercentage = round(((int) $pendingTask->commission_amount / 100) / (float) $pendingTask->cost * 100, 2);
        }

        $commission = $pendingTask ? $pendingTask->profit($commissionPercentage) : 0;

        return view('livewire.dashboard.record', [
            'pendingTasks' => $pendingTasks,
            'submittedTasks' => $submittedTasks,
            'totalSubmitted' => $submittedTasks->total(),
            'pendingCount' => $pendingTasks->count(),
            'totalEarned' => ((int) $user->total_commission) / 100,
            'commissionPercentage' => $commissionPercentage,
            'pendingCommissionPercentage' => $pendingCommissionPercentage,
            'pendingTask' => $pendingTask,
            'commission' => $commission,
            'topUpAmount' => $pendingTask?->is_merge ? (int) $user->negative_balance : 0,
        ]);
    }
}
