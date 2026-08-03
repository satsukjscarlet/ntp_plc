<?php

namespace App\Http\Requests\Api\Collector;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plc_code' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:ONLINE,ERROR,OFFLINE'],
            'message' => ['nullable', 'string', 'max:2000'],
            'sent_at' => ['nullable', 'date'],
        ];
    }
}
