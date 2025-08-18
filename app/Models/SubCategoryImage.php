<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCategoryImage extends Model
{
    protected $table = 'sub_category_images';

    protected $fillable = [
        'sub_category_id',
        'path',
        'alt',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    // URL publique si tu utilises le disk "public"
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        return asset('storage/' . ltrim($this->path, '/'));
    }
}
