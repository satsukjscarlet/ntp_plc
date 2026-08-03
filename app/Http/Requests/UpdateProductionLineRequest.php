<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production-lines.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'plant_id' => ['required', 'exists:plants,id'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('production_lines')
                    ->where(fn ($q) => $q->where('plant_id', $this->plant_id))
                    ->ignore($this->route('production_line')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->code))]);
    }
}
