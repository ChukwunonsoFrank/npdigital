<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    /** @use HasFactory<\Database\Factories\PlatformSettingFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $attributes = [
        'maintenance_mode' => false,
        'allow_registrations' => true,
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'allow_registrations' => 'boolean',
            'deposit_wallet_addresses' => 'array',
            'check_in_program' => 'array',
            'bonus_center' => 'array',
            'membership_chart' => 'array',
            'membership_perks' => 'array',
            'business_licenses' => 'array',
        ];
    }

    /** @return array<string, list<string>> */
    public function membershipPerks(): array
    {
        if ($this->membership_perks !== null) {
            return $this->membership_perks;
        }

        $perks = [];
        foreach (['Normal' => ['0.50', 30], 'Silver' => ['0.75', 30], 'Gold' => ['1.00', 30], 'Platinum' => ['1.25', 30], 'Diamond' => ['1.50', 30], 'VVIP' => ['2.00', 30]] as $name => [$percentage, $tasks]) {
            $perks[$name] = [
                $name.' users are assigned general usage access to data collection',
                'Applicable to most data collection situations of light to medium level of usage involving the Products',
                'Profits of '.$percentage.'% per Product - '.$tasks.' products per set',
                'No access to other premium features',
            ];
        }

        return $perks;
    }

    /** @return list<array{name: string, deposit: string, tasks: int|string, commission: string}> */
    public function membershipChart(): array
    {
        return $this->membership_chart ?? [
            ['name' => 'Normal', 'deposit' => '$99', 'tasks' => 30, 'commission' => '0.50%'],
            ['name' => 'Silver', 'deposit' => '$399', 'tasks' => 30, 'commission' => '0.75%'],
            ['name' => 'Gold', 'deposit' => '$999', 'tasks' => 30, 'commission' => '1.00%'],
            ['name' => 'Platinum', 'deposit' => '$3,999', 'tasks' => 30, 'commission' => '1.25%'],
            ['name' => 'Diamond', 'deposit' => '$7,999', 'tasks' => 30, 'commission' => '1.50%'],
            ['name' => 'VVIP', 'deposit' => '$15,999', 'tasks' => 30, 'commission' => '2.00%'],
        ];
    }

    /** @return array{milestones: list<array{day: int, reward: int|float|string}>, terms: string} */
    public function checkInProgram(): array
    {
        return $this->check_in_program ?? [
            'milestones' => [
                ['day' => 3, 'reward' => 500],
                ['day' => 5, 'reward' => 1000],
                ['day' => 15, 'reward' => 1500],
                ['day' => 30, 'reward' => 2000],
            ],
            'terms' => 'Every user is required to complete '.User::DAILY_TASK_SETS.' sets a day to be entitled for this program.'."\nWithdrawable incentives are to be credited to your account consecutively 3, 5, 15 & 30 days.",
        ];
    }

    /**
     * Bonus Center configuration, merged with defaults so new levels always have rewards.
     *
     * @return array{sets_required: int, cycle_days: int, rewards: array<string, list<string>>}
     */
    public function bonusCenter(): array
    {
        $defaults = self::defaultBonusCenter();
        $stored = $this->bonus_center ?? [];

        return [
            'sets_required' => (int) ($stored['sets_required'] ?? $defaults['sets_required']),
            'cycle_days' => (int) ($stored['cycle_days'] ?? $defaults['cycle_days']),
            'rewards' => array_replace_recursive($defaults['rewards'], $stored['rewards'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    public function bonusRewardsFor(string $level): array
    {
        $rewards = $this->bonusCenter()['rewards'];

        foreach ($rewards as $name => $amounts) {
            if (strcasecmp($name, $level) === 0) {
                return array_values($amounts);
            }
        }

        return array_values($rewards['Normal'] ?? self::defaultBonusCenter()['rewards']['Normal']);
    }

    /**
     * @return array{sets_required: int, cycle_days: int, rewards: array<string, list<string>>}
     */
    public static function defaultBonusCenter(): array
    {
        return [
            'sets_required' => User::DAILY_TASK_SETS,
            'cycle_days' => 5,
            'rewards' => [
                'Normal' => ['5.00', '5.00', '10.00', '10.00', '50.00'],
                'Silver' => ['5.00', '5.00', '10.00', '10.00', '100.00'],
                'Gold' => ['5.00', '5.00', '10.00', '10.00', '250.00'],
                'Platinum' => ['5.00', '5.00', '10.00', '10.00', '500.00'],
                'Diamond' => ['5.00', '5.00', '10.00', '10.00', '1000.00'],
                'VVIP' => ['5.00', '5.00', '10.00', '10.00', '2000.00'],
            ],
        ];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self;
    }
}
