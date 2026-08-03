<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('machines.update') ?? false;
    }

    public function rules(): array
    {
        $machine = $this->route('machine');

        return [
            'production_line_id' => ['required', 'exists:production_lines,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('machines', 'code')->ignore($machine)],
            'name' => ['required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('machines', 'serial_number')->ignore($machine)],
            'installation_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->code))]);
    }
}
