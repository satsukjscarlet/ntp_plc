<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DowntimeReason extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'MAINTENANCE' => 'Bảo trì',
        'EQUIPMENT' => 'Sự cố thiết bị',
        'MATERIAL' => 'Nguyên liệu',
        'MANPOWER' => 'Nhân lực',
        'QUALITY' => 'Chất lượng',
        'CHANGEOVER' => 'Chuyển đổi sản phẩm',
        'CLEANING' => 'Vệ sinh',
        'UTILITY' => 'Điện/Nước/Khí',
        'OPERATION' => 'Vận hành',
        'OTHER' => 'Khác',
    ];

    protected $fillable = [
        'code', 'name', 'category', 'is_planned', 'affects_availability',
        'requires_note', 'is_active', 'display_color', 'display_order', 'description',
    ];

    protected function casts(): array
    {
        return [
            'is_planned' => 'boolean',
            'affects_availability' => 'boolean',
            'requires_note' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Khác';
    }
}
