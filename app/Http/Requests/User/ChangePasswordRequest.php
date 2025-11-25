<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Delegate to UserPolicy::changePassword()
        $target = $this->route('user');
        
        return $this->user()?->can('changePassword', $target) ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }
}
