<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftProductionDeclarationItem extends Model
{
    protected $fillable = [
        'shift_production_declaration_id',
        'product_id',
        'machine_product_standard_id',
        'product_code_snapshot',
        'product_name_snapshot',
        'unit_snapshot',
        'standard_weight_g_snapshot',
        'default_length_m_snapshot',
        'actual_length_m',
        'standard_rate_snapshot',
        'rate_unit_snapshot',
        'total_quantity',
        'good_quantity',
        'reject_quantity',
        'scrap_quantity',
        'converted_total_kg',
        'converted_good_kg',
        'converted_reject_kg',
        'converted_scrap_kg',
        'work_order',
        'note',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'standard_weight_g_snapshot' => 'decimal:4',
            'default_length_m_snapshot' => 'decimal:4',
            'actual_length_m' => 'decimal:4',
            'standard_rate_snapshot' => 'decimal:4',
            'total_quantity' => 'decimal:3',
            'good_quantity' => 'decimal:3',
            'reject_quantity' => 'decimal:3',
            'scrap_quantity' => 'decimal:3',
            'converted_total_kg' => 'decimal:3',
            'converted_good_kg' => 'decimal:3',
            'converted_reject_kg' => 'decimal:3',
            'converted_scrap_kg' => 'decimal:3',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(
            ShiftProductionDeclaration::class,
            'shift_production_declaration_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function machineProductStandard(): BelongsTo
    {
        return $this->belongsTo(MachineProductStandard::class);
    }
}
