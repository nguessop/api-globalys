<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'icon',
        'color_class',
        'description',
    ];

    protected $casts = [
        'slug'        => 'string',
        'name'        => 'string',
        'icon'        => 'string',
        'color_class' => 'string',
        'description' => 'string',
    ];

    /* Relations */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function serviceOfferings(): HasManyThrough
    {
        return $this->hasManyThrough(
            ServiceOffering::class,
            SubCategory::class,
            'category_id',     // FK sur sub_categories pointant vers categories
            'sub_category_id', // FK sur service_offerings pointant vers sub_categories
            'id',              // PK local sur categories
            'id'               // PK local sur sub_categories
        );
    }

    /* Scopes */
    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeNameLike($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /* Bonus: routing par slug */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* Bonus: générer slug auto si absent */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = static::makeUniqueSlug($model->name, $model->id);
            }
        });
    }

    /**
     * Génère un slug unique en base: "beaute-bien-etre", "beaute-bien-etre-2", etc.
     */
    protected static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'-'.$i;
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
