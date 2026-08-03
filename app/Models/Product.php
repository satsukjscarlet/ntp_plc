<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'material_code',
        'material_name',
        'unit',
        'standard_weight_g',
        'default_length_m',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'standard_weight_g' => 'decimal:4',
            'default_length_m' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
    public function machineStandards(): HasMany { return $this->hasMany(MachineProductStandard::class); }
}
