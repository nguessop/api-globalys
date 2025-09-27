<?php
// app/Models/MessageReaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    protected $fillable = ['message_id', 'user_id', 'emoji'];

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
