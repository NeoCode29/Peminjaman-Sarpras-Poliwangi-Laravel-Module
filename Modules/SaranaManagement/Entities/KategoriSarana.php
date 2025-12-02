<?php

namespace Modules\SaranaManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KategoriSarana extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
    ];
    
    /**
     * Get all saranas for this kategori
     */
    public function saranas()
    {
        return $this->hasMany(Sarana::class, 'kategori_id');
    }
    
    /**
     * Scope: Search by name or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('deskripsi', 'like', "%{$search}%");
        });
    }
    
    protected static function newFactory()
    {
        return \Modules\SaranaManagement\Database\factories\KategoriSaranaFactory::new();
    }
}
