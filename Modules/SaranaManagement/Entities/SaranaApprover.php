<?php

namespace Modules\SaranaManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class SaranaApprover extends Model
{
    use HasFactory;

    protected $table = 'sarana_approvers';

    protected $fillable = [
        'sarana_id',
        'approver_id',
        'approval_level',
        'is_active',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Sarana.
     */
    public function sarana()
    {
        return $this->belongsTo(Sarana::class, 'sarana_id');
    }

    /**
     * Relasi ke User sebagai approver.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scope: hanya yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter per sarana.
     */
    public function scopeForSarana($query, $saranaId)
    {
        return $query->where('sarana_id', $saranaId);
    }

    /**
     * Scope: filter per level approval.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    protected static function newFactory()
    {
        return \Modules\SaranaManagement\Database\factories\SaranaApproverFactory::new();
    }
}
