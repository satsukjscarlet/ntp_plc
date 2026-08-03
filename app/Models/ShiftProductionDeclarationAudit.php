<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftProductionDeclarationAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shift_production_declaration_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'reason',
        'before_data',
        'after_data',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(
            ShiftProductionDeclaration::class,
            'shift_production_declaration_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
