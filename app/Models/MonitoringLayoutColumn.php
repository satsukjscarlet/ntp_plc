<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLayoutColumn extends Model
{
    protected $fillable = [
        'section_id',
        'code',
        'name_vi',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(MonitoringLayoutSection::class, 'section_id');
    }
}
