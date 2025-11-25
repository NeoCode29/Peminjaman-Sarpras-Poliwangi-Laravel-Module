<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusan';

    protected $fillable = [
        'nama_jurusan',
        'deskripsi',
    ];

    public function prodis(): HasMany
    {
        return $this->hasMany(Prodi::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nama_jurusan', 'like', "%{$keyword}%")
            ->orWhere('deskripsi', 'like', "%{$keyword}%");
    }
}
