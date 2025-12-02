<?php

namespace Modules\PrasaranaManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKategoriPrasaranaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
