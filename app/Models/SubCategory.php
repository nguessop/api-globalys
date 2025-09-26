<?php

namespace App\Models;

use App\Models\Meeting;
use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /* ====================================================================== */
    /* RELATIONS                                                              */
    /* ====================================================================== */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Offres publiées dans cette sous-catégorie */
    public function serviceOfferings(): HasMany
    {
        return $this->hasMany(ServiceOffering::class, 'sub_category_id');
    }

    /** Rendez-vous liés à cette sous-catégorie (service) */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'sub_category_id');
    }

    /** Meetings par statut (proposed|scheduled|cancelled|completed) */
    public function meetingsByStatus(string $status): HasMany
    {
        return $this->meetings()->where('status', $status);
    }

    /** Alias pratique pour les meetings planifiés */
    public function scheduledMeetings(): HasMany
    {
        return $this->meetingsByStatus('scheduled');
    }

    /** Images */
    public function images(): HasMany
    {
        return $this->hasMany(SubCategoryImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(SubCategoryImage::class)->where('is_primary', true);
    }

    /** Contrats rattachés à cette sous-catégorie */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'sub_category_id');
    }

    /** Contrats par statut (draft|sent|partially_signed|signed|cancelled|expired) */
    public function contractsByStatus(string $status): HasMany
    {
        return $this->contracts()->where('status', $status);
    }

    /** Templates de contrat associés (portée métier) */
    public function contractTemplates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class, 'sub_category_id');
    }

    /** Templates actifs & effectifs maintenant */
    public function activeContractTemplates(): HasMany
    {
        return $this->contractTemplates()
            ->active()
            ->effectiveNow();
    }

    /**
     * Récupère le “meilleur” template applicable :
     * - filtré par locale si fournie
     * - visible pour l’audience ('provider'|'client'|'both')
     * - actif & effectif
     * - version la plus élevée
     */
    public function bestContractTemplate(?string $locale = null, string $audience = 'both'): ?ContractTemplate
    {
        $query = $this->contractTemplates()
            ->active()
            ->effectiveNow()
            ->visibleTo($audience);

        if ($locale) {
            $query->forLocale($locale);
        }

        return $query
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    /* ====================================================================== */
    /* SCOPES                                                                 */
    /* ====================================================================== */

    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /* ====================================================================== */
    /* ROUTING                                                                */
    /* ====================================================================== */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }

    /* ====================================================================== */
    /* AUTO-SLUG                                                              */
    /* ====================================================================== */

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->slug = self::uniqueSlug($model->slug ?: $model->name);
        });

        static::updating(function (self $model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = self::uniqueSlug($model->name, $model->id);
            } elseif ($model->isDirty('slug')) {
                $model->slug = self::uniqueSlug($model->slug, $model->id);
            }
        });
    }

    /**
     * Génère un slug unique globalement.
     */
    protected static function uniqueSlug(?string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $base);
        $slug = $base ?: Str::random(8);
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
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
        $base = Str::slug($value ?: ($this->attributes['name'] ?? $this->name ?? ''));

        if (!$base) {
            $this->attributes['slug'] = Str::random(8);
            return;
        }

        $ignoreId = $this->exists ? $this->getKey() : null;

        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        $this->attributes['slug'] = $slug;
    }

    /* ====================================================================== */
    /* ACCESSORS                                                               */
    /* ====================================================================== */

    protected $appends = ['primary_image_url'];

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return optional($this->primaryImage)->url;
    }
}
