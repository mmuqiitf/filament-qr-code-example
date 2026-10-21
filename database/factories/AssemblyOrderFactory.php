<?php

namespace Database\Factories;

use App\Models\AssemblyOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssemblyOrder>
 */
class AssemblyOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'WO-'.fake()->unique()->numerify('####'),
            'product_name' => fake()->words(2, true),
            'is_rework' => false,
            'status' => 'open',
        ];
    }
}
