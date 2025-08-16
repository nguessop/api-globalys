<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilitySlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'availability_slots';

    protected $fillable = [
        'service_offering_id',
        'provider_id',

        'start_at',
        'end_at',
        'timezone',

        'capacity',
        'booked_count',

        'price_override',
        'currency',

        'is_recurring',
        'recurrence_rule',

        'status',
        'notes',
        'parent_id',
    ];

    protected $casts = [
        'start_at'      => 'datetime',
        'end_at'        => 'datetime',
        'capacity'      => 'integer',
        'booked_count'  => 'integer',
        'price_override'=> 'decimal:2',
        'is_recurring'  => 'boolean',
    ];

    /* ============================
     | Relations
     ============================ */

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class, 'service_offering_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /* ============================
     | Scopes
     ============================ */

    public function scopeForProvider($q, $providerId)
    {
        return $q->where('provider_id', (int) $providerId);
    }

    public function scopeForService($q, $serviceOfferingId)
    {
        return $q->where('service_offering_id', (int) $serviceOfferingId);
    }

    public function scopeStatus($q, $status)
    {
        return $q->where('status', $status);
    }

    public function scopeAvailable($q)
    {
        return $q->where('status', 'available')
            ->whereColumn('booked_count', '<', 'capacity');
    }

    public function scopeBetween($q, $from, $to)
    {
        if ($from) $q->where('end_at', '>=', $from);
        if ($to)   $q->where('start_at', '<=', $to);
        return $q;
    }

    public function scopeUpcoming($q)
    {
        return $q->where('end_at', '>=', now())
            ->orderBy('start_at', 'asc');
    }

    /* ============================
     | Helpers
     ============================ */

    public function remainingCapacity(): int
    {
        $cap = (int) ($this->capacity ?: 0);
        $booked = (int) ($this->booked_count ?: 0);
        $left = $cap - $booked;
        return $left > 0 ? $left : 0;
    }

    public function isBookable(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }
        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }
        return $this->remainingCapacity() > 0;
    }

    public function markFull(): void
    {
        $this->status = 'full';
        $this->save();
    }

    public function incrementBooked(int $qty = 1): void
    {
        $this->booked_count = (int) $this->booked_count + max(1, $qty);
        if ($this->remainingCapacity() <= 0) {
            $this->status = 'full';
        }
        $this->save();
    }

    public function decrementBooked(int $qty = 1): void
    {
        $this->booked_count = max(0, (int) $this->booked_count - max(1, $qty));
        if ($this->status === 'full' && $this->remainingCapacity() > 0) {
            $this->status = 'available';
        }
        $this->save();
    }
}
