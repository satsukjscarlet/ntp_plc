<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagMonitoringPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monitoring_section_id' => [
                'nullable',
                'integer',
                Rule::exists('monitoring_layout_sections', 'id'),
            ],
            'monitoring_row_id' => [
                'nullable',
                'integer',
                Rule::exists('monitoring_layout_rows', 'id'),
            ],
            'monitoring_column_id' => [
                'nullable',
                'integer',
                Rule::exists('monitoring_layout_columns', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monitoring_section_id.exists' => 'Loại thông số đã chọn không tồn tại.',
            'monitoring_row_id.exists' => 'Dòng hiển thị đã chọn không tồn tại.',
            'monitoring_column_id.exists' => 'Cột hiển thị đã chọn không tồn tại.',
        ];
    }
}
