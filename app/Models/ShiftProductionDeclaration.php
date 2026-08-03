<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftProductionDeclaration extends Model
{
    use SoftDeletes;
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'machine_id',
        'production_shift_id',
        'production_date',
        'record_scope',
        'shift_started_at',
        'shift_ended_at',
        'product_code',
        'product_name',
        'work_order',
        'good_quantity',
        'reject_quantity',
        'total_quantity',
        'planned_downtime_minutes',
        'planned_production_minutes',
        'plc_runtime_minutes',
        'unplanned_downtime_minutes',
        'ideal_rate_per_minute',
        'availability',
        'performance',
        'quality_rate',
        'oee',
        'status',
        'operator_id',
        'submitted_at',
        'approved_by',
        'approved_at',
        'returned_by',
        'returned_at',
        'return_reason',
        'locked_by',
        'locked_at',
        'unlocked_by',
        'unlocked_at',
        'unlock_reason',
        'note',
        'calculation_note',
        'deleted_by',
        'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'record_scope' => 'integer',
            'shift_started_at' => 'datetime',
            'shift_ended_at' => 'datetime',
            'good_quantity' => 'decimal:3',
            'reject_quantity' => 'decimal:3',
            'total_quantity' => 'decimal:3',
            'planned_downtime_minutes' => 'decimal:2',
            'planned_production_minutes' => 'decimal:2',
            'plc_runtime_minutes' => 'decimal:2',
            'unplanned_downtime_minutes' => 'decimal:2',
            'ideal_rate_per_minute' => 'decimal:6',
            'availability' => 'decimal:4',
            'performance' => 'decimal:4',
            'quality_rate' => 'decimal:4',
            'oee' => 'decimal:4',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'returned_at' => 'datetime',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ProductionShift::class, 'production_shift_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }


    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function downtimeEntries(): HasMany
    {
        return $this->hasMany(ShiftDowntimeEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShiftProductionDeclarationItem::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(
            ShiftProductionDeclarationAudit::class,
            'shift_production_declaration_id'
        )->latest('created_at')->latest('id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
        ], true);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_SUBMITTED => 'Chờ phê duyệt',
            self::STATUS_RETURNED => 'Đã trả lại',
            self::STATUS_APPROVED => 'Đã phê duyệt',
            self::STATUS_LOCKED => 'Đã khóa',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'text-bg-secondary',
            self::STATUS_SUBMITTED => 'text-bg-warning',
            self::STATUS_RETURNED => 'text-bg-danger',
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_LOCKED => 'text-bg-dark',
            default => 'text-bg-light',
        };
    }
}
