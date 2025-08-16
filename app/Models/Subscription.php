<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const COMMISSION_PERCENT = 'percent';
    public const COMMISSION_FIXED   = 'fixed';

    protected $fillable = [
        'user_id',
        'plan_name',        // ex: Starter, Standard, Silver, Gold, Premium...
        'plan_code',        // code interne si besoin
        'price',
        'currency',
        'start_date',
        'end_date',
        'status',
        'auto_renew',
        'payment_method',
        'payment_reference',

        // 👇 Commissions
        'commission_type',      // percent | fixed
        'commission_rate',      // si percent => % (ex: 5.00 = 5%)
        'commission_fixed',     // si fixed   => montant (ex: 500 = 500 XAF)
        'commission_notes',     // optionnel (texte libre)
    ];

    protected $casts = [
        'start_date'       => 'datetime',
        'end_date'         => 'datetime',
        'auto_renew'       => 'boolean',
        'price'            => 'decimal:2',
        'commission_rate'  => 'decimal:2',
        'commission_fixed' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->end_date
            && $this->end_date->isFuture();
    }

    /**
     * Calcule la commission pour un montant donné (ex: prix d'une transaction).
     */
    public function computeCommission(float $amount): float
    {
        if ($this->commission_type === self::COMMISSION_FIXED) {
            return max(0.0, (float)$this->commission_fixed);
        }

        // Par défaut: pourcentage
        $rate = max(0.0, (float)$this->commission_rate);
        return round(($rate / 100.0) * $amount, 2);
    }

    /**
     * Retourne un label lisible: "5% (percent)" ou "500 XAF (fixed)".
     */
    public function getCommissionLabelAttribute(): string
    {
        if ($this->commission_type === self::COMMISSION_FIXED) {
            return number_format((float)$this->commission_fixed, 0, ',', ' ') . ' ' . ($this->currency ?? 'XAF');
        }

        return rtrim(rtrim(number_format((float)$this->commission_rate, 2, ',', ' '), '0'), ',') . '%';
    }
}
