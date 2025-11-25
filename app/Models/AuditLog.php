<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'performed_by',
        'performed_by_type',
        'context',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];
}
