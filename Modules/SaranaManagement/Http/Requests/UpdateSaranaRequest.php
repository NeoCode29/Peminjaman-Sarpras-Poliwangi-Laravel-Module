<?php

namespace Modules\SaranaManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaranaRequest extends FormRequest
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
        $saranaId = $this->route('sarana')->id;

        return [
            'kode_sarana' => ['nullable', 'string', 'max:255', 'unique:saranas,kode_sarana,' . $saranaId],
            'nama' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', 'exists:kategori_saranas,id'],
            'merk' => ['nullable', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'kondisi' => ['required', 'in:baik,rusak_ringan,rusak_berat,dalam_perbaikan'],
            'status_ketersediaan' => ['required', 'in:tersedia,dipinjam,dalam_perbaikan,tidak_tersedia'],
            'type' => ['required', 'in:pooled,serialized'],
            'jumlah_total' => ['required', 'integer', 'min:1'],
            'jumlah_tersedia' => ['nullable', 'integer', 'min:0'],
            'jumlah_rusak' => ['nullable', 'integer', 'min:0'],
            'jumlah_maintenance' => ['nullable', 'integer', 'min:0'],
            'jumlah_hilang' => ['nullable', 'integer', 'min:0'],
            'tahun_perolehan' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'nilai_perolehan' => ['nullable', 'numeric', 'min:0'],
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama sarana harus diisi.',
            'kategori_id.required' => 'Kategori harus dipilih.',
            'kategori_id.exists' => 'Kategori tidak valid.',
            'kondisi.required' => 'Kondisi sarana harus dipilih.',
            'kondisi.in' => 'Kondisi sarana tidak valid.',
            'status_ketersediaan.required' => 'Status ketersediaan harus dipilih.',
            'status_ketersediaan.in' => 'Status ketersediaan tidak valid.',
            'type.required' => 'Tipe sarana harus dipilih.',
            'type.in' => 'Tipe sarana tidak valid.',
            'jumlah_total.required' => 'Jumlah total harus diisi.',
            'jumlah_total.min' => 'Jumlah total minimal 1.',
            'tahun_perolehan.min' => 'Tahun perolehan tidak valid.',
            'tahun_perolehan.max' => 'Tahun perolehan tidak valid.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
