<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by UserPolicy
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->getKey() ?? $this->input('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:500'],
            'user_type' => ['required', 'string', Rule::in(['mahasiswa', 'staff'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'profile_completed' => ['nullable', 'boolean'],
            'profile_completed_at' => ['nullable', 'date'],
            'blocked_until' => ['nullable', 'date'],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.in' => 'Tipe user harus salah satu dari: mahasiswa atau staff.',
            'status.in' => 'Status user tidak valid.',
        ];
    }
}
