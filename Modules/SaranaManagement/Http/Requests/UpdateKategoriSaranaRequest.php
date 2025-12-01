<?php

namespace Modules\SaranaManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKategoriSaranaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Delegate to policy
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $kategoriId = $this->route('kategori_sarana')->id;

        return [
            'nama' => ['required', 'string', 'max:255', 'unique:kategori_saranas,nama,' . $kategoriId],
            'deskripsi' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori harus diisi.',
            'nama.unique' => 'Nama kategori sudah digunakan.',
        ];
    }
}
