<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by PermissionPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:125',
                'unique:permissions,name',
                'regex:/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
            ],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'guard_name' => 'nullable|string|in:web,api',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Format permission harus domain.action (contoh: user.create).',
        ];
    }
}
