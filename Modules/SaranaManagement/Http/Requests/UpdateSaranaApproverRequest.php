<?php

namespace Modules\SaranaManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaranaApproverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via policy in controller
    }

    public function rules(): array
    {
        return [
            'approval_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
