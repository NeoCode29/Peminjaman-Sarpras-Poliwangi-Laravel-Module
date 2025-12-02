<?php

namespace Modules\MarkingManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendMarkingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxDays = config('markingmanagement.max_extension_days', 7);
        
        return [
            'extension_days' => ['required', 'integer', 'min:1', "max:{$maxDays}"],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxDays = config('markingmanagement.max_extension_days', 7);
        
        return [
            'extension_days.required' => 'Jumlah hari perpanjangan wajib diisi.',
            'extension_days.integer' => 'Jumlah hari harus berupa angka.',
            'extension_days.min' => 'Perpanjangan minimal 1 hari.',
            'extension_days.max' => "Perpanjangan maksimal {$maxDays} hari.",
        ];
    }
}
