<?php

namespace Modules\PeminjamanManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Delegate to policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'approval_type' => ['required', Rule::in(['global', 'sarana', 'prasarana'])],
            'sarana_id' => ['required_if:approval_type,sarana', 'nullable', 'exists:saranas,id'],
            'prasarana_id' => ['required_if:approval_type,prasarana', 'nullable', 'exists:prasarana,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib dipilih.',
            'action.in' => 'Aksi tidak valid.',
            'approval_type.required' => 'Tipe approval wajib dipilih.',
            'approval_type.in' => 'Tipe approval tidak valid.',
            'sarana_id.required_if' => 'ID sarana wajib diisi untuk approval sarana.',
            'prasarana_id.required_if' => 'ID prasarana wajib diisi untuk approval prasarana.',
            'reason.required_if' => 'Alasan wajib diisi untuk penolakan.',
        ];
    }
}
