<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubCategory extends Model
{
    use HasFactory;

    protected $table = 'sub_categories';

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'icon',
        'providers_count',
        'average_price',
        'description',
    ];

    protected $casts = [
        'category_id'     => 'integer',
        'providers_count' => 'integer',
    ];

    /* RELATIONS */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function serviceOfferings(): HasMany
    {
        return $this->hasMany(ServiceOffering::class, 'sub_category_id');
    }

    /* SCOPES */
    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /* ROUTING */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* AUTO-SLUG, avec unicité GLOBALE */
    protected static function booted(): void
    {
        // Création (avant INSERT)
        static::creating(function (self $model) {
            $model->slug = self::uniqueSlug($model->slug ?: $model->name);
        });

        // Mise à jour : si tu veux régénérer le slug quand le name change
        static::updating(function (self $model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = self::uniqueSlug($model->name, $model->id);
            } elseif ($model->isDirty('slug')) {
                // Même si on te passe un slug à la main, on l'unique-ifie
                $model->slug = self::uniqueSlug($model->slug, $model->id);
            }
        });
    }

    /**
     * Génère un slug unique globalement (car la DB impose unique(slug)).
     * Si $ignoreId est fourni, on l’exclut (cas UPDATE).
     */
    protected static function uniqueSlug(?string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $base);
        $slug = $base ?: Str::random(8);
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function setSlugAttribute($value): void
    {
        // Base: slug fourni OU name
        $base = \Illuminate\Support\Str::slug($value ?: ($this->attributes['name'] ?? $this->name ?? ''));

        // Si on n’a toujours rien, fallback aléatoire
        if (!$base) {
            $this->attributes['slug'] = \Illuminate\Support\Str::random(8);
            return;
        }

        // Si on est en update, exclure l’ID courant
        $ignoreId = $this->exists ? $this->getKey() : null;

        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn($q) => $q->whereKey('!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        $this->attributes['slug'] = $slug;
    }


    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }

}
