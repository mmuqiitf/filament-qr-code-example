<?php

namespace Database\Seeders;

use App\Models\AssemblyOrder;
use App\Models\Product;
use App\Models\ReceivedLine;
use App\Models\User;
use App\Support\Gs1SampleLabels;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        $demoProducts = [
            [
                'name' => 'Ergonomic Wireless Mouse',
                'sku' => 'PRD-1001',
                'barcode' => '8901234567005',
                'price' => 39.99,
                'stock' => 50,
                'description' => 'Rechargeable 2.4G ergonomic vertical mouse with silent switches.',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'sku' => 'PRD-1002',
                'barcode' => '8901234567012',
                'price' => 89.50,
                'stock' => 35,
                'description' => 'RGB backlit mechanical keyboard with hot-swappable brown switches.',
            ],
            [
                'name' => 'USB-C Fast Charging Hub (7-in-1)',
                'sku' => 'PRD-1003',
                'barcode' => '8901234567029',
                'price' => 49.00,
                'stock' => 100,
                'description' => 'Multiport adapter with 4K HDMI, 100W Power Delivery, and SD reader.',
            ],
            [
                'name' => 'Noise-Cancelling Over-Ear Headphones',
                'sku' => 'PRD-1004',
                'barcode' => '8901234567036',
                'price' => 129.99,
                'stock' => 20,
                'description' => 'Active noise cancellation with 40-hour battery life.',
            ],
            [
                'name' => 'Thermal Shipping Label Printer',
                'sku' => 'PRD-1005',
                'barcode' => '8901234567043',
                'price' => 149.00,
                'stock' => 15,
                'description' => 'High-speed 4x6 commercial direct thermal barcode printer.',
            ],
            [
                'name' => 'Ultra-Wide 34-Inch Curved Monitor',
                'sku' => 'PRD-1006',
                'barcode' => '8901234567050',
                'price' => 499.00,
                'stock' => 8,
                'description' => 'WQHD 144Hz HDR curved gaming and productivity display.',
            ],
            [
                'name' => 'Rugged Mobile Data Terminal',
                'sku' => 'PRD-1007',
                'barcode' => '8901234567067',
                'price' => 899.00,
                'stock' => 0,
                'description' => 'Out-of-stock Android handheld, used by the server-side checks demo.',
            ],
            [
                'name' => 'Direct Thermal Label Rolls 4x6 (case)',
                'sku' => 'PRD-1008',
                'barcode' => substr(Gs1SampleLabels::LABEL_ROLLS_GTIN, 1),
                'price' => 64.00,
                'stock' => 40,
                'description' => 'Case of 12 rolls of 500 labels. Thermal paper has a shelf life, so cases carry a best-before date (GS1 Receiving).',
            ],
            [
                'name' => 'Scanner Battery Pack 3200 mAh',
                'sku' => 'PRD-1009',
                'barcode' => substr(Gs1SampleLabels::BATTERY_PACK_GTIN, 1),
                'price' => 45.00,
                'stock' => 60,
                'description' => 'Serialised Li-ion pack for handheld scanners, received by serial and expiry (GS1 Receiving).',
            ],
            [
                'name' => 'Screen Cleaning Wipes (pallet)',
                'sku' => 'PRD-1010',
                'barcode' => substr(Gs1SampleLabels::WIPES_GTIN, 1),
                'price' => 310.00,
                'stock' => 4,
                'description' => 'Weighed pallet of alcohol wipes; the label carries the net weight (GS1 Receiving).',
            ],
        ];

        foreach ($demoProducts as $productData) {
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }

        $this->seedAssemblyOrders();
        $this->seedReceivedLines();
    }

    /**
     * Work orders for the Manufacturing station (labels on the QR Labels page).
     */
    private function seedAssemblyOrders(): void
    {
        $orders = [
            ['number' => 'WO-5001', 'product_name' => 'Handheld scanner kit', 'is_rework' => false, 'status' => 'open', 'bom' => ['CMP-100' => 2, 'CMP-200' => 1]],
            ['number' => 'WO-5002', 'product_name' => 'Handheld scanner kit (rework)', 'is_rework' => true, 'status' => 'open', 'bom' => ['CMP-300' => 1]],
            ['number' => 'WO-5003', 'product_name' => 'Label printer dock', 'is_rework' => false, 'status' => 'done', 'bom' => ['CMP-400' => 1]],
        ];

        foreach ($orders as $data) {
            $order = AssemblyOrder::firstOrCreate(['number' => $data['number']], collect($data)->except('bom')->all());

            foreach ($data['bom'] as $sku => $quantity) {
                $order->bomLines()->firstOrCreate(['component_sku' => $sku], ['quantity' => $quantity]);
            }
        }
    }

    /**
     * Lines already received on the GS1 Receiving page, one per valid sample
     * label. Dates come from the samples, so they are relative to today.
     */
    private function seedReceivedLines(): void
    {
        foreach (Gs1SampleLabels::valid() as $sample) {
            $label = Gs1::parse($sample['value']);
            $product = Product::forGtin($label->get('01'));

            if ($product === null || ReceivedLine::query()->where('label', $label->bracketed())->exists()) {
                continue;
            }

            ReceivedLine::create([
                'product_id' => $product->getKey(),
                'label' => $label->bracketed(),
                'gtin' => $label->get('01'),
                'batch' => $label->get('10'),
                'serial' => $label->get('21'),
                'expires_at' => $label->date('17')?->toDateString(),
                'net_weight_kg' => $label->decimal('3103'),
                'quantity' => (int) ($label->get('30') ?? 1),
            ]);
        }
    }
}
