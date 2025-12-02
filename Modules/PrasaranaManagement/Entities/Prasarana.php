<?php

namespace Modules\PrasaranaManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prasarana extends Model
{
    use HasFactory;

    protected $table = 'prasarana';

    protected $fillable = [
        'name',
        'kategori_id',
        'description',
        'lokasi',
        'kapasitas',
        'status',
        'created_by',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPrasarana::class, 'kategori_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PrasaranaImage::class)->orderBy('sort_order');
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(PrasaranaApprover::class, 'prasarana_id');
    }
}
