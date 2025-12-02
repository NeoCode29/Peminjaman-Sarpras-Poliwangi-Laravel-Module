<?php

namespace Modules\MarkingManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarkingRequest extends FormRequest
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
        // UKM wajib hanya untuk mahasiswa
        $ukmRule = $this->user()->isStudent() 
            ? ['required', 'exists:ukm,id'] 
            : ['nullable', 'exists:ukm,id'];

        return [
            'event_name' => ['required', 'string', 'max:255'],
            'ukm_id' => $ukmRule,
            'prasarana_id' => ['nullable', 'exists:prasarana,id'],
            'lokasi_custom' => ['nullable', 'string', 'max:255'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'jumlah_peserta' => ['nullable', 'integer', 'min:1'],
            'planned_submit_by' => ['nullable', 'date', 'after:now', 'before:end_datetime'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event_name.required' => 'Nama acara wajib diisi.',
            'event_name.max' => 'Nama acara maksimal 255 karakter.',
            'ukm_id.required' => 'UKM/Organisasi wajib dipilih untuk mahasiswa.',
            'ukm_id.exists' => 'UKM/Organisasi tidak valid.',
            'prasarana_id.exists' => 'Prasarana tidak valid.',
            'lokasi_custom.max' => 'Nama lokasi maksimal 255 karakter.',
            'start_datetime.required' => 'Waktu mulai wajib diisi.',
            'start_datetime.date' => 'Format waktu mulai tidak valid.',
            'start_datetime.after' => 'Waktu mulai harus setelah waktu sekarang.',
            'end_datetime.required' => 'Waktu selesai wajib diisi.',
            'end_datetime.date' => 'Format waktu selesai tidak valid.',
            'end_datetime.after' => 'Waktu selesai harus setelah waktu mulai.',
            'jumlah_peserta.integer' => 'Jumlah peserta harus berupa angka.',
            'jumlah_peserta.min' => 'Jumlah peserta minimal 1 orang.',
            'planned_submit_by.date' => 'Format tanggal rencana submit tidak valid.',
            'planned_submit_by.after' => 'Tanggal rencana submit harus setelah waktu sekarang.',
            'planned_submit_by.before' => 'Tanggal rencana submit harus sebelum waktu selesai acara.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that either prasarana_id or lokasi_custom is provided
            if (empty($this->prasarana_id) && empty($this->lokasi_custom)) {
                $validator->errors()->add('prasarana_id', 'Pilih prasarana atau isi lokasi custom.');
                $validator->errors()->add('lokasi_custom', 'Pilih prasarana atau isi lokasi custom.');
            }
        });
    }
}
