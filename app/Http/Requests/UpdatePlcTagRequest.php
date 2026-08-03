<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlcTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plc-tags.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'display_group' => ['nullable', 'string', 'max:100'],
            'display_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:6'],
            'is_visible' => ['required', 'boolean'],
            'history_enabled' => ['required', 'boolean'],
            'history_deadband' => ['required', 'numeric', 'min:0'],
            'alarm_low' => ['nullable', 'numeric'],
            'alarm_high' => ['nullable', 'numeric', 'gte:alarm_low'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_visible' => $this->boolean('is_visible'),
            'history_enabled' => $this->boolean('history_enabled'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'display_name' => 'tên hiển thị',
            'unit' => 'đơn vị',
            'display_group' => 'nhóm hiển thị',
            'display_order' => 'thứ tự',
            'decimal_places' => 'số chữ số thập phân',
            'is_visible' => 'trạng thái hiển thị',
            'history_enabled' => 'trạng thái lưu lịch sử',
            'history_deadband' => 'deadband',
            'alarm_low' => 'ngưỡng cảnh báo thấp',
            'alarm_high' => 'ngưỡng cảnh báo cao',
        ];
    }
}
