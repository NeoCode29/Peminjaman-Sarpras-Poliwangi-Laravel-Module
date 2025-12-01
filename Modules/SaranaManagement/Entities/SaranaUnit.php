<?php

namespace Modules\SaranaManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaranaUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'sarana_id',
        'unit_code',
        'unit_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sarana()
    {
        return $this->belongsTo(Sarana::class, 'sarana_id');
    }
}
