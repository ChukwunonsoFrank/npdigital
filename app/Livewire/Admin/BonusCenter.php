<?php

namespace App\Livewire\Admin;

use App\Models\DailyCheckIn;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard', ['title' => 'Bonus Center'])]
class BonusCenter extends Component
{
    public int $setsRequired = 3;

    public int $cycleDays = 5;

    /** @var array<string, list<string>> */
    public array $rewards = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $configuration = PlatformSetting::current()->bonusCenter();
        $this->setsRequired = (int) $configuration['sets_required'];
        $this->cycleDays = (int) $configuration['cycle_days'];
        $this->rewards = $this->normalizedRewards($configuration['rewards']);
    }

    public function updatedCycleDays(): void
    {
        $this->cycleDays = max(1, min(10, $this->cycleDays));
        $this->rewards = $this->normalizedRewards($this->rewards);
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $this->cycleDays = max(1, min(10, $this->cycleDays));

        $rules = [
            'setsRequired' => ['required', 'integer', 'min:1', 'max:10'],
            'cycleDays' => ['required', 'integer', 'min:1', 'max:10'],
        ];

        foreach (array_keys($this->rewards) as $level) {
            $rules['rewards.'.$level] = ['required', 'array', 'min:'.$this->cycleDays];
            $rules['rewards.'.$level.'.*'] = ['required', 'numeric', 'min:0', 'max:99999999.99'];
        }

        $validated = $this->validate($rules, [
            'setsRequired.required' => 'Enter how many sets are needed to unlock a check-in.',
            'cycleDays.required' => 'Enter the reward cycle length.',
        ]);

        $rewards = [];

        foreach ($validated['rewards'] as $level => $amounts) {
            $rewards[$level] = array_map(
                fn ($amount): string => number_format((float) $amount, 2, '.', ''),
                array_slice(array_values($amounts), 0, $this->cycleDays),
            );
        }

        PlatformSetting::query()->updateOrCreate(['id' => 1], [
            'bonus_center' => [
                'sets_required' => (int) $validated['setsRequired'],
                'cycle_days' => (int) $this->cycleDays,
                'rewards' => $rewards,
            ],
        ]);

        $this->rewards = $rewards;
        $this->dispatch('toast', message: 'Bonus Center settings saved.');
    }

    public function render(): View
    {
        $configuration = PlatformSetting::current()->bonusCenter();
        $today = now()->toDateString();

        return view('livewire.admin.bonus-center', [
            'levels' => array_keys($this->rewards),
            'eligibleToday' => User::query()->where('is_admin', false)->where('task_batch', '>=', $configuration['sets_required'])->count(),
            'checkInsToday' => DailyCheckIn::query()->where('checked_in_on', $today)->count(),
            'paidToday' => (int) DailyCheckIn::query()->where('checked_in_on', $today)->sum('reward'),
            'activeCycleDays' => $configuration['cycle_days'],
        ]);
    }

    /**
     * @param  array<string, list<string>>  $rewards
     * @return array<string, list<string>>
     */
    private function normalizedRewards(array $rewards): array
    {
        $defaults = PlatformSetting::defaultBonusCenter()['rewards'];
        $levels = array_unique([...array_keys($defaults), ...array_keys($rewards)]);
        $normalized = [];

        foreach ($levels as $level) {
            $values = array_values($rewards[$level] ?? $defaults[$level] ?? $defaults['Normal']);
            $lastValue = $values === [] ? '0.00' : (string) end($values);
            $values = array_slice($values, 0, $this->cycleDays);

            while (count($values) < $this->cycleDays) {
                $values[] = $lastValue;
            }

            $normalized[$level] = $values;
        }

        return $normalized;
    }
}
