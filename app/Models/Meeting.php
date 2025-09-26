<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class Meeting extends Model
{
    use HasFactory;

    /* =========================
     |        CONSTANTES
     ========================= */
    public const PURPOSE_DISCOVERY           = 'discovery';
    public const PURPOSE_PRE_CONTRACT        = 'pre_contract';
    public const PURPOSE_CONTRACT_ASSISTANCE = 'contract_assistance';

    public const STATUS_PROPOSED  = 'proposed';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    /* =========================
     |        ATTRIBUTS
     ========================= */
    protected $fillable = [
        'sub_category_id',   // remplace service_id
        'provider_id',
        'client_id',
        'subject',
        'purpose',
        'location_type',
        'location',
        'timezone',
        'duration_minutes',
        'status',
        'selected_slot_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
    ];

    /* =========================
     |        RELATIONS
     ========================= */

    // Ciblage métier (ton "service" = sous-catégorie)
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(MeetingSlot::class);
    }

    public function selectedSlot(): BelongsTo
    {
        return $this->belongsTo(MeetingSlot::class, 'selected_slot_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MeetingNote::class);
    }

    /**
     * 🔗 Contrats liés à ce meeting (0..n).
     * La migration `contracts` possède bien un `meeting_id` nullable.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'meeting_id');
    }

    /**
     * Dernier contrat créé pour ce meeting (utile si tu n’en gardes qu’un).
     * Utilise created_at par défaut.
     */
    public function latestContract(): HasOne
    {
        return $this->hasOne(Contract::class, 'meeting_id')->latestOfMany();
    }

    /**
     * Dernier contrat SIGNÉ pour ce meeting (si tu veux retrouver
     * rapidement la version signée la plus récente).
     * Nécessite Laravel avec `ofMany()`. Sinon, fais un finder dans le code appelant.
     */
    public function latestSignedContract(): HasOne
    {
        return $this->hasOne(Contract::class, 'meeting_id')
            ->ofMany('version', 'max', function ($q) {
                $q->where('status', Contract::STATUS_SIGNED);
            });
    }

    /* =========================
     |          SCOPES
     ========================= */

    public function scopeScheduled($q)
    {
        return $q->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeUpcoming($q)
    {
        // Meetings planifiés dont le créneau sélectionné est dans le futur
        return $q->where('status', self::STATUS_SCHEDULED)
            ->whereHas('selectedSlot', fn($s) => $s->where('start_at', '>=', now()));
    }
}
