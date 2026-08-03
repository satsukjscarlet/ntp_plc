<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MachineProductStandard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'machine_id', 'product_id', 'standard_rate', 'rate_unit',
        'effective_from', 'effective_to', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'standard_rate' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
