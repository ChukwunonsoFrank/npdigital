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
        foreach (['Normal' => ['0.50', 30], 'Silver' => ['0.75', 35], 'Gold' => ['1.00', 40], 'Platinum' => ['1.25', 45], 'Diamond' => ['1.50', 50]] as $name => [$percentage, $tasks]) {
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
            ['name' => 'Silver', 'deposit' => '$399', 'tasks' => 35, 'commission' => '0.75%'],
            ['name' => 'Gold', 'deposit' => '$999', 'tasks' => 40, 'commission' => '1.00%'],
            ['name' => 'Platinum', 'deposit' => '$3,999', 'tasks' => 45, 'commission' => '1.25%'],
            ['name' => 'Diamond', 'deposit' => '$7,999', 'tasks' => 50, 'commission' => '1.50%'],
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
            'terms' => "Every user is required to complete 2 sets a day to be entitled for this program.\nWithdrawable incentives are to be credited to your account consecutively 3, 5, 15 & 30 days.",
        ];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self;
    }
}
