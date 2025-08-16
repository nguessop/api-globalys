<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_id',
        'service_offering_id',
        'booking_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    /* ============================
     | Relations
     ============================ */

    /** Auteur de l'avis (client) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Prestataire évalué */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /** Service évalué */
    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    /** Réservation liée */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /* ============================
     | Scopes
     ============================ */

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_offering_id', $serviceId);
    }

    public function scopeWithRating($query, $min, $max = null)
    {
        $query->where('rating', '>=', $min);
        if ($max !== null) {
            $query->where('rating', '<=', $max);
        }
        return $query;
    }
}
