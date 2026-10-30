<?php

namespace App\Support;

use App\Models\CompletedTask;

/**
 * Adds the "submit task" modal state and actions to a component.
 *
 * The consuming component must also use {@see CompletesTasks}.
 */
trait HasTaskModal
{
    public int $rating = 0;

    public string $review = '';

    protected function pendingTask(?int $taskId = null): ?CompletedTask
    {
        $taskId ??= session('pending_task_id');

        if (! $taskId) {
            return null;
        }

        return CompletedTask::query()
            ->whereKey($taskId)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    }

    public function submitTask(?int $taskId = null, ?int $rating = null, ?string $review = null): void
    {
        $task = $this->pendingTask($taskId);

        if (! $task) {
            $this->dispatch('close-task-modal');

            return;
        }

        if ($this->blockedByRequiredUpgrade(auth()->user())) {
            $this->dispatch('close-task-modal');

            return;
        }

        if ($task->is_merge && (int) auth()->user()->negative_balance > 0) {
            $this->dispatch(
                'insufficient-balance',
                message: 'Top up $'.number_format(auth()->user()->negative_balance / 100, 2).' to submit this merge product.',
            )->self();

            return;
        }

        $this->completeTask(
            auth()->user(),
            $task,
            max(0, min(5, $rating ?? $this->rating)),
            trim($review ?? $this->review),
        );

        session()->forget('task_completed');

        $this->reset('rating', 'review');
        $this->dispatch('close-task-modal');
        $this->dispatch('task-completed', message: 'Task completed successfully');
    }
}
