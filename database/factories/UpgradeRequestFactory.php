<?php

namespace Database\Factories;

use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UpgradeRequest>
 */
class UpgradeRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'membership_level_id' => fn () => MembershipLevel::query()->firstOrCreate(
                ['name' => 'Silver'],
                ['percentage' => '0.75', 'minimum_balance' => '399.00'],
            )->id,
            'from_level' => 'Normal',
            'status' => 'pending',
        ];
    }
}
