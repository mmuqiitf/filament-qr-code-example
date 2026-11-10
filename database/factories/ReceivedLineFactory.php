<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ReceivedLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceivedLine>
 */
class ReceivedLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gtin = '0'.fake()->ean13();
        $expiresAt = now()->addDays(fake()->numberBetween(30, 720));
        $batch = 'LOT-'.strtoupper(fake()->bothify('####?'));

        return [
            'product_id' => Product::factory()->state(['barcode' => substr($gtin, 1)]),
            'label' => "(01){$gtin}(17){$expiresAt->format('ymd')}(10){$batch}",
            'gtin' => $gtin,
            'batch' => $batch,
            'serial' => null,
            'expires_at' => $expiresAt->toDateString(),
            'net_weight_kg' => null,
            'quantity' => 1,
        ];
    }
}
