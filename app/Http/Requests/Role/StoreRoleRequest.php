<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by RolePolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:125',
                'unique:roles,name',
                'min:2',
            ],
            // Field berikut tidak disediakan di form create role saat ini,
            // sehingga dibuat opsional dan akan diisi default oleh service jika diperlukan.
            'display_name' => 'nullable|string|max:255|min:2',
            'description' => 'nullable|string|max:1000',
            'guard_name' => 'nullable|string|in:web,api',
            'category' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Nama role hanya boleh menggunakan huruf kecil dan underscore (_).',
            'name.min' => 'Nama role minimal 2 karakter.',
            'display_name.min' => 'Nama tampilan minimal 2 karakter.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'guard_name.in' => 'Guard name harus web atau api.',
            'permissions.*.exists' => 'Permission yang dipilih tidak valid.',
        ];
    }
}
