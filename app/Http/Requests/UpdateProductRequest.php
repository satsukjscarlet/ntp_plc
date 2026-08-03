<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'material_code' => mb_strtoupper(trim((string) $this->input('material_code'))),
            'material_name' => trim((string) $this->input('material_name')),
            'unit' => trim((string) $this->input('unit')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'material_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'material_code')->ignore($product?->id),
            ],
            'material_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'standard_weight_g' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'default_length_m' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'material_code' => 'mã vật tư',
            'material_name' => 'tên vật tư',
            'unit' => 'đơn vị tính',
            'standard_weight_g' => 'định mức khối lượng',
            'default_length_m' => 'chiều dài mặc định',
            'is_active' => 'trạng thái',
            'notes' => 'ghi chú',
        ];
    }
}
