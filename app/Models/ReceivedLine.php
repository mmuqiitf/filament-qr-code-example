<?php

namespace App\Models;

use Database\Factories\ReceivedLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stock line received from a GS1 label on the GS1 Receiving page.
 */
class ReceivedLine extends Model
{
    /** @use HasFactory<ReceivedLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'label',
        'gtin',
        'batch',
        'serial',
        'expires_at',
        'net_weight_kg',
        'quantity',
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
            'expires_at' => 'date',
            'net_weight_kg' => 'decimal:3',
            'quantity' => 'integer',
        ];
    }
}
