<?php

namespace Modules\SaranaManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaranaApproverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via policy in controller
    }

    public function rules(): array
    {
        return [
            'approver_id' => ['required', 'integer', 'exists:users,id'],
            'approval_level' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
