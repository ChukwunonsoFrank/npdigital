<?php

namespace App\Support;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Notifications\TaskThresholdReached;
use Illuminate\Support\Facades\Notification;

trait CompletesTasks
{
    /**
     * Approve a pending task, credit the user's commission and roll the batch when the pole is reached.
     */
    protected function completeTask(User $user, CompletedTask $task, ?int $rating = null, ?string $review = null): void
    {
        $attributes = ['status' => 'approved'];

        if ($rating !== null) {
            $attributes['rating'] = $rating;
        }

        if ($review !== null) {
            $attributes['review'] = $review;
        }

        $task->update($attributes);

        $shouldCreditTrainingBalance = $user->task_batch === 0
            && ! $user->has_made_first_deposit
            && $user->training_balance > 0;

        $user->increment('tasks_completed');
        $user->refresh();

        if ($user->tasks_completed === 10) {
            Notification::route('mail', config('mail.support_address'))
                ->notify(new TaskThresholdReached($user->username));
        }

        if ($user->tasks_completed >= $user->task_pole) {
            $user->increment('task_batch');
            $user->update(['tasks_completed' => 0]);
            $user->refresh();
        }

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        if ($membershipLevel) {
            $commission = (float) $task->cost * ($membershipLevel->percentage / 100);
            $commissionInCents = (int) round($commission * 100);
            $user->increment('daily_commission', $commissionInCents);
            $user->increment('total_commission', $commissionInCents);

            if ($shouldCreditTrainingBalance) {
                $user->increment('training_balance', $commissionInCents);
            } else {
                $user->increment('balance', $commissionInCents);
            }
        }

        session()->forget('pending_task_id');
        session()->flash('task_completed', 'Task completed successfully');
    }
}
