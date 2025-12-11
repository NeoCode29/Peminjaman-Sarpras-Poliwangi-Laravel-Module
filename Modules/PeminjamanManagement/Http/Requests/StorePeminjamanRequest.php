<?php

namespace Modules\PeminjamanManagement\Http\Requests;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
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
        $rules = [
            'event_name' => ['required', 'string', 'max:255'],
            'loan_type' => ['required', Rule::in(['sarana', 'prasarana', 'both'])],
            'prasarana_id' => ['nullable', 'exists:prasarana,id'],
            'lokasi_custom' => ['nullable', 'string', 'max:150'],
            'jumlah_peserta' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'surat' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'sarana_items' => ['nullable', 'array'],
            'sarana_items.*.sarana_id' => ['required_with:sarana_items', 'distinct', 'exists:saranas,id'],
            'sarana_items.*.qty_requested' => ['required_with:sarana_items', 'integer', 'min:1'],
        ];

        // UKM required for mahasiswa
        if (Auth::user()->user_type === 'mahasiswa') {
            $rules['ukm_id'] = ['required', 'exists:ukm,id'];
        } else {
            $rules['ukm_id'] = ['nullable', 'exists:ukm,id'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLoanTypeSelection($validator);
            $this->validateDuration($validator);
            $this->validateDateTimeOrder($validator);
            $this->validatePrasaranaCapacity($validator);
        });
    }

    /**
     * Validate loan type selection.
     */
    protected function validateLoanTypeSelection($validator): void
    {
        $loanType = $this->input('loan_type');
        $prasaranaId = $this->input('prasarana_id');
        $lokasiCustom = $this->input('lokasi_custom');
        $saranaItems = $this->input('sarana_items', []);

        if (in_array($loanType, ['prasarana', 'both'], true)) {
            if (empty($prasaranaId) && empty($lokasiCustom)) {
                $validator->errors()->add('prasarana_id', 'Pilih prasarana atau isi lokasi custom.');
            }

            if (empty($this->input('jumlah_peserta'))) {
                $validator->errors()->add('jumlah_peserta', 'Jumlah peserta wajib diisi untuk peminjaman prasarana.');
            }
        }

        if (in_array($loanType, ['sarana', 'both'], true)) {
            if (empty($saranaItems)) {
                $validator->errors()->add('sarana_items', 'Pilih minimal satu sarana.');
            }
        }
    }

    /**
     * Ensure end datetime is not before start datetime.
     */
    protected function validateDateTimeOrder($validator): void
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        if ($startDate && $endDate && $startTime && $endTime) {
            $start = \Carbon\Carbon::parse("{$startDate} {$startTime}");
            $end = \Carbon\Carbon::parse("{$endDate} {$endTime}");

            if ($end->lt($start)) {
                $validator->errors()->add('end_time', 'Waktu selesai harus setelah waktu mulai.');
            }
        }
    }

    /**
     * Validate duration limit.
     */
    protected function validateDuration($validator): void
    {
        $maxDuration = SystemSetting::get('max_duration_days', 7);
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $duration = $start->diffInDays($end) + 1;

            if ($duration > $maxDuration) {
                $validator->errors()->add('end_date', "Durasi peminjaman tidak boleh lebih dari {$maxDuration} hari.");
            }
        }
    }

    /**
     * Validate prasarana capacity.
     */
    protected function validatePrasaranaCapacity($validator): void
    {
        $prasaranaId = $this->input('prasarana_id');
        $jumlahPeserta = $this->input('jumlah_peserta');

        if ($prasaranaId && $jumlahPeserta) {
            $prasarana = \Modules\PrasaranaManagement\Entities\Prasarana::find($prasaranaId);

            if ($prasarana && $prasarana->kapasitas !== null) {
                $kapasitas = (int) $prasarana->kapasitas;
                $peserta = (int) $jumlahPeserta;

                if ($kapasitas > 0 && $peserta > $kapasitas) {
                    $validator->errors()->add(
                        'jumlah_peserta',
                        "Jumlah peserta ({$peserta}) melebihi kapasitas prasarana {$prasarana->name} ({$kapasitas})."
                    );
                }
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event_name.required' => 'Nama kegiatan wajib diisi.',
            'loan_type.required' => 'Jenis peminjaman wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai minimal hari ini.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'surat.required' => 'Surat pengajuan wajib diunggah.',
            'surat.mimes' => 'Surat pengajuan harus berformat PDF, JPG, JPEG, atau PNG.',
            'surat.max' => 'Ukuran surat pengajuan maksimal 5MB.',
            'ukm_id.required' => 'UKM wajib dipilih untuk pengajuan oleh mahasiswa.',
            'sarana_items.*.sarana_id.required_with' => 'Pilih sarana yang akan dipinjam.',
            'sarana_items.*.sarana_id.distinct' => 'Sarana tidak boleh duplikat.',
            'sarana_items.*.qty_requested.required_with' => 'Jumlah sarana wajib diisi.',
            'sarana_items.*.qty_requested.min' => 'Jumlah sarana minimal 1.',
        ];
    }
}
