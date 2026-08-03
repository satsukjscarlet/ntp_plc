<?php

namespace App\Http\Requests;

use App\Models\DowntimeReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDowntimeReasonRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'is_planned' => $this->boolean('is_planned'),
            'affects_availability' => $this->boolean('affects_availability'),
            'requires_note' => $this->boolean('requires_note'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('downtimeReason')?->id;
        return [
            'code' => ['required','string','max:50','regex:/^[A-Z0-9_-]+$/',Rule::unique('downtime_reasons','code')->ignore($id)],
            'name' => ['required','string','max:150'],
            'category' => ['required',Rule::in(array_keys(DowntimeReason::CATEGORIES))],
            'is_planned' => ['required','boolean'],
            'affects_availability' => ['required','boolean'],
            'requires_note' => ['required','boolean'],
            'is_active' => ['required','boolean'],
            'display_color' => ['nullable','string','max:20'],
            'display_order' => ['nullable','integer','min:0','max:9999'],
            'description' => ['nullable','string','max:2000'],
        ];
    }
}
