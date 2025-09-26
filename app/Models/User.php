<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\{ BelongsTo, HasMany, HasOne };
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'first_name','last_name','email','password','phone',
        'preferred_language','country','account_type','role_id',
        'subscription_id',
        'company_name','sector','tax_number','website','company_logo',
        'company_description','company_address','company_city',
        'company_size','preferred_contact_method',
        'gender','birthdate','job','personal_address',
        'user_type','profile_photo',
        'accepts_terms','wants_newsletter',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'accepts_terms' => 'boolean',
        'wants_newsletter' => 'boolean',
        'email_verified_at' => 'datetime',
        'birthdate' => 'date',
        'account_type' => 'string',
        'user_type' => 'string',
    ];

    /* =========================
     |        RELATIONS
     ========================= */

    public function role(): BelongsTo                 { return $this->belongsTo(Role::class); }
    public function subscriptions(): HasMany          { return $this->hasMany(Subscription::class); }
    public function activeSubscription(): HasOne      { return $this->hasOne(Subscription::class)->where('status', Subscription::STATUS_ACTIVE); }
    public function currentSubscription(): BelongsTo  { return $this->belongsTo(Subscription::class, 'subscription_id'); }

    public function serviceOfferings(): HasMany       { return $this->hasMany(ServiceOffering::class, 'provider_id'); }
    public function bookings(): HasMany               { return $this->hasMany(Booking::class, 'client_id'); }
    public function receivedBookings(): HasMany       { return $this->hasMany(Booking::class, 'provider_id'); }
    public function paymentsMade(): HasMany           { return $this->hasMany(Payment::class, 'client_id'); }
    public function paymentsReceived(): HasMany       { return $this->hasMany(Payment::class, 'provider_id'); }

    /* Meetings */
    public function meetingsAsProvider(): HasMany     { return $this->hasMany(Meeting::class, 'provider_id'); }
    public function meetingsAsClient(): HasMany       { return $this->hasMany(Meeting::class, 'client_id'); }

    public function meetingSlotsAsProvider(): HasManyThrough
    {
        return $this->hasManyThrough(MeetingSlot::class, Meeting::class, 'provider_id', 'meeting_id', 'id', 'id');
    }

    public function meetingSlotsAsClient(): HasManyThrough
    {
        return $this->hasManyThrough(MeetingSlot::class, Meeting::class, 'client_id', 'meeting_id', 'id', 'id');
    }

    public function meetingNotesAuthored(): HasMany   { return $this->hasMany(MeetingNote::class, 'author_id'); }

    /* Contracts */
    public function contractsAsProvider(): HasMany    { return $this->hasMany(Contract::class, 'provider_id'); }
    public function contractsAsClient(): HasMany      { return $this->hasMany(Contract::class, 'client_id'); }

    public function contractParties(): HasMany        { return $this->hasMany(ContractPartie::class, 'user_id'); }

    public function contractsViaParties(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_parties', 'user_id', 'contract_id')->withTimestamps();
    }

    /** Événements où l’utilisateur est l’acteur */
    public function contractEventsAsActor(): HasMany
    {
        return $this->hasMany(ContractEvent::class, 'actor_user_id');
    }

    /* Signatures (journal) faites par cet utilisateur */
    public function contractSignatures(): HasMany         { return $this->hasMany(ContractSignature::class, 'signer_user_id'); }
    public function pendingContractSignatures(): HasMany  { return $this->contractSignatures()->where('status', ContractSignature::STATUS_PENDING); }
    public function signedContractSignatures(): HasMany   { return $this->contractSignatures()->where('status', ContractSignature::STATUS_SIGNED); }
    public function declinedContractSignatures(): HasMany { return $this->contractSignatures()->where('status', ContractSignature::STATUS_DECLINED); }

    public function contractsAwaitingMySignature()
    {
        return Contract::query()
            ->whereIn('status', [Contract::STATUS_SENT, Contract::STATUS_PARTIALLY_SIGNED])
            ->whereHas('signatures', fn ($q) => $q->where('signer_user_id', $this->id)->where('status', ContractSignature::STATUS_PENDING));
    }

    public function nextPendingContractSignature(): ?ContractSignature
    {
        return $this->pendingContractSignatures()->orderBy('created_at')->first();
    }

    public function nextAwaitingSignatureContractViaParties(): ?Contract
    {
        return Contract::query()
            ->whereIn('status', [Contract::STATUS_SENT, Contract::STATUS_PARTIALLY_SIGNED])
            ->whereHas('parties', fn ($q) => $q->where('user_id', $this->id)->where('require_signature', true)->whereNull('signed_at'))
            ->orderBy('created_at')
            ->first();
    }

    public function nextAwaitingSignatureContractAsProvider(): ?Contract
    {
        return $this->contractsAsProvider()
            ->whereIn('status', [Contract::STATUS_SENT, Contract::STATUS_PARTIALLY_SIGNED])
            ->where(fn ($q) => $q->where('require_provider_signature', true)->whereNull('provider_signed_at'))
            ->orderBy('created_at')
            ->first();
    }

    public function nextAwaitingSignatureContractAsClient(): ?Contract
    {
        return $this->contractsAsClient()
            ->whereIn('status', [Contract::STATUS_SENT, Contract::STATUS_PARTIALLY_SIGNED])
            ->where(fn ($q) => $q->where('require_client_signature', true)->whereNull('client_signed_at'))
            ->orderBy('created_at')
            ->first();
    }

    /* =========================
     |           JWT
     ========================= */

    public function getJWTIdentifier()            { return $this->getKey(); }
    public function getJWTCustomClaims()          { return []; }

    /* =========================
     |       HELPERS BUSINESS
     ========================= */

    public function subscriptionOrActive(): ?Subscription
    {
        if ($this->relationLoaded('currentSubscription') && $this->currentSubscription) return $this->currentSubscription;
        if ($this->subscription_id) return Subscription::find($this->subscription_id);
        return $this->activeSubscription()->first();
    }

    public function commissionRate(): float
    {
        $sub = $this->subscriptionOrActive();
        return (float) ($sub->commission_rate ?? 0.0);
    }

    public function computeCommission(float $amount): float
    {
        $sub = $this->subscriptionOrActive();
        return $sub ? $sub->computeCommission($amount) : 0.0;
    }

    public function hasActiveSubscription(): bool
    {
        $sub = $this->subscriptionOrActive();
        return $sub ? $sub->isActive() : false;
    }

    public function nextScheduledMeetingAsProvider(): ?Meeting
    {
        return $this->meetingsAsProvider()
            ->where('status', 'scheduled')
            ->whereHas('selectedSlot', fn ($q) => $q->where('start_at', '>=', now()))
            ->with('selectedSlot')
            ->orderBy(
                MeetingSlot::query()->select('start_at')->whereColumn('meeting_slots.id', 'meetings.selected_slot_id')->limit(1)
            )->first();
    }

    public function nextScheduledMeetingAsClient(): ?Meeting
    {
        return $this->meetingsAsClient()
            ->where('status', 'scheduled')
            ->whereHas('selectedSlot', fn ($q) => $q->where('start_at', '>=', now()))
            ->with('selectedSlot')
            ->orderBy(
                MeetingSlot::query()->select('start_at')->whereColumn('meeting_slots.id', 'meetings.selected_slot_id')->limit(1)
            )->first();
    }

    /* =========================
     |          SCOPES
     ========================= */

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $term = trim($term);
        return $q->where(fn ($qq) => $qq->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%"));
    }

    public function scopeWithRoleName($q, string $roleName) { return $q->whereHas('role', fn ($r) => $r->where('name', $roleName)); }
    public function scopeAdmins($q)       { return $q->withRoleName('admin'); }
    public function scopeEntreprises($q)  { return $q->where('account_type', 'entreprise'); }
    public function scopeParticuliers($q) { return $q->where('account_type', 'particulier'); }
    public function scopePrestataires($q) { return $q->where(fn ($w) => $w->where('user_type', 'prestataire')->orWhere('account_type', 'entreprise')); }
    public function scopeClients($q)      { return $q->where('user_type', 'client'); }
    public function scopeInCountry($q, ?string $country) { return $country ? $q->where('country', $country) : $q; }
    public function scopeInCity($q, ?string $city)
    {
        if (!$city) return $q;
        return $q->where(fn ($w) => $w->where('company_city', $city)->orWhere('personal_address', 'like', "%{$city}%"));
    }
    public function scopeWithActiveSubscription($q) { return $q->whereHas('subscriptions', fn ($s) => $s->where('status', Subscription::STATUS_ACTIVE)); }
    public function scopeWithCurrentSubscription($q) { return $q->whereNotNull('subscription_id'); }
    public function scopeWithActiveOfferings($q) { return $q->whereHas('serviceOfferings', fn ($s) => $s->where('status', ServiceOffering::STATUS_ACTIVE)); }
    public function scopeOrderByFullName($q, $direction = 'asc') { return $q->orderBy('last_name', $direction)->orderBy('first_name', $direction); }

    /* =========================
     |        MUTATORS
     ========================= */

    public function setPasswordAttribute($value): void
    {
        if (!empty($value) && \Illuminate\Support\Str::startsWith($value, '$2y$') === false) {
            $this->attributes['password'] = bcrypt($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /* resolveRouteBinding identique */
    public function resolveRouteBinding($value, $field = null)
    {
        $with = ['role', 'currentSubscription', 'bookings', 'receivedBookings'];
        $query = $this->newQuery()->with($with);

        if (is_numeric($value)) return $query->where('id', (int) $value)->firstOrFail();
        return $query->where('email', $value)->firstOrFail();
    }
}
