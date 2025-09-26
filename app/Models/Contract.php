<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{ BelongsTo, HasMany };

class Contract extends Model
{
    use SoftDeletes;

    /* =========================
     |        STATUTS
     ========================= */
    public const STATUS_DRAFT            = 'draft';
    public const STATUS_SENT             = 'sent';
    public const STATUS_PARTIALLY_SIGNED = 'partially_signed';
    public const STATUS_SIGNED           = 'signed';
    public const STATUS_CANCELLED        = 'cancelled';
    public const STATUS_EXPIRED          = 'expired';

    protected $fillable = [
        'template_id','meeting_id','provider_id','client_id','sub_category_id',
        'number','title','version','locale','currency',
        'body','variables','filled_values',
        'require_provider_signature','require_client_signature','status',
        'provider_signed_at','client_signed_at','activated_at','expires_at',
        'amount_subtotal','amount_tax','amount_total','deposit_amount','payment_terms',
        'hashed_body','signed_pdf_path','meta',
    ];

    protected $casts = [
        'version' => 'integer',
        'require_provider_signature' => 'boolean',
        'require_client_signature'   => 'boolean',
        'variables'     => 'array',
        'filled_values' => 'array',
        'payment_terms' => 'array',
        'meta'          => 'array',
        'provider_signed_at' => 'datetime',
        'client_signed_at'   => 'datetime',
        'activated_at'       => 'datetime',
        'expires_at'         => 'datetime',
        'amount_subtotal' => 'decimal:2',
        'amount_tax'      => 'decimal:2',
        'amount_total'    => 'decimal:2',
        'deposit_amount'  => 'decimal:2',
    ];

    protected $appends = ['is_fully_signed','is_expired','can_activate'];

    /* =========================
     |        RELATIONS
     ========================= */

    public function template(): BelongsTo   { return $this->belongsTo(ContractTemplate::class, 'template_id'); }
    public function meeting(): BelongsTo    { return $this->belongsTo(Meeting::class, 'meeting_id'); }
    public function provider(): BelongsTo   { return $this->belongsTo(User::class, 'provider_id'); }
    public function client(): BelongsTo     { return $this->belongsTo(User::class, 'client_id'); }
    public function subCategory(): BelongsTo{ return $this->belongsTo(SubCategory::class, 'sub_category_id'); }

    public function parties(): HasMany            { return $this->hasMany(ContractPartie::class); }
    public function requiredParties(): HasMany    { return $this->parties()->where('require_signature', true); }
    public function pendingParties(): HasMany     { return $this->requiredParties()->whereNull('signed_at'); }
    public function signedParties(): HasMany      { return $this->parties()->whereNotNull('signed_at'); }

    /** Journal d’événements */
    public function events(): HasMany             { return $this->hasMany(ContractEvent::class); }

    /** Signatures (journal complet) */
    public function signatures(): HasMany         { return $this->hasMany(ContractSignature::class); }
    public function pendingSignatures(): HasMany  { return $this->signatures()->where('status', ContractSignature::STATUS_PENDING); }
    public function signedSignatures(): HasMany   { return $this->signatures()->where('status', ContractSignature::STATUS_SIGNED); }
    public function declinedSignatures(): HasMany { return $this->signatures()->where('status', ContractSignature::STATUS_REVOKED); }

    /* =========================
     |       HELPERS PARTIES
     ========================= */

    public function nextSigner(): ?ContractPartie
    {
        return $this->pendingParties()->orderBy('position')->orderBy('id')->first();
    }

    public function addParty(array $attributes): ContractPartie
    {
        if (!isset($attributes['position'])) {
            $max = (int) $this->parties()->max('position');
            $attributes['position'] = $max > 0 ? $max + 1 : 1;
        }
        return $this->parties()->create($attributes);
    }

    public function addPartyFromUser(User $user, string $role, array $overrides = []): ContractPartie
    {
        $base = [
            'user_id'      => $user->id,
            'role'         => $role,
            'display_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->company_name,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'company_name' => $user->company_name,
            'require_signature' => true,
        ];
        return $this->addParty(array_replace($base, $overrides));
    }

    public function providerParty(): ?ContractPartie
    {
        return $this->parties()->where('role', ContractPartie::ROLE_PROVIDER)->orderBy('position')->first();
    }

    public function clientParty(): ?ContractPartie
    {
        return $this->parties()->where('role', ContractPartie::ROLE_CLIENT)->orderBy('position')->first();
    }

    /** Pré-crée des signatures "pending" pour les parties requises */
    public function ensurePendingSignaturesForRequiredParties(?string $method = null): void
    {
        $requiredIds = $this->requiredParties()->pluck('id')->all();
        if (!$requiredIds) return;

        $already = $this->signatures()->whereIn('contract_partie_id', $requiredIds)
            ->pluck('contract_partie_id')->unique()->all();

        $missing = array_diff($requiredIds, $already);
        if ($missing) {
            $this->parties()->whereIn('id', $missing)->get()
                ->each(fn (ContractPartie $p) => $p->createPendingSignature($method));
        }
    }

