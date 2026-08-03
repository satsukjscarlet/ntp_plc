<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlcTag extends Model
{
    protected $fillable = [
        'plc_id',
        'source_key',
        'address',
        'source_name',
        'portal_name',
        'display_name',
        'data_type',
        'display_format',
        'unit',
        'comment',
        'is_active',
        'is_visible',
        'sort_order',
        'last_synced_at',
        'monitoring_section_id',
        'monitoring_row_id',
        'monitoring_column_id',
        'display_unit',
        'display_decimals',
        'warning_low',
        'warning_high',
        'alarm_enabled',
        'blink_on_alarm',
        'chart_enabled',
        'display_color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'last_synced_at' => 'datetime',
        'history_enabled' => 'boolean',
        'history_deadband' => 'float',
        'alarm_low' => 'float',
        'alarm_high' => 'float',
        'display_order' => 'integer',
        'decimal_places' => 'integer',
        'display_decimals' => 'integer',
        'warning_low' => 'float',
        'warning_high' => 'float',
        'alarm_enabled' => 'boolean',
        'blink_on_alarm' => 'boolean',
        'chart_enabled' => 'boolean',

    ];

    public function plc(): BelongsTo
    {
        return $this->belongsTo(Plc::class);
    }

    public function currentValue(): HasOne
    {
        return $this->hasOne(PlcCurrentValue::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(PlcTagValue::class);
    }

    public function monitoringSection()
    {
        return $this->belongsTo(
            \App\Models\MonitoringLayoutSection::class,
            'monitoring_section_id'
        );
    }

    public function monitoringRow()
    {
        return $this->belongsTo(
            \App\Models\MonitoringLayoutRow::class,
            'monitoring_row_id'
        );
    }

    public function monitoringColumn()
    {
        return $this->belongsTo(
            \App\Models\MonitoringLayoutColumn::class,
            'monitoring_column_id'
        );
    }
}
