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

    public bool $showTaskModal = false;

    protected function pendingTask(): ?CompletedTask
    {
        return CompletedTask::query()
            ->where('id', session('pending_task_id'))
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    }

    public function setRating(int $rating): void
    {
        $this->rating = ($rating === $this->rating) ? 0 : $rating;
    }

    public function openTaskModal(): void
    {
        $this->rating = 0;
        $this->review = '';
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->rating = 0;
        $this->review = '';
    }

    public function submitTask(): void
    {
        $task = $this->pendingTask();

        if (! $task) {
            $this->closeTaskModal();

            return;
        }

        if ($this->blockedByRequiredUpgrade(auth()->user())) {
            $this->closeTaskModal();

            return;
        }

        if ($task->is_merge && (int) auth()->user()->negative_balance > 0) {
            $this->dispatch(
                'insufficient-balance',
                message: 'Top up $'.number_format(auth()->user()->negative_balance / 100, 2).' to submit this merge product.',
            )->self();

            return;
        }

        $this->completeTask(auth()->user(), $task, $this->rating, $this->review);

        session()->forget('task_completed');

        $this->closeTaskModal();

        $this->dispatch('task-completed', message: 'Task completed successfully');
    }
}
