<?php

namespace Modules\PrasaranaManagement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrasaranaImage extends Model
{
    use HasFactory;

    protected $table = 'prasarana_images';

    protected $fillable = [
        'prasarana_id',
        'image_url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function prasarana(): BelongsTo
    {
        return $this->belongsTo(Prasarana::class, 'prasarana_id');
    }
}
