<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    // Statuts réservation
    public const STATUS_PENDING     = 'pending';
    public const STATUS_CONFIRMED   = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    // Statut paiement
    public const PAYMENT_UNPAID   = 'unpaid';
    public const PAYMENT_PAID     = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_PARTIAL  = 'partial';

    protected $table = 'bookings';

    protected $fillable = [
        // Liens
        'client_id',
        'provider_id',
        'service_offering_id',
        'meeting_id',            // ⬅️ lien optionnel vers le meeting d'origine

        // Référence
        'code',

        // Fenêtre de prestation
        'start_at',
        'end_at',
        'city',
        'address',
        'notes_client',
        'notes_provider',

        // Montants (⚠️ plus de champs de commission ici)
        'currency',
        'unit_price',
        'quantity',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_status',

        // Statuts
        'status',
        'cancelled_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'start_at'        => 'datetime',
        'end_at'          => 'datetime',
        'cancelled_at'    => 'datetime',

        'unit_price'      => 'decimal:2',
        'quantity'        => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    /* =========================
     |        RELATIONS
     ========================= */

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class, 'service_offering_id');
    }

    /** Meeting d’origine (rendez-vous de cadrage / assistance contractuelle) */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    /** Créneau réservé (facultatif si tu affectes un slot précis) */
    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class, 'availability_slot_id');
    }

    /* =========================
     |          SCOPES
     ========================= */

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForSlot($query, $slotId)
    {
        return $query->where('availability_slot_id', (int) $slotId);
    }

    /** Réservations issues d’un meeting donné */
    public function scopeForMeeting($query, $meetingId)
    {
        return $query->where('meeting_id', (int) $meetingId);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS])
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', Carbon::now());
            })
            ->orderBy('start_at');
    }

    public function scopePast($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED])
            ->orderByDesc('end_at');
    }

    /* =========================
     |          EVENTS
     ========================= */

    protected static function booted(): void
    {
        // Avant création : calculs de base (hors commission)
        static::creating(function (self $model) {
            // Code
            if (empty($model->code)) {
                $date = Carbon::now()->format('Ymd');
                $rand = strtoupper(substr(md5(uniqid('', true)), 0, 4));
                $model->code = 'BK-' . $date . '-' . $rand;
            }

            // Montants
            if ($model->subtotal === null) {
                $qty  = (float) ($model->quantity ?: 1);
                $unit = (float) ($model->unit_price ?: 0);
                $model->subtotal = round($qty * $unit, 2);
            }

            if ($model->tax_amount === null) {
                $rate = (float) ($model->tax_rate ?: 0);
                $model->tax_amount = round(($rate / 100.0) * (float) $model->subtotal, 2);
            }

            if ($model->discount_amount === null) {
                $model->discount_amount = 0;
            }

            if ($model->total_amount === null) {
                $model->total_amount = max(
                    0,
                    round((float)$model->subtotal + (float)$model->tax_amount - (float)$model->discount_amount, 2)
                );
            }

            if (empty($model->currency)) {
                $model->currency = 'XAF';
            }

            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }

            if (empty($model->payment_status)) {
                $model->payment_status = self::PAYMENT_UNPAID;
            }

            // (Optionnel) Si on veut copier automatiquement des horaires depuis le meeting:
            // - Laisse ce bloc commenté si tu préfères gérer ça au service.
            // if ($model->meeting_id && !$model->start_at) {
            //     $meeting = Meeting::with('selectedSlot')->find($model->meeting_id);
            //     if ($meeting && $meeting->selectedSlot) {
            //         $model->start_at = $meeting->selectedSlot->start_at;
            //         $model->end_at   = $meeting->selectedSlot->end_at;
            //     }
            // }
        });

        // Après création : créer la commission associée (snapshot depuis l’abonnement du provider)
        static::created(function (self $booking) {
            // Récupère l’abonnement courant/actif du provider (si dispo)
            $provider     = $booking->provider()->first();
            $subscription = $provider ? $provider->subscriptionOrActive() : null;

            // Détermine la règle (par défaut: percent 0)
            $commissionType  = $subscription ? $subscription->commission_type : \App\Models\Commission::TYPE_PERCENT;
            $commissionRate  = $subscription ? (float)$subscription->commission_rate : 0.0;
            $commissionFixed = $subscription ? (float)$subscription->commission_fixed : null;

            // Crée la commission liée
            $booking->commission()->create([
                'provider_id'      => $booking->provider_id,
                'subscription_id'  => $subscription ? $subscription->id : null,
                'base_amount'      => (float)$booking->subtotal, // base de calcul = subtotal
                'currency'         => $booking->currency ?: 'XAF',

                'commission_type'  => $commissionType,
                'commission_rate'  => $commissionType === \App\Models\Commission::TYPE_PERCENT ? $commissionRate : null,
                'commission_fixed' => $commissionType === \App\Models\Commission::TYPE_FIXED ? $commissionFixed : null,

                // Laisse le modèle Commission calculer amount dans son creating()
                'status'           => \App\Models\Commission::STATUS_PENDING,
            ]);
        });
    }

    /* =========================
     |          HELPERS
     ========================= */

    public function isCancelable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true)
            && ($this->start_at ? $this->start_at->isFuture() : true);
    }

    public function markCancelled($reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_reason = $reason;
        $this->cancelled_at = Carbon::now();
        $this->save();
    }

    public function markConfirmed(): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->save();
    }

    public function markInProgress(): void
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    public function markPaid(): void
    {
        $this->payment_status = self::PAYMENT_PAID;
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function getLabelAttribute(): string
    {
        return sprintf('%s • %s → %s', $this->code, (string)$this->start_at, (string)$this->end_at);
    }

    /**
     * Avis associé à cette réservation (s'il existe).
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'booking_id');
    }
}
