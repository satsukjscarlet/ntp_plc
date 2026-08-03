<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'code',
        'name',
        'model',
        'manufacturer',
        'serial_number',
        'installation_date',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function plcs(): HasMany
    {
        return $this->hasMany(Plc::class);
    }

    public function oeeSetting()
    {
        return $this->hasOne(MachineOeeSetting::class);
    }

    public function productionDeclarations(): HasMany
    {
        return $this->hasMany(ShiftProductionDeclaration::class);
    }

    public function productStandards(): HasMany
    {
        return $this->hasMany(MachineProductStandard::class);
    }

    public function monitoringLayoutSections()
    {
        return $this->hasMany(
            \App\Models\MonitoringLayoutSection::class
        );
    }
}
