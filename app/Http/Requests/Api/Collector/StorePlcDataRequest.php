<?php

namespace App\Http\Requests\Api\Collector;

use Illuminate\Foundation\Http\FormRequest;

class StorePlcDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * Token đã được middleware collector kiểm tra.
         */
        return true;
    }

    public function rules(): array
    {
        return [
            'plc_code' => ['nullable', 'string', 'max:100'],
            'collected_at' => ['required', 'date'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],

            'values' => ['required', 'array', 'min:1'],
            'values.*.source_key' => ['required', 'string', 'max:255'],
            'values.*.address' => ['nullable', 'string', 'max:255'],
            'values.*.source_name' => ['required', 'string', 'max:255'],
            'values.*.data_type' => ['nullable', 'string', 'max:100'],
            'values.*.display_format' => ['nullable', 'string', 'max:100'],
            'values.*.value' => ['nullable'],
            'values.*.raw_value' => ['nullable'],
            'values.*.quality' => ['nullable', 'string', 'max:30'],
            'values.*.comment' => ['nullable', 'string'],
            'values.*.collected_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'collected_at.required' => 'Thời gian thu thập chung là bắt buộc.',
            'values.required' => 'Danh sách values là bắt buộc.',
            'values.array' => 'Danh sách values không đúng định dạng.',
            'values.min' => 'Danh sách values phải có ít nhất một phần tử.',
            'values.*.source_key.required' => 'Mỗi giá trị phải có source_key.',
            'values.*.source_name.required' => 'Mỗi giá trị phải có source_name.',
        ];
    }
}
