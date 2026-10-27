<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'location',
        'counted',
        'missing',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'counted' => 'array',
            'missing' => 'array',
        ];
    }
}
