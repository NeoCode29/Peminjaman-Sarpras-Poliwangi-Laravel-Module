<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'min:10', 'max:15'],
        ];

        // Add specific rules based on user type
        if ($user && $user->user_type === 'mahasiswa') {
            $rules['jurusan_id'] = ['required', 'integer', 'exists:jurusan,id'];
            $rules['prodi_id'] = ['required', 'integer', 'exists:prodi,id'];
        } elseif ($user && $user->user_type === 'staff') {
            $rules['unit_id'] = ['required', 'integer', 'exists:units,id'];
            $rules['position_id'] = ['required', 'integer', 'exists:positions,id'];
            
            // NIP unique check excluding current staff record
            if ($user->staffEmployee) {
                $rules['nip'] = ['nullable', 'string', 'max:50', Rule::unique('staff_employees', 'nip')->ignore($user->staffEmployee->id)];
            } else {
                $rules['nip'] = ['nullable', 'string', 'max:50', 'unique:staff_employees,nip'];
            }
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama harus diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.required' => 'Nomor handphone harus diisi.',
            'phone.min' => 'Nomor handphone minimal 10 digit.',
            'phone.max' => 'Nomor handphone maksimal 15 digit.',
            'jurusan_id.required' => 'Jurusan harus dipilih.',
            'jurusan_id.exists' => 'Jurusan tidak valid.',
            'prodi_id.required' => 'Program studi harus dipilih.',
            'prodi_id.exists' => 'Program studi tidak valid.',
            'unit_id.required' => 'Unit harus dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',
            'position_id.required' => 'Posisi harus dipilih.',
            'position_id.exists' => 'Posisi tidak valid.',
            'nip.unique' => 'NIP sudah digunakan.',
            'nip.max' => 'NIP maksimal 50 karakter.',
        ];
    }
}
