<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    public function getCredentials(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
