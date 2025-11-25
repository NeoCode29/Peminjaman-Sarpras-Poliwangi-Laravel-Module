<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    use HasFactory;

    protected $table = 'prodi';

    protected $fillable = [
        'nama_prodi',
        'jurusan_id',
        'jenjang',
        'deskripsi',
    ];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nama_prodi', 'like', "%{$keyword}%")
            ->orWhere('deskripsi', 'like', "%{$keyword}%");
    }

    public function scopeByJurusan($query, int $jurusanId)
    {
        return $query->where('jurusan_id', $jurusanId);
    }

    public function scopeByJenjang($query, string $jenjang)
    {
        return $query->where('jenjang', $jenjang);
    }
}
