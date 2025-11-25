<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'user_type' => ['required', 'in:mahasiswa,staff'],
            'phone' => ['required', 'string', 'min:10', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap harus diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'username.required' => 'Username harus diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan underscore.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password harus diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'password_confirmation.required' => 'Konfirmasi password harus diisi.',
            'user_type.required' => 'Tipe pengguna harus dipilih.',
            'user_type.in' => 'Tipe pengguna tidak valid.',
            'phone.required' => 'Nomor handphone harus diisi.',
            'phone.min' => 'Nomor handphone minimal 10 digit.',
            'phone.max' => 'Nomor handphone maksimal 15 digit.',
            'phone.regex' => 'Format nomor handphone tidak valid.',
        ];
    }

    public function sanitized(): array
    {
        $data = $this->validated();

        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));
        $data['username'] = strtolower(trim($data['username']));
        $data['phone'] = preg_replace('/[^0-9]/', '', $data['phone']);

        return $data;
    }
}
