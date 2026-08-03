<?php

namespace App\Http\Requests;

use App\Models\PlcTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlcTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plc-tags.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'plc_id' => ['required', 'exists:plcs,id'],
            'address' => ['nullable', 'string', 'max:100'],
            'source_name' => [
                'required', 'string', 'max:255',
                Rule::unique('plc_tags')->where(
                    fn ($q) => $q->where('plc_id', $this->plc_id)
                ),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', Rule::in(PlcTag::DATA_TYPES)],
            'unit' => ['nullable', 'string', 'max:50'],
            'display_group' => ['nullable', 'string', 'max:100'],
            'display_order' => ['required', 'integer', 'min:0'],
            'oee_role' => ['nullable', Rule::in(PlcTag::OEE_ROLES)],
            'is_visible' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
