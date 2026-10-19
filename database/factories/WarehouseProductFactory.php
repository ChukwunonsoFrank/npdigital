<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseProduct>
 */
class WarehouseProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => 'custom-normal-d1-a-'.Str::uuid(),
            'level' => 'normal',
            'day' => 1,
            'group_key' => 'a',
            'name' => fake()->words(2, true),
            'image_url' => null,
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(28, 38),
            'commission' => 0.5,
            'is_custom' => true,
            'is_deleted' => false,
        ];
    }
}
