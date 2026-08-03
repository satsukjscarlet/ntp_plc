<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlcTagValue extends Model
{
    protected $fillable = [
        'plc_tag_id',
        'raw_value',
        'numeric_value',
        'text_value',
        'quality',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:6',
            'collected_at' => 'datetime',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(PlcTag::class, 'plc_tag_id');
    }
}
