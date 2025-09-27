<?php
// app/Models/ScheduledMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT      = 'sent';
    const STATUS_CANCELED  = 'canceled';
    const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'thread_id', 'user_id', 'body', 'scheduled_at', 'attachment_ids', 'status',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'attachment_ids'=> 'array',
    ];

    public function thread(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
