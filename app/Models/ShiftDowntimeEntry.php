<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftDowntimeEntry extends Model
{
    protected $fillable = [
        'shift_production_declaration_id',
        'downtime_reason_id',
        'minutes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'decimal:2',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(
            ShiftProductionDeclaration::class,
            'shift_production_declaration_id'
        );
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(DowntimeReason::class, 'downtime_reason_id');
    }
}
