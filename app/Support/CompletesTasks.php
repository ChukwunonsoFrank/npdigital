<?php

namespace App\Support;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Notifications\TaskThresholdReached;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

trait CompletesTasks
{
    /**
     * Dispatch the required-upgrade notice and report whether task submission is blocked.
     */
    protected function blockedByRequiredUpgrade(User $user): bool
    {
        if (! $user->requiresUpgrade()) {
            return false;
        }

        $this->dispatch(
            'upgrade-required',
            message: 'You must upgrade to '.$user->requiredUpgradeLevel().' before you can submit tasks.',
        )->self();

        return true;
    }

    /**
     * Approve a pending task, credit the user's commission and roll the batch when the set is complete.
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

        if ($task->is_merge) {
            $user->update(['negative_balance' => 0]);
        }

        $shouldCreditTrainingBalance = $user->task_batch === 0
            && ! $user->has_made_first_deposit
            && $user->training_balance > 0;

        $user->increment('tasks_completed');
        $user->refresh();

        if ($task->warehouse_product_id !== null) {
            $user->update([
                'submitted_warehouse_products' => array_values(array_unique([
                    ...($user->submitted_warehouse_products ?? []),
                    $task->warehouse_product_id,
                ])),
            ]);
        }

        if ($user->tasks_completed === 10) {
            Notification::route('mail', config('mail.support_address'))
                ->notify(new TaskThresholdReached($user->username));
        }

        if ($user->tasks_completed >= $user->productsPerSet()) {
            $user->increment('task_batch');
            $user->update(['tasks_completed' => 0]);
            $user->refresh();
        }

        $membershipLevel = MembershipLevel::query()
            ->where('name', $user->membership_level)
            ->first();

        if ($task->commission_amount !== null || $task->commission_percentage !== null || $membershipLevel) {
            $commissionInCents = $task->profitInCents((float) ($membershipLevel?->percentage ?? 0));
            $user->increment('daily_commission', $commissionInCents);
            $user->increment('total_commission', $commissionInCents);

            if ($shouldCreditTrainingBalance) {
                $user->increment('training_balance', $commissionInCents);
            } else {
                $user->increment('balance', $commissionInCents);
            }

            if ($user->is_training && $user->training_referral_commission_enabled && $user->referred_by) {
                $referrer = User::query()->where('referral_code', $user->referred_by)->whereKeyNot($user->id)->first();
                $referralCommissionInCents = (int) round($commissionInCents * 0.2);

                if ($referrer && $referralCommissionInCents > 0) {
                    DB::transaction(function () use ($user, $referrer, $referralCommissionInCents): void {
                        $referrer->increment('balance', $referralCommissionInCents);
                        $referrer->increment('daily_commission', $referralCommissionInCents);
                        $referrer->increment('total_commission', $referralCommissionInCents);
                        DB::table('referrals')->insert([
                            'user_id' => $referrer->id,
                            'referral_code' => $user->referral_code,
                            'referred_by' => $referrer->referral_code,
                            'amount' => $referralCommissionInCents,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
                }
            }
        }

        session()->forget('pending_task_id');
        session()->flash('task_completed', 'Task completed successfully');
    }
}
