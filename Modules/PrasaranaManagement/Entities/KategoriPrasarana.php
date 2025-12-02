<?php

namespace Modules\PrasaranaManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPrasarana extends Model
{
    use HasFactory;

    protected $table = 'kategori_prasarana';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prasarana(): HasMany
    {
        return $this->hasMany(Prasarana::class, 'kategori_id');
    }
}
