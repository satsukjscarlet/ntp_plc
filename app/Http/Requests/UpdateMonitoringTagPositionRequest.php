<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitoringTagPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monitoring_section_id' => ['nullable', 'integer'],
            'monitoring_row_id' => ['nullable', 'integer'],
            'monitoring_column_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'monitoring_section_id.integer' => 'Loại thông số không hợp lệ.',
            'monitoring_row_id.integer' => 'Dòng hiển thị không hợp lệ.',
            'monitoring_column_id.integer' => 'Cột hiển thị không hợp lệ.',
        ];
    }
}
