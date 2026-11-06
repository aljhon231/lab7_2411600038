<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement([
                'Lumber',
                'Hardware',
                'Tools',
                'Electrical',
                'Plumbing',
                'Paint',
            ]),
            'quantity' => fake()->numberBetween(0, 100),
            'reorder_level' => fake()->numberBetween(1, 20),
            'unit_price' => fake()->randomFloat(2, 0, 50000),
            'supplier' => fake()->company(),
        ];
    }
}
