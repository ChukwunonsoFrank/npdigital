<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseExclusiveProduct>
 */
class WarehouseExclusiveProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exclusive_id' => 'custom-normal-'.Str::uuid(),
            'level' => 'normal',
            'name' => fake()->words(2, true),
            'image_url' => null,
            'price' => fake()->numberBetween(35, 60),
            'name2' => fake()->words(2, true),
            'image_url2' => null,
            'price2' => fake()->numberBetween(20, 40),
            'commission' => 5,
            'description' => fake()->sentence(),
            'is_custom' => true,
            'is_deleted' => false,
        ];
    }
}
