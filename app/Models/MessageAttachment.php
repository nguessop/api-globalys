<?php
// app/Models/MessageAttachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    protected $fillable = [
        'thread_id', 'message_id', 'uploaded_by',
        'path', 'url', 'name', 'mime_type', 'size',
        'kind', 'width', 'height', 'thumbnail_url',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    public function thread(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function uploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
