<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftProductionDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST')
            ? 'production-declarations.create'
            : 'production-declarations.update';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'production_shift_id' => ['required', 'integer', 'exists:production_shifts,id'],
            'production_date' => ['required', 'date'],
            'planned_downtime_minutes' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:3000'],
            'submit_action' => ['required', Rule::in(['draft', 'submit'])],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.actual_length_m' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.good_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.reject_quantity' => ['nullable', 'numeric', 'min:0'],
            // Phế liệu được khai báo độc lập bằng kg.
            'items.*.scrap_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.work_order' => ['nullable', 'string', 'max:100'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],

            'downtimes' => ['nullable', 'array'],
            'downtimes.*.downtime_reason_id' => ['nullable', 'integer', 'exists:downtime_reasons,id'],
            'downtimes.*.minutes' => ['nullable', 'numeric', 'min:0'],
            'downtimes.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $total = (float) ($item['total_quantity'] ?? 0);
                $good = (float) ($item['good_quantity'] ?? 0);
                $reject = (float) ($item['reject_quantity'] ?? 0);

                // Tổng sản lượng, chính phẩm và phế phẩm cùng đơn vị cây/cuộn.
                // Phế liệu là kg riêng, tuyệt đối không cộng vào phép đối soát này.
                if (abs(($good + $reject) - $total) > 0.0005) {
                    $validator->errors()->add(
                        "items.$index.total_quantity",
                        'Tổng sản lượng phải bằng Chính phẩm + Phế phẩm. Phế liệu kg được nhập và theo dõi riêng.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'machine_id.required' => 'Vui lòng chọn máy.',
            'production_shift_id.required' => 'Vui lòng chọn ca.',
            'production_date.required' => 'Vui lòng chọn ngày sản xuất.',
            'items.required' => 'Vui lòng nhập ít nhất một dòng sản phẩm.',
            'items.min' => 'Vui lòng nhập ít nhất một dòng sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.distinct' => 'Một sản phẩm chỉ được khai báo một dòng trong cùng phiếu.',
            'items.*.total_quantity.required' => 'Vui lòng nhập tổng sản lượng.',
            'items.*.good_quantity.required' => 'Vui lòng nhập chính phẩm.',
        ];
    }
}
