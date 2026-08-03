<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plcs.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'exists:machines,id'],
            'name' => ['required', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'protocol' => ['required', Rule::in(['HTTP_PORTAL', 'S7', 'MODBUS_TCP', 'OPC_UA'])],
            'collector_url' => ['nullable', 'url', 'max:2000'],
            'scan_interval' => ['required', 'integer', 'min:1', 'max:3600'],
            'timezone' => ['required', 'timezone'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