    /* =========================
     |          SCOPES
     ========================= */

    public function scopeStatus($q, string $status) { return $q->where('status', $status); }

    public function scopeForUser($q, int $userId)
    {
        return $q->where(function ($w) use ($userId) {
            $w->where('provider_id', $userId)
                ->orWhere('client_id', $userId)
                ->orWhereHas('parties', fn ($p) => $p->where('user_id', $userId));
        });
    }

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_SIGNED)
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /* =========================
     |         ACCESSORS
     ========================= */

    public function getIsFullySignedAttribute(): bool
    {
        $requiredCount = (int) $this->requiredParties()->count();
        if ($requiredCount > 0) {
            $requiredIds = $this->requiredParties()->pluck('id')->all();
            $signedIds = $this->signedSignatures()->whereIn('contract_partie_id', $requiredIds)
                ->pluck('contract_partie_id')->unique()->all();
            return count($signedIds) >= $requiredCount;
        }

        $needProv = $this->require_provider_signature;
        $needCli  = $this->require_client_signature;
        $provOk   = !$needProv || !is_null($this->provider_signed_at);
        $cliOk    = !$needCli  || !is_null($this->client_signed_at);
        return ($provOk && $cliOk);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at ? $this->expires_at->isPast() : false;
    }

    public function getCanActivateAttribute(): bool
    {
        return $this->is_fully_signed && !$this->is_expired && is_null($this->activated_at);
    }

    /* =========================
     |          HELPERS
     ========================= */

    public function markSent(?User $actor = null): void
    {
        $this->status = self::STATUS_SENT;
        $this->save();
        $this->recordEvent(ContractEvent::TYPE_SENT, $actor);
    }

    public function markCancelled(?User $actor = null, ?string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
        $this->recordEvent(ContractEvent::TYPE_CANCELLED, $actor, ['reason' => $reason]);
    }

    /** Fallback duo provider/client */
    public function signAsProvider(?User $actor = null): void
    {
        if ($this->require_provider_signature && is_null($this->provider_signed_at)) {
            $this->provider_signed_at = Carbon::now();
            $this->save();
            $this->recordEvent(ContractEvent::TYPE_PARTIALLY_SIGNED, $actor, ['mode' => 'fallback_provider']);
            $this->refreshSignatureStatus($actor);
        }
    }

    public function signAsClient(?User $actor = null): void
    {
        if ($this->require_client_signature && is_null($this->client_signed_at)) {
            $this->client_signed_at = Carbon::now();
            $this->save();
            $this->recordEvent(ContractEvent::TYPE_PARTIALLY_SIGNED, $actor, ['mode' => 'fallback_client']);
            $this->refreshSignatureStatus($actor);
        }
    }

    /**
     * Met à jour le statut selon l’état des signatures et journalise les transitions.
     */
    public function refreshSignatureStatus(?User $actor = null): void
    {
        $before = $this->status;

        $requiredIds = $this->requiredParties()->pluck('id')->all();
        if ($requiredIds) {
            $signedIds = $this->signedSignatures()->whereIn('contract_partie_id', $requiredIds)
                ->pluck('contract_partie_id')->unique()->all();

            $required = count($requiredIds);
            $signed   = count($signedIds);

            if ($signed >= $required && $required > 0) {
                $this->status = self::STATUS_SIGNED;
                if (is_null($this->activated_at)) {
                    $this->activated_at = Carbon::now();
                }
            } elseif ($signed > 0) {
                $this->status = self::STATUS_PARTIALLY_SIGNED;
            } elseif ($this->status === self::STATUS_DRAFT) {
                $this->status = self::STATUS_SENT;
            }

            $this->save();
        } else {
            if ($this->is_fully_signed) {
                $this->status = self::STATUS_SIGNED;
                if (is_null($this->activated_at)) {
                    $this->activated_at = Carbon::now();
                }
            } elseif ($this->provider_signed_at || $this->client_signed_at) {
                $this->status = self::STATUS_PARTIALLY_SIGNED;
            } elseif ($this->status === self::STATUS_DRAFT) {
                $this->status = self::STATUS_SENT;
            }
            $this->save();
        }

        if ($before !== $this->status) {
            $type = match ($this->status) {
                self::STATUS_SIGNED           => ContractEvent::TYPE_FULLY_SIGNED,
                self::STATUS_PARTIALLY_SIGNED => ContractEvent::TYPE_PARTIALLY_SIGNED,
                self::STATUS_SENT             => ContractEvent::TYPE_SENT,
                self::STATUS_CANCELLED        => ContractEvent::TYPE_CANCELLED,
                self::STATUS_EXPIRED          => ContractEvent::TYPE_EXPIRED,
                default                       => ContractEvent::TYPE_UPDATED,
            };
            $this->recordEvent($type, $actor, ['from' => $before, 'to' => $this->status]);
        }
    }

    /** Envoie pour signature + pré-crée les signatures pending */
    public function sendForSignature(?User $actor = null, ?string $method = null): void
    {
        $this->markSent($actor);
        $this->ensurePendingSignaturesForRequiredParties($method);
    }

    public function computeTotalsIfMissing(): void
    {
        if (!is_null($this->amount_total)) return;
        $subtotal = (float) ($this->amount_subtotal ?? 0);
        $tax      = (float) ($this->amount_tax ?? 0);
        $this->amount_total = round($subtotal + $tax, 2);
    }

    /**
     * Normalise une valeur de type d’événement libre en une valeur ENUM autorisée.
     * (ex: 'signed' => 'fully_signed')
     */
    protected function normalizeEventType(string $type): string
    {
        $type = strtolower(trim($type));

        // déjà valide ?
        if (in_array($type, ContractEvent::TYPES, true)) {
            return $type;
        }

        // mappings tolérants
        $map = [
            'signed'            => ContractEvent::TYPE_FULLY_SIGNED,
            'signature'         => ContractEvent::TYPE_SIGNATURE_SIGNED,
            'signature_signed'  => ContractEvent::TYPE_SIGNATURE_SIGNED,
            'declined'          => ContractEvent::TYPE_SIGNATURE_DECLINED,
            'rejected'          => ContractEvent::TYPE_SIGNATURE_DECLINED,
            'partial'           => ContractEvent::TYPE_PARTIALLY_SIGNED,
            'view'              => ContractEvent::TYPE_VIEWED,
            'download'          => ContractEvent::TYPE_DOWNLOADED,
            'send'              => ContractEvent::TYPE_SENT,
            'cancel'            => ContractEvent::TYPE_CANCELLED,
            'expire'            => ContractEvent::TYPE_EXPIRED,
        ];

        return $map[$type] ?? ContractEvent::TYPE_UPDATED;
    }

    /** Helper pour journaliser un événement */
    public function recordEvent(
        string $type,
        ?User $actor = null,
        ?array $data = null,               // stocké dans 'meta'
        ?ContractPartie $partie = null,
        ?string $ip = null,
        ?string $ua = null,
        ?string $channel = ContractEvent::CHANNEL_SYSTEM,
        ?string $message = null
    ): ContractEvent {
        return $this->events()->create([
            'type'               => $this->normalizeEventType($type), // ✅ normalisation ici
            'actor_user_id'      => $actor?->id,
            'contract_partie_id' => $partie?->id,
            'channel'            => $channel ?? ContractEvent::CHANNEL_SYSTEM,
            'occurred_at'        => now(),
            'ip_address'         => $ip,
            'user_agent'         => $ua,
            'message'            => $message,
            'meta'               => $data,
        ]);
    }

    /* =========================
     |          EVENTS
     ========================= */

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->number)) $c->number = self::generateNumber();
            $c->version  = $c->version  ?: 1;
            $c->locale   = $c->locale   ?: 'fr';
            $c->currency = $c->currency ?: 'XOF';
            $c->computeTotalsIfMissing();
            if (!empty($c->body)) $c->hashed_body = hash('sha256', $c->body);
        });

        static::created(function (self $c) {
            $c->recordEvent(ContractEvent::TYPE_CREATED);
        });

        static::saving(function (self $c) {
            if ($c->isDirty('body') && !empty($c->body)) {
                $c->hashed_body = hash('sha256', $c->body);
            }
            if ($c->isDirty('amount_subtotal') || $c->isDirty('amount_tax')) {
                $c->computeTotalsIfMissing();
            }
            if ($c->status !== self::STATUS_SIGNED && $c->expires_at && $c->expires_at->isPast()) {
                $c->status = self::STATUS_EXPIRED;
            }
        });

        static::updated(function (self $c) {
            if ($c->wasChanged('status') && $c->status === self::STATUS_EXPIRED) {
                $c->recordEvent(ContractEvent::TYPE_EXPIRED);
            } elseif ($c->wasChanged() && !$c->wasChanged('status')) {
                $c->recordEvent(ContractEvent::TYPE_UPDATED, null, ['fields' => array_keys($c->getChanges())]);
            }
        });
    }

    protected static function generateNumber(): string
    {
        $y = Carbon::now()->format('Y');
        $rand = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        return "CTR-{$y}-{$rand}";
    }

    // ajouter au cas ou .........
}
