<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => 'PO-'.fake()->unique()->numberBetween(1000, 9999),
            'product_id' => Product::factory(),
            'sku' => fn (array $attributes): string => Product::query()->whereKey($attributes['product_id'])->sole()->sku,
            'lot_number' => null,
            'serials' => [],
        ];
    }
}
