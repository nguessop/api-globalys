<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    protected $table = 'contract_signatures';

    // Statuts
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED  = 'signed';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_DECLINED = 'declined'; // si tu l’utilises

    // Méthodes
    public const METHOD_CLICK    = 'click';
    public const METHOD_DRAW     = 'draw';
    public const METHOD_UPLOAD   = 'upload';
    public const METHOD_STAMP    = 'stamp';
    public const METHOD_EXTERNAL = 'external';

    protected $fillable = [
        'contract_id',
        'contract_partie_id',
        'user_id',
        'status',
        'signed_at',
        'signature_method',
        'signature_ip',
        'signature_user_agent',
        'signature_hash',
        'signature_file_path',
        'meta',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'meta'      => 'array',
    ];

    protected $appends = ['is_signed'];

    /* Relations */
    public function contract(): BelongsTo { return $this->belongsTo(Contract::class, 'contract_id'); }
    public function partie(): BelongsTo   { return $this->belongsTo(ContractPartie::class, 'contract_partie_id'); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class, 'user_id'); }

    /* Scopes */
    public function scopeForContract($q, int $contractId) { return $q->where('contract_id', $contractId); }
    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeSigned($q)   { return $q->where('status', self::STATUS_SIGNED); }

    /* Accessors */
    public function getIsSignedAttribute(): bool
    {
        return $this->status === self::STATUS_SIGNED && !is_null($this->signed_at);
    }

    /* Hooks : journalise un changement de statut important */
    protected static function booted(): void
    {
        static::updated(function (self $s) {
            if ($s->wasChanged('status')) {
                $type = $s->status === self::STATUS_SIGNED ? 'signed' : 'updated';
                $s->contract?->recordEvent($type, $s->user, [
                    'contract_signature_id' => $s->id,
                    'contract_partie_id'    => $s->contract_partie_id,
                    'status'                => $s->status,
                ]);
            }
        });
    }
}
