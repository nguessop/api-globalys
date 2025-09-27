<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MessageThread extends Model
{
    use HasFactory;

    protected $table = 'message_threads';
    public $incrementing = false; // UUID => pas auto-incrément
    protected $keyType = 'string'; // Clé primaire = string (UUID)

    protected $fillable = [
        'service_offering_id',
        'customer_id',
        'provider_id',
        'subject',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Génération automatique d’un UUID si pas encore défini
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relations
    public function participants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MessageThreadParticipant::class, 'thread_id');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'thread_id')->orderBy('created_at', 'asc');
    }

    public function serviceOffering(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class, 'service_offering_id');
    }
}
