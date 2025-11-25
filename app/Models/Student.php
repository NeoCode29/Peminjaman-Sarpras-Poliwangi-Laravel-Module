<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nim',
        'angkatan',
        'jurusan_id',
        'prodi_id',
        'semester',
        'status_mahasiswa',
    ];

    protected $casts = [
        'angkatan' => 'integer',
        'semester' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status_mahasiswa', $status);
    }

    public function scopeByAngkatan($query, int $angkatan)
    {
        return $query->where('angkatan', $angkatan);
    }

    public function scopeByJurusan($query, int $jurusanId)
    {
        return $query->where('jurusan_id', $jurusanId);
    }

    public function scopeByProdi($query, int $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nim', 'like', "%{$keyword}%")
            ->orWhereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%");
            });
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status_mahasiswa) {
            'aktif' => 'Aktif',
            'cuti' => 'Cuti',
            'dropout' => 'Drop Out',
            'lulus' => 'Lulus',
            default => $this->status_mahasiswa ?? '-',
        };
    }
}
