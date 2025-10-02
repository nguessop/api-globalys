<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'slug',
        'name',
        'icon',
        'color_class',
        'description',
    ];

    protected $casts = [
        'parent_id'   => 'integer',
        'slug'        => 'string',
        'name'        => 'string',
        'icon'        => 'string',
        'color_class' => 'string',
        'description' => 'string',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /* ---------------- Relations ---------------- */

    // Relation hiérarchique : une catégorie peut avoir un parent
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relation hiérarchique : une catégorie peut avoir plusieurs enfants
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Une catégorie a plusieurs sous-catégories
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    // Une catégorie a plusieurs offres de service via ses sous-catégories
    public function serviceOfferings(): HasManyThrough
    {
        return $this->hasManyThrough(
            ServiceOffering::class,
            SubCategory::class,
            'category_id',     // clé étrangère sur sub_categories
            'sub_category_id', // clé étrangère sur service_offerings
            'id',              // PK sur categories
            'id'               // PK sur sub_categories
        );
    }

    /* ---------------- Scopes ---------------- */

    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeNameLike($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /* ---------------- Slugging ---------------- */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = static::makeUniqueSlug($model->name, $model->getKey());
            }
        });
    }

    protected static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }
}
