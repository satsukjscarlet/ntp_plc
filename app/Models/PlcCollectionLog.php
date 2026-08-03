<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlcCollectionLog extends Model
{
    protected $fillable = [
        'plc_id',
        'status',
        'total_tags',
        'current_values',
        'history_values',
        'unknown_tags',
        'duration_ms',
        'collector_name',
        'collector_version',
        'request_id',
        'message',
        'metadata',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function plc(): BelongsTo
    {
        return $this->belongsTo(Plc::class);
    }
}
