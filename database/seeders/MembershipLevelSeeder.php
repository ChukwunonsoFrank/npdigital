<?php

namespace Database\Seeders;

use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;

class MembershipLevelSeeder extends Seeder
{
    /**
     * Seed the membership tiers used by the upgrade dropdowns and task gating.
     */
    public function run(): void
    {
        $levels = [
            ['name' => 'Normal', 'percentage' => '0.50', 'minimum_balance' => '99.00'],
            ['name' => 'Silver', 'percentage' => '0.75', 'minimum_balance' => '399.00'],
            ['name' => 'Gold', 'percentage' => '1.00', 'minimum_balance' => '999.00'],
            ['name' => 'Platinum', 'percentage' => '1.25', 'minimum_balance' => '3999.00'],
            ['name' => 'Diamond', 'percentage' => '1.50', 'minimum_balance' => '7999.00'],
        ];

        foreach ($levels as $level) {
            MembershipLevel::query()->firstOrCreate(
                ['name' => $level['name']],
                $level,
            );
        }
    }
}
