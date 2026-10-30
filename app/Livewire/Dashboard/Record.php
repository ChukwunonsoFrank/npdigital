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

        $pendingTaskModals = $pendingTasks->map(function (CompletedTask $task) use ($commissionPercentage, $user): array {
            $percentage = (float) ($task->commission_percentage ?? $commissionPercentage);

            if ($task->commission_amount !== null && (float) $task->cost > 0) {
                $percentage = round(((int) $task->commission_amount / 100) / (float) $task->cost * 100, 2);
            }

            return [
                'task' => $task,
                'commission' => $task->profit($commissionPercentage),
                'commission_percentage' => $percentage,
                'top_up_amount' => $task->is_merge ? (int) $user->negative_balance : 0,
            ];
        });

        return view('livewire.dashboard.record', [
            'pendingTasks' => $pendingTasks,
            'pendingTaskModals' => $pendingTaskModals,
            'submittedTasks' => $submittedTasks,
            'totalSubmitted' => $submittedTasks->total(),
            'pendingCount' => $pendingTasks->count(),
            'totalEarned' => ((int) $user->total_commission) / 100,
            'commissionPercentage' => $commissionPercentage,
        ]);
    }
}
