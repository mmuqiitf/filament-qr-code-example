<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'component_sku',
        'quantity',
    ];

    /**
     * @return BelongsTo<AssemblyOrder, $this>
     */
    public function assemblyOrder(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrder::class);
    }
}
