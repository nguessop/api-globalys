<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    /** Types de commission */
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed';

    /** Statuts */
    public const STATUS_PENDING   = 'pending';   // calculée, pas encore capturée
    public const STATUS_CAPTURED  = 'captured';  // encaissée côté plateforme
    public const STATUS_SETTLED   = 'settled';   // reversée au prestataire (si logique split/payout)
    public const STATUS_REFUNDED  = 'refunded';  // remboursée
    public const STATUS_CANCELLED = 'cancelled'; // annulée

    protected $table = 'commissions';

    protected $fillable = [
        'booking_id',          // Réservation source
        'provider_id',         // Prestataire (User)
        'subscription_id',     // Abonnement utilisé (snapshot, optionnel)

        // Base de calcul
        'base_amount',         // Montant de référence (souvent subtotal de la booking)
        'currency',            // Devise (ex: XAF)

        // Règle appliquée (snapshot)
        'commission_type',     // percent | fixed
        'commission_rate',     // en % si percent
        'commission_fixed',    // montant si fixed

        // Montants calculés
        'amount',              // commission finale (prélevée par la plateforme)

        // Cycle de vie
        'status',
        'captured_at',         // date encaissement effectif
        'settled_at',          // date reversement (si appliqué)
        'refunded_at',

        // Références/notes externes
        'external_reference',  // id transaction PSP, etc.
        'notes',
        'metadata',            // json libre
    ];

    protected $casts = [
        'base_amount'       => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_fixed'  => 'decimal:2',
        'amount'            => 'decimal:2',

        'captured_at'       => 'datetime',
        'settled_at'        => 'datetime',
        'refunded_at'       => 'datetime',

        'metadata'          => 'array',
    ];

    /* Relations */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /* Scopes utiles */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /* Helpers */
    /**
     * Calcule le montant de commission selon la règle (percent/fixed).
     * Si $baseAmount est null, utilise base_amount.
     */
    public function computeAmount(?float $baseAmount = null): float
    {
        $base = $baseAmount !== null ? $baseAmount : (float) $this->base_amount;

        if ($this->commission_type === self::TYPE_FIXED) {
            return max(0.0, (float) $this->commission_fixed);
        }

        // Par défaut: pourcentage
        $rate = max(0.0, (float) $this->commission_rate);
        return round(($rate / 100.0) * $base, 2);
    }

    /** Applique le calcul et remplit $amount si vide. */
    public function ensureAmountCalculated(): void
    {
        if ($this->amount === null) {
            $this->amount = $this->computeAmount();
        }
    }

    /** Transitions simples de statut */
    public function markCaptured(?string $reference = null): void
    {
        $this->status = self::STATUS_CAPTURED;
        $this->captured_at = now();
        if (!empty($reference)) {
            $this->external_reference = $reference;
        }
        $this->save();
    }

    public function markSettled(): void
    {
        $this->status = self::STATUS_SETTLED;
        $this->settled_at = now();
        $this->save();
    }

    public function markRefunded(): void
    {
        $this->status = self::STATUS_REFUNDED;
        $this->refunded_at = now();
        $this->save();
    }

    public function markCancelled(?string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        if (!empty($reason)) {
            $this->notes = trim((string) $this->notes . PHP_EOL . 'Cancelled: ' . $reason);
        }
        $this->save();
    }

    /* Événement: calcul automatique si non fourni */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Devise par défaut
            if (empty($model->currency)) {
                $model->currency = 'XAF';
            }

            // Status par défaut
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }

            // Calcul auto
            if ($model->amount === null) {
                $model->amount = $model->computeAmount();
            }
        });
    }
}
