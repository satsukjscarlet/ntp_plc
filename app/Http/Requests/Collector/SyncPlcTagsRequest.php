<?php

namespace App\Http\Requests\Collector;

use Illuminate\Foundation\Http\FormRequest;

class SyncPlcTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tags' => ['required', 'array', 'min:1', 'max:10000'],

            'tags.*.address' => ['nullable', 'string', 'max:191'],
            'tags.*.source_name' => ['required', 'string', 'max:500'],
            'tags.*.portal_name' => ['nullable', 'string', 'max:500'],
            'tags.*.data_type' => ['nullable', 'string', 'max:50'],
            'tags.*.unit' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'tags.required' => 'Danh sách tag là bắt buộc.',
            'tags.array' => 'Danh sách tag không đúng định dạng.',
            'tags.*.source_name.required' => 'Mỗi tag phải có source_name.',
        ];
    }
}
