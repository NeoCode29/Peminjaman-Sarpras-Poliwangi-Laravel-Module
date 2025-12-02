<?php

namespace Modules\PrasaranaManagement\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrasaranaApprover extends Model
{
    use HasFactory;

    protected $table = 'prasarana_approvers';

    protected $fillable = [
        'prasarana_id',
        'approver_id',
        'approval_level',
        'is_active',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function prasarana()
    {
        return $this->belongsTo(Prasarana::class, 'prasarana_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPrasarana($query, int $prasaranaId)
    {
        return $query->where('prasarana_id', $prasaranaId);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('approval_level', $level);
    }
}
