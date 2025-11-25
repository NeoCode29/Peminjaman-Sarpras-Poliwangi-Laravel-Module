<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('system.settings') : false;
    }

    public function rules(): array
    {
        return [
            'settings' => ['sometimes', 'array'],
        ];
    }
}
