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
            'sku' => 'PRD-'.fake()->unique()->numerify('####'),
            'barcode' => fake()->ean13(),
            'price' => fake()->randomFloat(2, 5, 250),
            'stock' => fake()->numberBetween(10, 150),
            'description' => fake()->sentence(),
        ];
    }
}
