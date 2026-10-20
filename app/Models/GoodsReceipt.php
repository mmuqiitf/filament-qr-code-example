<?php

namespace App\Models;

use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'po_number',
        'product_id',
        'sku',
        'lot_number',
        'serials',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'serials' => 'array',
        ];
    }
}
