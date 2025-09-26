<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{ BelongsTo, HasMany, HasOne };

class ContractPartie extends Model
{
    use HasFactory, SoftDeletes;

    public const ROLE_PROVIDER  = 'provider';
    public const ROLE_CLIENT    = 'client';
    public const ROLE_WITNESS   = 'witness';
    public const ROLE_APPROVER  = 'approver';
    public const ROLE_OBSERVER  = 'observer';
    public const ROLE_GUARANTOR = 'guarantor';
    public const ROLE_OTHER     = 'other';

    public const SIGN_CLICK    = 'click';
    public const SIGN_DRAW     = 'draw';
    public const SIGN_UPLOAD   = 'upload';
    public const SIGN_STAMP    = 'stamp';
    public const SIGN_EXTERNAL = 'external';

    public const ROLES = [self::ROLE_PROVIDER,self::ROLE_CLIENT,self::ROLE_WITNESS,self::ROLE_APPROVER,self::ROLE_OBSERVER,self::ROLE_GUARANTOR,self::ROLE_OTHER];
    public const SIGN_METHODS = [self::SIGN_CLICK,self::SIGN_DRAW,self::SIGN_UPLOAD,self::SIGN_STAMP,self::SIGN_EXTERNAL];

    protected $table = 'contract_parties';

    protected $fillable = [
        'contract_id','user_id','role','position',
        'display_name','email','phone',
        'company_name','company_legal_id','address',
        'require_signature','signed_at','signature_method','signature_ip','signature_user_agent',
        'signer_user_id','meta',
    ];

    protected $casts = [
        'position' => 'integer',
        'require_signature' => 'boolean',
        'signed_at' => 'datetime',
        'address' => 'array',
        'meta' => 'array',
    ];

    protected $appends = ['is_signed', 'signature_status'];

    /* Relations */
    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function signer(): BelongsTo   { return $this->belongsTo(User::class, 'signer_user_id'); }

    public function signatures(): HasMany { return $this->hasMany(ContractSignature::class, 'contract_partie_id'); }

    public function lastSignature(): HasOne
    {
        return $this->hasOne(ContractSignature::class, 'contract_partie_id')->latestOfMany();
    }

    public function pendingSignature(): HasOne
    {
        return $this->hasOne(ContractSignature::class, 'contract_partie_id')
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('status', ContractSignature::STATUS_PENDING));
    }

    /* Scopes */
    public function scopeByRole($q, string $role) { return $q->where('role', $role); }
    public function scopeOrderByPosition($q, $direction = 'asc') { return $q->orderBy('position', $direction)->orderBy('id', $direction); }
    public function scopeRequiringSignature($q) { return $q->where('require_signature', true); }
    public function scopePendingSignature($q)   { return $q->requiringSignature()->whereNull('signed_at'); }
    public function scopeSigned($q)             { return $q->whereNotNull('signed_at'); }

    /* Accessors */
    public function getIsSignedAttribute(): bool
    {
        if (!is_null($this->signed_at)) return true;
        if ($this->relationLoaded('signatures')) {
            return (bool) $this->signatures->firstWhere('status', ContractSignature::STATUS_SIGNED);
        }
        return $this->signatures()->where('status', ContractSignature::STATUS_SIGNED)->exists();
    }

    public function getSignatureStatusAttribute(): string
    {
        if ($this->is_signed) return ContractSignature::STATUS_SIGNED;
        return $this->require_signature ? ContractSignature::STATUS_PENDING : 'optional';
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->company_name) return trim($this->company_name . ' - ' . ($this->display_name ?? ''));
        return (string) ($this->display_name ?? '');
    }

    /* Helpers */

    public function isSigned(): bool { return $this->getIsSignedAttribute(); }

    public function createPendingSignature(?string $method = null, ?array $meta = null): ContractSignature
    {
        $method = $method && in_array($method, self::SIGN_METHODS, true) ? $method : ContractSignature::METHOD_CLICK;

        return $this->signatures()->create([
            'contract_id'       => $this->contract_id,
            'user_id'           => $this->user_id,
            'status'            => ContractSignature::STATUS_PENDING,
            'signature_method'  => $method,
            'meta'              => $meta,
        ]);
    }

    public function sign(
        ?User $actor = null,
        ?string $ip = null,
        ?string $ua = null,
        ?string $method = null,
        ?string $signatureHash = null,
        ?string $signatureFile = null,
        ?array $meta = null
    ): self {
        if ($this->isSigned()) return $this;

        $this->signed_at = now();
        $this->signature_ip = $ip ?: $this->signature_ip;
        $this->signature_user_agent = $ua ?: $this->signature_user_agent;

        if ($method && in_array($method, self::SIGN_METHODS, true)) {
            $this->signature_method = $method;
        }

        if ($actor) {
            $this->signer_user_id = $actor->getKey();
        } elseif (!$this->signer_user_id && $this->user_id) {
            $this->signer_user_id = $this->user_id;
        }

        $this->save();

        // Journal de signature
        $this->signatures()->create([
            'contract_id'          => $this->contract_id,
            'contract_partie_id'   => $this->id,
            'user_id'              => $this->signer_user_id,
            'status'               => ContractSignature::STATUS_SIGNED,
            'signed_at'            => now(),
            'signature_method'     => $this->signature_method ?: ContractSignature::METHOD_CLICK,
            'signature_ip'         => $this->signature_ip,
            'signature_user_agent' => $this->signature_user_agent,
            'signature_hash'       => $signatureHash,
            'signature_file_path'  => $signatureFile,
            'meta'                 => $meta,
        ]);

        // Événement "signed" au niveau contrat (avec partie)
        $this->contract?->recordEvent('signed', $actor, [
            'contract_partie_id' => $this->id,
            'role' => $this->role,
        ], $this);

        // Met à jour le statut global
        $this->contract?->refreshSignatureStatus($actor);

        return $this;
    }

    /* Hooks */

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if ($model->user_id && (!$model->display_name || !$model->email || !$model->phone)) {
                $u = User::find($model->user_id);
                if ($u) {
                    $model->display_name = $model->display_name ?: (trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->company_name);
                    $model->email = $model->email ?: $u->email;
                    $model->phone = $model->phone ?: $u->phone;
                    if (!$model->company_name && $u->company_name) $model->company_name = $u->company_name;
                }
            }
        });
    }
}
