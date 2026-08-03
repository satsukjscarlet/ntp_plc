<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlcDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('collector_plc');
    }

    public function rules(): array
    {
        return [
            'request_id' => ['nullable', 'string', 'max:100'],
            'collector_name' => ['nullable', 'string', 'max:150'],
            'collector_version' => ['nullable', 'string', 'max:50'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],

            'values' => ['required', 'array', 'min:1', 'max:5000'],

            'values.*.tag_id' => ['nullable', 'integer'],
            'values.*.source_name' => ['nullable', 'string', 'max:255'],
            'values.*.address' => ['nullable', 'string', 'max:100'],

            'values.*.raw_value' => ['nullable'],
            'values.*.numeric_value' => ['nullable', 'numeric'],
            'values.*.text_value' => ['nullable', 'string'],

            'values.*.quality' => [
                'nullable',
                Rule::in(['GOOD', 'BAD', 'UNCERTAIN']),
            ],

            'values.*.collected_at' => ['required', 'date'],
            'values.*.save_history' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'values.required' => 'Danh sách values là bắt buộc.',
            'values.*.collected_at.required' =>
                'Mỗi giá trị phải có collected_at.',
        ];
    }
}
