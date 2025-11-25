<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileSetupRequest extends FormRequest
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

        $rules = [];

        // Phone is required if not already filled (SSO users might not have phone)
        if ($user && empty($user->phone)) {
            $rules['phone'] = ['required', 'string', 'min:10', 'max:15', 'regex:/^[0-9]+$/'];
        }

        // Add specific rules based on user type
        if ($user && $user->user_type === 'mahasiswa') {
            // NIM is required if username is not a valid NIM format (for manual registration)
            // Valid NIM format: 12 digits
            if (!preg_match('/^\d{12}$/', $user->username)) {
                $rules['nim'] = ['required', 'string', 'size:12', 'regex:/^\d{12}$/', 'unique:students,nim'];
            }
            
            $rules['jurusan_id'] = ['required', 'integer', 'exists:jurusan,id'];
            $rules['prodi_id'] = ['required', 'integer', 'exists:prodi,id'];
        } elseif ($user && $user->user_type === 'staff') {
            $rules['unit_id'] = ['required', 'integer', 'exists:units,id'];
            $rules['position_id'] = ['required', 'integer', 'exists:positions,id'];
            $rules['nip'] = ['nullable', 'string', 'max:50', 'unique:staff_employees,nip'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Nomor handphone harus diisi.',
            'phone.min' => 'Nomor handphone minimal 10 digit.',
            'phone.max' => 'Nomor handphone maksimal 15 digit.',
            'phone.regex' => 'Nomor handphone hanya boleh berisi angka.',
            'nim.required' => 'NIM harus diisi.',
            'nim.size' => 'NIM harus 12 digit.',
            'nim.regex' => 'NIM hanya boleh berisi angka.',
            'nim.unique' => 'NIM sudah digunakan.',
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize phone number: remove all non-numeric characters
        if ($this->has('phone') && !empty($this->phone)) {
            $this->merge([
                'phone' => preg_replace('/[^0-9]/', '', $this->phone),
            ]);
        }

        // Sanitize NIM: remove all non-numeric characters
        if ($this->has('nim') && !empty($this->nim)) {
            $this->merge([
                'nim' => preg_replace('/[^0-9]/', '', $this->nim),
            ]);
        }
    }
}
