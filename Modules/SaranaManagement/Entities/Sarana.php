<?php

namespace Modules\SaranaManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sarana extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_sarana',
        'nama',
        'kategori_id',
        'merk',
        'spesifikasi',
        'kondisi',
        'status_ketersediaan',
        'type',
        'jumlah_total',
        'jumlah_tersedia',
        'jumlah_rusak',
        'jumlah_maintenance',
        'jumlah_hilang',
        'tahun_perolehan',
        'nilai_perolehan',
        'lokasi_penyimpanan',
        'foto',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_total' => 'integer',
        'jumlah_tersedia' => 'integer',
        'jumlah_rusak' => 'integer',
        'jumlah_maintenance' => 'integer',
        'jumlah_hilang' => 'integer',
        'tahun_perolehan' => 'integer',
        'nilai_perolehan' => 'decimal:2',
    ];

    protected $appends = ['foto_url'];

    /**
     * Get kategori for this sarana
     */
    public function kategori()
    {
        return $this->belongsTo(KategoriSarana::class, 'kategori_id');
    }

    /**
     * Relationship: units (for serialized sarana)
     */
    public function units()
    {
        return $this->hasMany(SaranaUnit::class, 'sarana_id');
    }

    /**
     * Relationship: approvers (approval spesifik per sarana)
     */
    public function approvers()
    {
        return $this->hasMany(SaranaApprover::class, 'sarana_id');
    }

    /**
     * Scope: Filter by kondisi
     */
    public function scopeKondisi($query, $kondisi)
    {
        return $query->where('kondisi', $kondisi);
    }

    /**
     * Scope: Filter by status ketersediaan
     */
    public function scopeStatusKetersediaan($query, $status)
    {
        return $query->where('status_ketersediaan', $status);
    }

    /**
     * Scope: Only tersedia
     */
    public function scopeTersedia($query)
    {
        return $query->where('status_ketersediaan', 'tersedia');
    }

    /**
     * Scope: serialized type
     */
    public function scopeSerialized($query)
    {
        return $query->where('type', 'serialized');
    }

    /**
     * Scope: pooled type
     */
    public function scopePooled($query)
    {
        return $query->where('type', 'pooled');
    }

    /**
     * Scope: Search by kode, nama, merk
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('kode_sarana', 'like', "%{$search}%")
              ->orWhere('nama', 'like', "%{$search}%")
              ->orWhere('merk', 'like', "%{$search}%")
              ->orWhere('lokasi_penyimpanan', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by kategori
     */
    public function scopeByKategori($query, $kategoriId)
    {
        return $query->where('kategori_id', $kategoriId);
    }

    /**
     * Hitung statistik untuk sarana serialized berdasarkan sarana_units
     */
    public function calculateSerializedStats(): void
    {
        if ($this->type !== 'serialized') {
            return;
        }

        $this->jumlah_tersedia = $this->units()->where('unit_status', 'tersedia')->count();
        $this->jumlah_rusak = $this->units()->where('unit_status', 'rusak')->count();
        $this->jumlah_maintenance = $this->units()->where('unit_status', 'maintenance')->count();
        $this->jumlah_hilang = $this->units()->where('unit_status', 'hilang')->count();

        $this->save();
    }

    /**
     * Update statistik sarana (saat ini hanya untuk serialized)
     */
    public function updateStats(): void
    {
        if ($this->type === 'serialized') {
            $this->calculateSerializedStats();
        }
    }

    /**
     * Accessor: sisa unit yang belum terdaftar (serialized)
     */
    public function getRemainingUnitsAttribute(): int
    {
        if ($this->type !== 'serialized') {
            return 0;
        }

        $currentUnits = $this->units()->count();

        return max(0, (int) $this->jumlah_total - (int) $currentUnits);
    }

    /**
     * Accessor: Get full URL for foto
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        return asset('storage/' . $this->foto);
    }
    
    protected static function newFactory()
    {
        return \Modules\SaranaManagement\Database\factories\SaranaFactory::new();
    }
}
