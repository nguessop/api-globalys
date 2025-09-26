<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingNote extends Model
{
    use HasFactory;

    /**
     * Visibilité de la note :
     * - internal  : visible uniquement en interne (ex. staff / prestataire)
     * - client    : visible par le client
     * - provider  : visible par le prestataire
     * - both      : visible par client + prestataire
     */
    public const VISIBILITY_INTERNAL = 'internal';
    public const VISIBILITY_CLIENT   = 'client';
    public const VISIBILITY_PROVIDER = 'provider';
    public const VISIBILITY_BOTH     = 'both';

    protected $fillable = [
        'meeting_id',
        'author_id',
        'body',
        'visibility',   // internal | client | provider | both
        'meta',         // json optionnel (fichiers, tags, etc.)
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Relations
    public function meeting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Scopes utiles
    public function scopeShared($q)
    {
        return $q->whereIn('visibility', [self::VISIBILITY_CLIENT, self::VISIBILITY_PROVIDER, self::VISIBILITY_BOTH]);
    }

    public function scopeInternal($q)
    {
        return $q->where('visibility', self::VISIBILITY_INTERNAL);
    }
}
