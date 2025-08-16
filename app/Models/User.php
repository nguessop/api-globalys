<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'phone',
        'preferred_language', 'country', 'account_type', 'role_id',
        'subscription_id', // FK vers subscriptions.id

        // Entreprise
        'company_name', 'sector', 'tax_number', 'website', 'company_logo',
        'company_description', 'company_address', 'company_city',
        'company_size', 'preferred_contact_method',

        // Particulier
        'gender', 'birthdate', 'job', 'personal_address',
        'user_type', 'profile_photo',

        // RGPD
        'accepts_terms', 'wants_newsletter',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'accepts_terms'     => 'boolean',
        'wants_newsletter'  => 'boolean',
        'email_verified_at' => 'datetime',
        'birthdate'         => 'date',
        'account_type'      => 'string', // 'entreprise' | 'particulier'
        'user_type'         => 'string', // 'client' | 'prestataire' | null
    ];

    /* =========================
     |        RELATIONS
     ========================= */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Historique des abonnements */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** Abonnement actif par statut */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE);
    }

    /** Abonnement courant pointé par users.subscription_id */
    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /** Offres publiées par ce user (prestataire/entreprise) */
    public function serviceOfferings(): HasMany
    {
        return $this->hasMany(ServiceOffering::class, 'provider_id');
    }

    /** Réservations faites par l'utilisateur (client) */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    /** Réservations reçues par l'utilisateur (prestataire/entreprise) */
    public function receivedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    /** Paiements effectués par l'utilisateur (client) */
    public function paymentsMade(): HasMany
    {
        return $this->hasMany(Payment::class, 'client_id');
    }

    /** Paiements reçus par l'utilisateur (prestataire/entreprise) */
    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'provider_id');
    }

    /* =========================
     |           JWT
     ========================= */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /* =========================
     |       HELPERS BUSINESS
     ========================= */

    /**
     * Abonnement prioritaire :
     * 1) celui pointé par users.subscription_id
     * 2) sinon l'abonnement actif par statut
     */
    public function subscriptionOrActive(): ?Subscription
    {
        if ($this->relationLoaded('currentSubscription') && $this->currentSubscription) {
            return $this->currentSubscription;
        }
        if ($this->subscription_id) {
            return Subscription::find($this->subscription_id);
        }
        return $this->activeSubscription()->first();
    }

    /** Taux de commission (0 si aucun abonnement valide) */
    public function commissionRate(): float
    {
        $sub = $this->subscriptionOrActive();
        return (float) (isset($sub) ? $sub->commission_rate : 0.0);
    }

    /** Calcule la commission via l’abonnement courant/actif */
    public function computeCommission(float $amount): float
    {
        $sub = $this->subscriptionOrActive();
        return $sub ? $sub->computeCommission($amount) : 0.0;
    }

    /** L’utilisateur a-t-il un abonnement valide ? */
    public function hasActiveSubscription(): bool
    {
        $sub = $this->subscriptionOrActive();
        return $sub ? $sub->isActive() : false;
    }

    /* =========================
     |          SCOPES
     ========================= */

    /** Recherche générique sur nom, email, téléphone */
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $term = trim($term);
        return $q->where(function ($qq) use ($term) {
            $qq->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /** Filtrer par rôle (name) */
    public function scopeWithRoleName($q, string $roleName)
    {
        return $q->whereHas('role', function ($r) use ($roleName) {
            $r->where('name', $roleName);
        });
    }

    /** Admins (exige un rôle "admin") */
    public function scopeAdmins($q)
    {
        return $q->withRoleName('admin');
    }

    /** Entreprises (compte entreprise) */
    public function scopeEntreprises($q)
    {
        return $q->where('account_type', 'entreprise');
    }

    /** Particuliers */
    public function scopeParticuliers($q)
    {
        return $q->where('account_type', 'particulier');
    }

    /** Prestataires (particulier avec user_type=prestataire OU compte entreprise) */
    public function scopePrestataires($q)
    {
        return $q->where(function ($w) {
            $w->where('user_type', 'prestataire')
                ->orWhere('account_type', 'entreprise');
        });
    }

    /** Clients (particulier avec user_type=client) */
    public function scopeClients($q)
    {
        return $q->where('user_type', 'client');
    }

    /** Dans un pays (code libre / label) */
    public function scopeInCountry($q, ?string $country)
    {
        if (!$country) return $q;
        return $q->where('country', $country);
    }

    /** Dans une ville (selon champs côté entreprise ou perso) */
    public function scopeInCity($q, ?string $city)
    {
        if (!$city) return $q;
        return $q->where(function ($w) use ($city) {
            $w->where('company_city', $city)
                ->orWhere('personal_address', 'like', "%{$city}%");
        });
    }

    /** Ayant un abonnement actif (par statut) */
    public function scopeWithActiveSubscription($q)
    {
        return $q->whereHas('subscriptions', function ($s) {
            $s->where('status', Subscription::STATUS_ACTIVE);
        });
    }

    /** Reliés à un abonnement courant via users.subscription_id (non null) */
    public function scopeWithCurrentSubscription($q)
    {
        return $q->whereNotNull('subscription_id');
    }

    /** Prestataires ayant au moins une offre active */
    public function scopeWithActiveOfferings($q)
    {
        return $q->whereHas('serviceOfferings', function ($s) {
            $s->where('status', ServiceOffering::STATUS_ACTIVE);
        });
    }

    /** Tri pratique par nom complet */
    public function scopeOrderByFullName($q, $direction = 'asc')
    {
        return $q->orderBy('last_name', $direction)->orderBy('first_name', $direction);
    }

    /* =========================
     |        MUTATORS
     ========================= */

    /** Hash auto du mot de passe si clair */
    public function setPasswordAttribute($value): void
    {
        if (!empty($value) && \Illuminate\Support\Str::startsWith($value, '$2y$') === false) {
            $this->attributes['password'] = bcrypt($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /** Nom complet virtuel */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Avis que l'utilisateur a RÉDIGÉS (en tant que client).
     */
    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Avis REÇUS par l'utilisateur (en tant que prestataire/entreprise).
     */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    /**
     * Moyenne des notes reçues (avis approuvés uniquement).
     * (Petit helper pratique pour l’UI)
     */
    public function averageRating(): float
    {
        $avg = $this->reviewsReceived()->approved()->avg('rating');
        return (float) ($avg ?: 0.0);
    }

    /**
     * Nombre d'avis reçus (avis approuvés uniquement).
     */
    public function reviewsCount(): int
    {
        return (int) $this->reviewsReceived()->approved()->count();
    }

    /** Tous les créneaux que cet utilisateur (prestataire/entreprise) a publiés */
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'provider_id');
    }

    /**
     * Créneaux via les offres de service du prestataire.
     * Utile si tu veux uniquement les slots liés à ses ServiceOfferings.
     */
    public function availabilitySlotsThroughOfferings(): HasManyThrough
    {
        return $this->hasManyThrough(
            AvailabilitySlot::class, // related
            ServiceOffering::class,  // through
            'provider_id',           // FK sur service_offerings pointant users.id
            'service_offering_id',   // FK sur availability_slots pointant service_offerings.id
            'id',                    // PK local users
            'id'                     // PK local service_offerings
        );
    }

    // app/Models/User.php

    public function resolveRouteBinding($value, $field = null)
    {
        // Relations utiles chargées d’emblée
        $with = ['role', 'currentSubscription', 'bookings', 'receivedBookings'];

        $query = $this->newQuery()->with($with);

        // Si {user} est numérique -> on traite comme un ID
        if (is_numeric($value)) {
            return $query->where('id', (int) $value)->firstOrFail();
        }

        // Sinon on tente par email (utile pour /api/users/john@doe.com)
        return $query->where('email', $value)->firstOrFail();
    }
}
