<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_id',

        'amount',
        'currency',
        'processor_fee',
        'net_amount',

        'method',
        'gateway',

        'reference',
        'idempotency_key',
        'external_id',

        'status',
        'authorized_at',
        'captured_at',
        'refunded_at',

        'failure_code',
        'failure_message',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'processor_fee'  => 'decimal:2',
        'net_amount'     => 'decimal:2',

        'authorized_at'  => 'datetime',
        'captured_at'    => 'datetime',
        'refunded_at'    => 'datetime',

        'payload'        => 'array',
        'metadata'       => 'array',
    ];

    // Enums / constantes
    public const STATUS_PENDING    = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_SUCCEEDED  = 'succeeded';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_REFUNDED   = 'refunded';
    public const STATUS_CANCELLED  = 'cancelled';

    public const METHOD_CARD         = 'card';
    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_BANK         = 'bank_transfer';
    public const METHOD_CASH         = 'cash';
    public const METHOD_WALLET       = 'wallet';

    /** Relations */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /** Scopes pratiques */
    public function scopeStatus($q, $status)          { return $q->where('status', $status); }
    public function scopeForBooking($q, $bookingId)   { return $q->where('booking_id', (int)$bookingId); }
    public function scopeForClient($q, $clientId)     { return $q->where('client_id', (int)$clientId); }
    public function scopeForProvider($q, $providerId) { return $q->where('provider_id', (int)$providerId); }
    public function scopeSucceeded($q)                { return $q->where('status', self::STATUS_SUCCEEDED); }
    public function scopePending($q)                  { return $q->where('status', self::STATUS_PENDING); }

    /** Helpers */
    public function isSucceeded(): bool  { return $this->status === self::STATUS_SUCCEEDED; }
    public function isRefunded(): bool   { return $this->status === self::STATUS_REFUNDED; }
    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isAuthorized(): bool { return $this->status === self::STATUS_AUTHORIZED; }

    public function markAuthorized(): void
    {
        $this->status = self::STATUS_AUTHORIZED;
        $this->authorized_at = now();
        $this->save();
    }

    public function markSucceeded(): void
    {
        $this->status = self::STATUS_SUCCEEDED;
        $this->captured_at = now();
        // net_amount si pas déjà calculé
        if ($this->net_amount === null) {
            $amount = (float) ($this->amount ?: 0);
            $fee    = (float) ($this->processor_fee ?: 0);
            $this->net_amount = max(0, $amount - $fee);
        }
        $this->save();
    }

    public function markFailed($code = null, $message = null): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failure_code = $code;
        $this->failure_message = $message;
        $this->save();
    }

    public function markRefunded(): void
    {
        $this->status = self::STATUS_REFUNDED;
        $this->refunded_at = now();
        $this->save();
    }

    /** Normalisation & garde-fous */
    protected static function booted(): void
    {
        $normalize = function (self $m) {
            // Devise ISO-4217
            if (!empty($m->currency)) {
                $m->currency = strtoupper(substr($m->currency, 0, 3));
            } else {
                $m->currency = 'XAF';
            }
            // Montants non négatifs
            if ($m->amount !== null)        { $m->amount = max(0, (float)$m->amount); }
            if ($m->processor_fee !== null) { $m->processor_fee = max(0, (float)$m->processor_fee); }
            // net_amount cohérent si fourni
            if ($m->net_amount !== null)    { $m->net_amount = max(0, (float)$m->net_amount); }
        };

        static::creating(function (self $model) use ($normalize) {
            $normalize($model);
            // défauts raisonnables
            if (empty($model->status))   { $model->status = self::STATUS_PENDING; }
            if (empty($model->method))   { $model->method = self::METHOD_MOBILE_MONEY; }
        });

        static::updating(function (self $model) use ($normalize) {
            $normalize($model);
        });
    }
}
