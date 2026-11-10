<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'price',
        'stock',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    /**
     * The product whose barcode is this GTIN. A GTIN-14 from a GS1 label is
     * the EAN-13, UPC-A or EAN-8 padded with leading zeros, so match the
     * stored barcode with and without them.
     */
    public static function forGtin(?string $gtin): ?self
    {
        if ($gtin === null || preg_match('/^\d{14}$/', $gtin) !== 1) {
            return null;
        }

        $candidates = [$gtin];

        foreach ([13, 12, 8] as $length) {
            if (str_starts_with($gtin, str_repeat('0', 14 - $length))) {
                $candidates[] = substr($gtin, 14 - $length);
            }
        }

        return static::query()->whereIn('barcode', $candidates)->first();
    }
}
