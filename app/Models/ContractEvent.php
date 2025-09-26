<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractEvent extends Model
{
    use HasFactory;

    protected $table = 'contract_events';

    /* =========================
     |        ENUMS
     ========================= */
    public const TYPE_CREATED             = 'created';
    public const TYPE_UPDATED             = 'updated';
    public const TYPE_SENT                = 'sent';
    public const TYPE_VIEWED              = 'viewed';
    public const TYPE_DOWNLOADED          = 'downloaded';
    public const TYPE_REMINDER_SENT       = 'reminder_sent';
    public const TYPE_SIGNATURE_REQUESTED = 'signature_requested';
    public const TYPE_SIGNATURE_SIGNED    = 'signature_signed';
    public const TYPE_SIGNATURE_DECLINED  = 'signature_declined';
    public const TYPE_PARTIALLY_SIGNED    = 'partially_signed';
    public const TYPE_FULLY_SIGNED        = 'fully_signed';
    public const TYPE_CANCELLED           = 'cancelled';
    public const TYPE_EXPIRED             = 'expired';
    public const TYPE_REOPENED            = 'reopened';
    public const TYPE_COMMENT_ADDED       = 'comment_added';
    public const TYPE_ATTACHMENT_ADDED    = 'attachment_added';

    public const CHANNEL_SYSTEM = 'system';
    public const CHANNEL_WEB    = 'web';
    public const CHANNEL_API    = 'api';
    public const CHANNEL_MOBILE = 'mobile';
    public const CHANNEL_EMAIL  = 'email';
    public const CHANNEL_BOT    = 'bot';
    public const CHANNEL_OTHER  = 'other';

    public const TYPES = [
        self::TYPE_CREATED,
        self::TYPE_UPDATED,
        self::TYPE_SENT,
        self::TYPE_VIEWED,
        self::TYPE_DOWNLOADED,
        self::TYPE_REMINDER_SENT,
        self::TYPE_SIGNATURE_REQUESTED,
        self::TYPE_SIGNATURE_SIGNED,
        self::TYPE_SIGNATURE_DECLINED,
        self::TYPE_PARTIALLY_SIGNED,
        self::TYPE_FULLY_SIGNED,
        self::TYPE_CANCELLED,
        self::TYPE_EXPIRED,
        self::TYPE_REOPENED,
        self::TYPE_COMMENT_ADDED,
        self::TYPE_ATTACHMENT_ADDED,
    ];

    public const CHANNELS = [
        self::CHANNEL_SYSTEM,
        self::CHANNEL_WEB,
        self::CHANNEL_API,
        self::CHANNEL_MOBILE,
        self::CHANNEL_EMAIL,
        self::CHANNEL_BOT,
        self::CHANNEL_OTHER,
    ];

    /* =========================
     |     FILLABLE & CASTS
     ========================= */
    protected $fillable = [
        'contract_id',
        'type',
        'actor_user_id',
        'contract_partie_id',
        'contract_signature_id',
        'channel',
        'occurred_at',
        'ip_address',
        'user_agent',
        'message',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta'        => 'array',
    ];

    /* =========================
     |        RELATIONS
     ========================= */

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** Utilisateur acteur (à l’origine de l’événement), optionnel */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** Partie du contrat concernée (prestataire, client, témoin, …), optionnel */
    public function partie(): BelongsTo
    {
        return $this->belongsTo(ContractPartie::class, 'contract_partie_id');
    }

    /** Lien vers un enregistrement de signature, si pertinent */
    public function signature(): BelongsTo
    {
        return $this->belongsTo(ContractSignature::class, 'contract_signature_id');
    }

    /* =========================
     |          SCOPES
     ========================= */

    public function scopeForContract($q, int $contractId)
    {
        return $q->where('contract_id', $contractId);
    }

    public function scopeForActor($q, int $userId)
    {
        return $q->where('actor_user_id', $userId);
    }

    public function scopeType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function scopeChannel($q, string $channel)
    {
        return $q->where('channel', $channel);
    }

    public function scopeSince($q, $dateTime)
    {
        return $q->where('occurred_at', '>=', $dateTime);
    }

    public function scopeBetween($q, $from, $to)
    {
        return $q->whereBetween('occurred_at', [$from, $to]);
    }

    public function scopeLatestFirst($q)
    {
        return $q->orderByDesc('occurred_at')->orderByDesc('id');
    }

    /* =========================
     |         HELPERS
     ========================= */

    /** Indique si l’événement concerne une étape de signature */
    public function isSignatureEvent(): bool
    {
        return in_array($this->type, [
            self::TYPE_SIGNATURE_REQUESTED,
            self::TYPE_SIGNATURE_SIGNED,
            self::TYPE_SIGNATURE_DECLINED,
            self::TYPE_PARTIALLY_SIGNED,
            self::TYPE_FULLY_SIGNED,
        ], true);
    }

    /**
     * Enregistre rapidement un événement.
     *
     * @param  int         $contractId
     * @param  string      $type
     * @param  array       $attrs  (actor_user_id, contract_partie_id, contract_signature_id, channel, occurred_at, ip_address, user_agent, message, meta…)
     * @return static
     */
    public static function record(int $contractId, string $type, array $attrs = []): self
    {
        $data = array_merge([
            'contract_id' => $contractId,
            'type'        => $type,
            'channel'     => self::CHANNEL_SYSTEM,
            'occurred_at' => now(),
        ], $attrs);

        return static::create($data);
    }
}
