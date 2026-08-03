<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(
                trim((string) $this->input('username'))
            ),
            'email' => $this->filled('email')
                ? mb_strtolower(trim((string) $this->input('email')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                'unique:users,employee_code',
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z0-9._-]+$/',
                'unique:users,username',
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'department' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'is_active' => ['required', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Tên đăng nhập chỉ được gồm chữ thường không dấu, số, dấu chấm, gạch ngang và gạch dưới.',
        ];
    }
}
