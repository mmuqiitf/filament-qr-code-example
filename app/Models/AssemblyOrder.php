<?php

namespace App\Models;

use Database\Factories\AssemblyOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssemblyOrder extends Model
{
    /** @use HasFactory<AssemblyOrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'product_name',
        'is_rework',
        'status',
    ];

    public static function findOpen(?string $number): ?self
    {
        return $number === null ? null : static::query()->where('number', $number)->where('status', 'open')->first();
    }

    /**
     * @return HasMany<BomLine, $this>
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(BomLine::class);
    }

    /**
     * @return HasMany<AssemblyBuild, $this>
     */
    public function builds(): HasMany
    {
        return $this->hasMany(AssemblyBuild::class);
    }

    /**
     * Component units one build needs, e.g. ['CMP-100' => 2, 'CMP-200' => 1].
     *
     * @return array<string, int>
     */
    public function bom(): array
    {
        return $this->bomLines->mapWithKeys(fn (BomLine $line): array => [$line->component_sku => (int) $line->quantity])->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_rework' => 'boolean',
        ];
    }
}
