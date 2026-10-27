<?php

namespace App\Livewire\Dashboard;

use App\Models\DailyCheckIn;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard', ['title' => 'Bonus Center'])]
class BonusCenter extends Component
{
    public function claim(): void
    {
        $user = auth()->user();
        $settings = PlatformSetting::current();
        $configuration = $settings->bonusCenter();
        $cycleDays = max(1, (int) $configuration['cycle_days']);
        $setsRequired = max(1, (int) $configuration['sets_required']);

        if ((int) $user->task_batch < $setsRequired) {
            $this->dispatch('toast', message: 'Complete '.$setsRequired.' sets today to unlock your bonus check-in.');

            return;
        }

        $timezone = $user->timezone ?: config('app.timezone');
        $today = now($timezone)->toDateString();
        $yesterday = now($timezone)->subDay()->toDateString();
        $reward = 0;

        DB::transaction(function () use ($user, $cycleDays, $today, $yesterday, $settings, &$reward): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lastCheckIn = DailyCheckIn::query()
                ->where('user_id', $lockedUser->id)
                ->latest('checked_in_on')
                ->lockForUpdate()
                ->first();

            if ($lastCheckIn && $lastCheckIn->checked_in_on->toDateString() === $today) {
                return;
            }

            $dayNumber = 1;

            if ($lastCheckIn && $lastCheckIn->checked_in_on->toDateString() === $yesterday) {
                $dayNumber = $lastCheckIn->day_number >= $cycleDays ? 1 : $lastCheckIn->day_number + 1;
            }

            $rewards = $settings->bonusRewardsFor((string) $lockedUser->membership_level);
            $fallback = $rewards === [] ? 0.0 : (float) end($rewards);
            $amount = (float) ($rewards[$dayNumber - 1] ?? $fallback);
            $reward = (int) round($amount * 100);

            DailyCheckIn::create([
                'user_id' => $lockedUser->id,
                'checked_in_on' => $today,
                'day_number' => $dayNumber,
                'reward' => $reward,
            ]);

            $lockedUser->increment('balance', $reward);
        });

        if ($reward === 0) {
            $this->dispatch('toast', message: 'You have already checked in today.');

            return;
        }

        $this->dispatch('toast', message: 'Check-in complete. $'.number_format($reward / 100, 2).' added to your balance.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $settings = PlatformSetting::current();
        $configuration = $settings->bonusCenter();
        $cycleDays = max(1, (int) $configuration['cycle_days']);
        $setsRequired = max(1, (int) $configuration['sets_required']);
        $rewards = $settings->bonusRewardsFor((string) $user->membership_level);

        $timezone = $user->timezone ?: config('app.timezone');
        $today = now($timezone)->toDateString();
        $yesterday = now($timezone)->subDay()->toDateString();

        $lastCheckIn = DailyCheckIn::query()
            ->where('user_id', $user->id)
            ->latest('checked_in_on')
            ->first();

        $claimedToday = $lastCheckIn?->checked_in_on->toDateString() === $today;
        $lastCheckInDate = $lastCheckIn?->checked_in_on->toDateString();
        $streakActive = in_array($lastCheckInDate, [$today, $yesterday], true);

        $nextDay = 1;
        $claimedDays = [];

        if ($lastCheckIn && $streakActive) {
            if ($claimedToday) {
                $nextDay = $lastCheckIn->day_number;
                $claimedDays = range(1, $lastCheckIn->day_number);
            } elseif ($lastCheckIn->day_number >= $cycleDays) {
                $nextDay = 1;
            } else {
                $nextDay = $lastCheckIn->day_number + 1;
                $claimedDays = range(1, $lastCheckIn->day_number);
            }
        }
        $progressInSet = min(100, (int) floor(($user->tasks_completed ?? 0) / max(1, $user->productsPerSet()) * 100));
        $setsCompleted = min((int) $user->task_batch, $setsRequired);
        $unlocked = $setsCompleted >= $setsRequired;

        return view('livewire.dashboard.bonus-center', [
            'cycleDays' => $cycleDays,
            'setsRequired' => $setsRequired,
            'setsCompleted' => $setsCompleted,
            'unlocked' => $unlocked,
            'claimedToday' => $claimedToday,
            'rewards' => $rewards,
            'nextDay' => $nextDay,
            'claimedDays' => $claimedDays,
            'level' => (string) $user->membership_level,
            'progressInSet' => $progressInSet,
        ]);
    }
}
