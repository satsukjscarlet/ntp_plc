<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionShift extends Model
{
    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(ShiftProductionDeclaration::class);
    }
}
