<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringLayoutSection extends Model
{
    protected $fillable = [
        'machine_id',
        'code',
        'name_vi',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(MonitoringLayoutRow::class, 'section_id')
            ->orderBy('display_order')
            ->orderBy('name_vi');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(MonitoringLayoutColumn::class, 'section_id')
            ->orderBy('display_order')
            ->orderBy('name_vi');
    }
}
