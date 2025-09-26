<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContractTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contract_templates';

    protected $fillable = [
        'created_by',
        'sub_category_id',
        'title',
        'code',
        'version',
        'locale',
        'description',
        'body',
        'variables',
        'visibility',
        'require_provider_signature',
        'require_client_signature',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'variables'                     => 'array',
        'require_provider_signature'    => 'boolean',
        'require_client_signature'      => 'boolean',
        'is_active'                     => 'boolean',
        'effective_from'                => 'date',
        'effective_to'                  => 'date',
    ];

    /* ----------------------------------------------------------------------
     | Relations
     |---------------------------------------------------------------------- */

    /** Auteur/créateur de la template (optionnel) */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Portée métier (optionnelle) par sous-catégorie */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /** Contrats générés depuis cette template */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /* ----------------------------------------------------------------------
     | Scopes utiles
     |---------------------------------------------------------------------- */

    /** Actives (is_active = true) */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Valides à la date courante (entre effective_from et effective_to si définies) */
    public function scopeEffectiveNow($q)
    {
        $today = now()->toDateString();

        return $q->where(function ($w) use ($today) {
            $w->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
        })->where(function ($w) use ($today) {
            $w->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
        });
    }

    /** Pour une locale donnée (fr, en, fr_FR…) */
    public function scopeForLocale($q, ?string $locale)
    {
        return $locale ? $q->where('locale', $locale) : $q;
    }

    /** Pour une sous-catégorie donnée (ou null = globales) */
    public function scopeForSubCategory($q, $subCategoryId)
    {
        return $q->where(function ($w) use ($subCategoryId) {
            $w->whereNull('sub_category_id');
            if ($subCategoryId) {
                $w->orWhere('sub_category_id', $subCategoryId);
            }
        });
    }

    /** Visibilité côté consommateur (provider|client|both) */
    public function scopeVisibleTo($q, string $audience)
    {
        if ($audience === 'provider') {
            return $q->whereIn('visibility', ['provider_only', 'both']);
        }
        if ($audience === 'client') {
            return $q->whereIn('visibility', ['client_only', 'both']);
        }
        return $q; // autre cas: pas de filtre
    }

    /* ----------------------------------------------------------------------
     | Helpers / Accessors
     |---------------------------------------------------------------------- */

    /** Est-elle actuellement effective (date) ET active ? */
    public function isCurrentlyEffective(): bool
    {
        $today = now()->startOfDay();
        $fromOk = !$this->effective_from || $this->effective_from->startOfDay()->lte($today);
        $toOk   = !$this->effective_to   || $this->effective_to->endOfDay()->gte($today);

        return (bool) ($this->is_active && $fromOk && $toOk);
    }

    public function isProviderVisible(): bool
    {
        return in_array($this->visibility, ['provider_only', 'both'], true);
    }

    public function isClientVisible(): bool
    {
        return in_array($this->visibility, ['client_only', 'both'], true);
    }

    /** Récupère le schéma des variables (array) */
    public function variableSchema(): array
    {
        return $this->variables ?? [];
    }

    /* ----------------------------------------------------------------------
     | Events (slug/code auto, normalisation)
     |---------------------------------------------------------------------- */

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Si le code est vide, on le déduit du titre
            if (empty($model->code) && !empty($model->title)) {
                $model->code = Str::slug($model->title, '_'); // ex: "prestation_standard"
            }

            // Normalise le code quoi qu'il arrive
            if (!empty($model->code)) {
                $model->code = Str::slug($model->code, '_');
            }
        });

        static::updating(function (self $model) {
            if ($model->isDirty('code') && !empty($model->code)) {
                $model->code = Str::slug($model->code, '_');
            }
        });
    }
}
