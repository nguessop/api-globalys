<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOffering extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_offerings';

    /* ============================
     |       MASS ASSIGNABLE
     ============================*/
    protected $fillable = [
        'sub_category_id',
        'provider_id',
        'title',
        'description',

        // Tarifs
        'price_amount',
        'price_unit',
        'currency',
        'tax_rate',
        'discount_amount',

        // Marketplace / E-commerce
        'stock_quantity',// pour produits ou offres limitées
        'is_limited_stock',   // service limité ou pas

        // Zone
        'city',
        'country',
        'address',
        'coverage_km',
        'on_site',
        'at_provider',
        'lat',
        'lng',

        // SLA / capacité
        'min_delay_hours',
        'max_delay_hours',
        'duration_minutes',
        'capacity',

        // Statut / publication
        'status',
        'published_at',
        'featured',
        'is_verified',
        'status_reason',

        // Dérivés / métriques
        'avg_rating',
        'ratings_count',
        'views_count',
        'bookings_count',
        'favorites_count',

        // Médias / extra
        'attachments',
        'metadata',
    ];

    /* Exposer automatiquement le label prix dans les réponses JSON */
    protected $appends = ['price_label'];

    /* ============================
     |           CASTS
     ============================*/
    protected $casts = [
        // numéraires
        'price_amount'     => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'discount_amount'  => 'decimal:2',

        // booleans
        'on_site'          => 'boolean',
        'at_provider'      => 'boolean',
        'featured'         => 'boolean',
        'is_verified'      => 'boolean',

        // entiers
        'coverage_km'      => 'integer',
        'min_delay_hours'  => 'integer',
        'max_delay_hours'  => 'integer',
        'duration_minutes' => 'integer',
        'capacity'         => 'integer',
        'ratings_count'    => 'integer',
        'views_count'      => 'integer',
        'bookings_count'   => 'integer',
        'favorites_count'  => 'integer',

        // décimaux géoloc
        'lat'              => 'decimal:7',
        'lng'              => 'decimal:7',

        // rating /5.00
        'avg_rating'       => 'decimal:2',

        // dates
        'published_at'     => 'datetime',

        // json
        'attachments'      => 'array',
        'metadata'         => 'array',
    ];

    /* ============================
     |         ENUMS
     ============================*/
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAUSED   = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const UNIT_HOUR    = 'hour';
    public const UNIT_SERVICE = 'service';
    public const UNIT_KM      = 'km';
    public const UNIT_COURSE  = 'course';
    public const UNIT_KG      = 'kg';
    public const UNIT_JOUR    = 'jour';

    /** Liste blanche des unités */
    public static $ALLOWED_UNITS = [
        self::UNIT_HOUR, self::UNIT_SERVICE, self::UNIT_KM,
        self::UNIT_COURSE, self::UNIT_KG, self::UNIT_JOUR,
    ];

    /* ============================
     |        RELATIONS
     ============================*/
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    // Décommente si tu as ces tables:
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_offering_id');
    }
    // public function availabilitySlots(): HasMany { return $this->hasMany(AvailabilitySlot::class); }
    // public function reviews(): HasMany { return $this->hasMany(Review::class); }

    /* ============================
     |          SCOPES
     ============================*/
    public function scopeActive($q)      { return $q->where('status', self::STATUS_ACTIVE); }
    public function scopeStatus($q, $s)  { return $q->where('status', $s); }
    public function scopeForProvider($q, $id)   { return $q->where('provider_id', (int)$id); }
    public function scopeForSubCategory($q, $id){ return $q->where('sub_category_id', (int)$id); }
    public function scopeInCity($q, $city)      { return $city ? $q->where('city', $city) : $q; }
    public function scopeVerified($q)    { return $q->where('is_verified', true); }
    public function scopeFeatured($q)    { return $q->where('featured', true); }
    public function scopePriceBetween($q, $min, $max)
    {
        if ($min !== null) { $q->where('price_amount', '>=', $min); }
        if ($max !== null) { $q->where('price_amount', '<=', $max); }
        return $q;
    }

    /* ============================
     |   ACCESSORS / HELPERS
     ============================*/
    public function getPriceLabelAttribute(): string
    {
        $cur = $this->currency ?: 'XAF';
        return number_format((float)$this->price_amount, 0, ',', ' ') . ' ' . $cur . ' / ' . $this->price_unit;
    }

    public function isBookable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /* ============================
     |    SAFETY / NORMALISATION
     ============================*/
    protected static function booted(): void
    {
        // Normalisations avant insert/update
        $normalize = function (self $m) {
            // Unité : valeur autorisée, sinon 'service'
            if (empty($m->price_unit) || !in_array($m->price_unit, self::$ALLOWED_UNITS, true)) {
                $m->price_unit = self::UNIT_SERVICE;
            }

            // Devise en uppercase, 3 lettres
            if (!empty($m->currency)) {
                $m->currency = strtoupper(substr($m->currency, 0, 3));
            } else {
                $m->currency = 'XAF';
            }

            // Valeurs minimales
            $m->capacity         = max(1, (int)($m->capacity ?: 1));
            $m->coverage_km      = $m->coverage_km !== null ? max(0, (int)$m->coverage_km) : null;
            $m->min_delay_hours  = $m->min_delay_hours !== null ? max(0, (int)$m->min_delay_hours) : null;
            $m->max_delay_hours  = $m->max_delay_hours !== null ? max(0, (int)$m->max_delay_hours) : null;
            $m->duration_minutes = $m->duration_minutes !== null ? max(0, (int)$m->duration_minutes) : null;

            // Montants non négatifs
            if ($m->price_amount !== null)   { $m->price_amount    = max(0, (float)$m->price_amount); }
            if ($m->tax_rate !== null)       { $m->tax_rate        = max(0, (float)$m->tax_rate); }
            if ($m->discount_amount !== null){ $m->discount_amount = max(0, (float)$m->discount_amount); }

            // Lat/Lng : clamp léger et arrondi (si fournis)
            if ($m->lat !== null) { $m->lat = max(-90, min(90, round((float)$m->lat, 7))); }
            if ($m->lng !== null) { $m->lng = max(-180, min(180, round((float)$m->lng, 7))); }
        };

        static::creating(function (self $model) use ($normalize) {
            $normalize($model);
            // Valeurs par défaut de statut si absent
            if (empty($model->status)) {
                $model->status = self::STATUS_DRAFT;
            }
            if ($model->featured === null)   { $model->featured = false; }
            if ($model->is_verified === null){ $model->is_verified = false; }
        });

        static::updating(function (self $model) use ($normalize) {
            $normalize($model);
        });
    }

    // Optionnel: barrière côté modèle (préférer Policy/FormRequest en prod)
    // protected static function boot()
    // {
    //     parent::boot();
    //     static::creating(function (self $m) {
    //         $u = auth()->user();
    //         if ($u && !in_array($u->account_type, ['prestataire','entreprise'], true)) {
    //             abort(403, 'Seuls les prestataires ou entreprises peuvent publier un service.');
    //         }
    //     });
    // }

    /**
     * Avis liés à cette offre de service.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'service_offering_id');
    }

    /**
     * Moyenne des notes pour ce service (avis approuvés).
     */
    public function averageRating(): float
    {
        $avg = $this->reviews()->approved()->avg('rating');
        return (float) ($avg ?: 0.0);
    }

    /**
     * Nombre d'avis pour ce service (avis approuvés).
     */
    public function reviewsCount(): int
    {
        return (int) $this->reviews()->approved()->count();
    }

    /** Créneaux de disponibilité rattachés à cette offre */
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'service_offering_id');
    }

    /** Prochain créneau disponible (helper pratique) */
    public function nextAvailableSlot()
    {
        return $this->availabilitySlots()
            ->available()
            ->where('end_at', '>=', now())
            ->orderBy('start_at', 'asc')
            ->first();
    }

    // Créneaux disponibles à l’intérieur d’un range
    public function scopeWithAvailableSlotsBetween($q, $from, $to)
    {
        return $q->whereHas('availabilitySlots', function ($qq) use ($from, $to) {
            if ($from) $qq->where('end_at', '>=', $from);
            if ($to)   $qq->where('start_at', '<=', $to);
            $qq->available();
        })->with(['availabilitySlots' => function ($qq) use ($from, $to) {
            if ($from) $qq->where('end_at', '>=', $from);
            if ($to)   $qq->where('start_at', '<=', $to);
            $qq->available()->orderBy('start_at');
        }]);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Category::class,
            SubCategory::class,
            'id',              // PK sur sub_categories
            'id',              // PK sur categories
            'sub_category_id', // FK local sur service_offerings
            'category_id'      // FK sur sub_categories
        );
    }

    public function scopeSearch($q, string $term)
    {
        return $q->where(function($qq) use ($term) {
            $qq->where('title', 'like', "%$term%")
                ->orWhere('description', 'like', "%$term%");
        });
    }

    public function isAvailable(): bool
    {
        if ($this->is_verified === false || $this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->is_limited_stock && $this->stock_quantity !== null) {
            return $this->stock_quantity > 0;
        }
        return true;
    }
}
