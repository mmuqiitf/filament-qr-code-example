<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                'barcode' => '8901234567890',
                'price' => 39.99,
                'stock' => 50,
                'description' => 'Rechargeable 2.4G ergonomic vertical mouse with silent switches.',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'sku' => 'PRD-1002',
                'barcode' => '8901234567891',
                'price' => 89.50,
                'stock' => 35,
                'description' => 'RGB backlit mechanical keyboard with hot-swappable brown switches.',
            ],
            [
                'name' => 'USB-C Fast Charging Hub (7-in-1)',
                'sku' => 'PRD-1003',
                'barcode' => '8901234567892',
                'price' => 49.00,
                'stock' => 100,
                'description' => 'Multiport adapter with 4K HDMI, 100W Power Delivery, and SD reader.',
            ],
            [
                'name' => 'Noise-Cancelling Over-Ear Headphones',
                'sku' => 'PRD-1004',
                'barcode' => '8901234567893',
                'price' => 129.99,
                'stock' => 20,
                'description' => 'Active noise cancellation with 40-hour battery life.',
            ],
            [
                'name' => 'Thermal Shipping Label Printer',
                'sku' => 'PRD-1005',
                'barcode' => '8901234567894',
                'price' => 149.00,
                'stock' => 15,
                'description' => 'High-speed 4x6 commercial direct thermal barcode printer.',
            ],
            [
                'name' => 'Ultra-Wide 34-Inch Curved Monitor',
                'sku' => 'PRD-1006',
                'barcode' => '8901234567895',
                'price' => 499.00,
                'stock' => 8,
                'description' => 'WQHD 144Hz HDR curved gaming and productivity display.',
            ],
        ];

        foreach ($demoProducts as $productData) {
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }
    }
}
