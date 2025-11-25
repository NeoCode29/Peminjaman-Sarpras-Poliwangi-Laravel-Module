<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by UserPolicy::block()
        return true;
    }

    public function rules(): array
    {
        return [
            'blocked_until' => ['nullable', 'date'],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
