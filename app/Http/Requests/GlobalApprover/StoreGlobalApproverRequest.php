<?php

namespace App\Http\Requests\GlobalApprover;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGlobalApproverRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by GlobalApproverPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'approval_level' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User wajib dipilih.',
            'user_id.exists' => 'User tidak ditemukan.',
            'approval_level.required' => 'Level approval wajib dipilih.',
            'approval_level.min' => 'Level approval minimal 1.',
            'approval_level.max' => 'Level approval maksimal 10.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert is_active checkbox to boolean
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        } else {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}
