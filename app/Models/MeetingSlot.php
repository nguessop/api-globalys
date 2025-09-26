<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MeetingSlot extends Model
{
    use HasFactory;

    // Harmonise ces constantes avec TA migration
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';   // si tu gardes "declined"
    public const STATUS_CANCELLED = 'cancelled'; // si tu préfères "cancelled"
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'meeting_id',
        'start_at',
        'end_at',
        'status',        // proposed|accepted|declined|cancelled|expired (au choix, mais cohérent partout)
        'proposed_by',   // users.id
        'location',
        'timezone',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    /** Met à jour updated_at du meeting quand un slot change */
    protected $touches = ['meeting'];

    /* ------------------------------- Relations ------------------------------- */

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /* --------------------------------- Scopes -------------------------------- */

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())->orderBy('start_at');
    }

    public function scopeForMeeting($query, $meetingId)
    {
        return $query->where('meeting_id', $meetingId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /* -------------------------------- Helpers -------------------------------- */

    public function isPast(): bool
    {
        $end = $this->end_at ?: $this->start_at;
        return $end ? $end->isPast() : false;
    }

    public function durationMinutes(): ?int
    {
        if (!$this->start_at) return null;
        $end = $this->end_at ?: $this->start_at;
        return $this->start_at->diffInMinutes($end);
    }

    /**
     * Accepte ce créneau :
     *  - passe ce slot en ACCEPTED
     *  - met les autres slots du meeting en DECLINED
     *  - renseigne meetings.selected_slot_id
     *  - passe le meeting en "scheduled"
     */
    public function accept(): void
    {
        DB::transaction(function () {
            // Rafraîchir pour éviter les surprises
            $this->refresh();

            // 1) Ce slot = accepted
            $this->update(['status' => self::STATUS_ACCEPTED]);

            // 2) Les autres = declined (si tu utilises "declined")
            $this->meeting
                ->slots()
                ->where('id', '!=', $this->id)
                ->update(['status' => self::STATUS_DECLINED]);

            // 3) Meeting.selected_slot_id + statut
            $this->meeting->update([
                'selected_slot_id' => $this->id,
                'status'           => 'scheduled', // ou Meeting::STATUS_SCHEDULED si tu as des constantes
            ]);
        });
    }

    /** Décline explicitement ce créneau */
    public function decline(): void
    {
        $this->update(['status' => self::STATUS_DECLINED]);
    }

    /** Annule ce créneau (différent de decline : annulation unilatérale) */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /** Marque comme expiré si la fin (ou début) est passée */
    public function expireIfPast(): void
    {
        if ($this->isPast() && $this->status === self::STATUS_PROPOSED) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }
    }

    /* ------------------------------ Model events ----------------------------- */

    protected static function booted(): void
    {
        // Défauts & validations rapides
        static::creating(function (self $slot) {
            if (empty($slot->timezone)) {
                $slot->timezone = 'UTC';
            }

            self::assertDates($slot->start_at, $slot->end_at);
        });

        static::updating(function (self $slot) {
            self::assertDates($slot->start_at, $slot->end_at);
        });
    }

    protected static function assertDates($start, $end): void
    {
        if (!$start instanceof Carbon) return; // laisser la DB/validation formelle trancher
        $effectiveEnd = $end instanceof Carbon ? $end : $start;
        if ($effectiveEnd->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('end_at doit être strictement supérieur à start_at.');
        }
    }
}
