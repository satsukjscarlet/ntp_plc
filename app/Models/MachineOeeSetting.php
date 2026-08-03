<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineOeeSetting extends Model
{
    protected $fillable = [
        'machine_id',
        'availability_tag_id',
        'availability_value_unit',
        'performance_tag_id',
        'performance_aggregation',
        'performance_stability_tolerance',
        'performance_stability_min_seconds',
        'performance_min_valid_value',
        'run_status_tag_id',
        'run_value_mode',
        'run_value_threshold',
        'ideal_rate_per_minute',
        'collector_gap_limit_seconds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'run_value_threshold' => 'float',
            'ideal_rate_per_minute' => 'float',
            'collector_gap_limit_seconds' => 'integer',
            'performance_stability_tolerance' => 'float',
            'performance_stability_min_seconds' => 'integer',
            'performance_min_valid_value' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function availabilityTag(): BelongsTo
    {
        return $this->belongsTo(PlcTag::class, 'availability_tag_id');
    }

    public function performanceTag(): BelongsTo
    {
        return $this->belongsTo(PlcTag::class, 'performance_tag_id');
    }

    // Giữ quan hệ cũ để tương thích với các màn hình/logic trước đây.
    public function runStatusTag(): BelongsTo
    {
        return $this->belongsTo(PlcTag::class, 'run_status_tag_id');
    }
}
