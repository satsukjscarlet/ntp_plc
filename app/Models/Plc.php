<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plc extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'name',
        'brand',
        'model',
        'ip_address',
        'port',
        'protocol',
        'collector_url',
        'scan_interval',
        'timezone',
        'description',
        'is_active',
        'collector_token_hash',
        'last_seen_at',
        'connection_status',
        'last_error',

    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'scan_interval' => 'integer',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',

        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PlcTag::class);
    }

    public function currentValues()
    {
        return $this->hasManyThrough(
            \App\Models\PlcCurrentValue::class,
            \App\Models\PlcTag::class,
            'plc_id',
            'plc_tag_id'
        );
    }

    public function collectionLogs()
    {
        return $this->hasMany(
            \App\Models\PlcCollectionLog::class
        );
    }
}
