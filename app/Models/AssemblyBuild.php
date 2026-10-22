<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyBuild extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'assembly_order_id',
        'operator_badge',
        'operator_name',
        'rework_reason',
        'components',
    ];

    /**
     * @return BelongsTo<AssemblyOrder, $this>
     */
    public function assemblyOrder(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'components' => 'array',
        ];
    }
}
