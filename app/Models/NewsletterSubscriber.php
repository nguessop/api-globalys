<?php

// app/Models/NewsletterSubscriber.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email','name','status','gdpr_consent','source','tags','meta','ip','user_agent',
        'subscribed_at','confirmed_at','unsubscribed_at','token',
    ];

    protected $casts = [
        'gdpr_consent'   => 'boolean',
        'tags'           => 'array',
        'meta'           => 'array',
        'subscribed_at'  => 'datetime',
        'confirmed_at'   => 'datetime',
        'unsubscribed_at'=> 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function($m){
            if (empty($m->token)) {
                $m->token = Str::random(40);
            }
        });
    }
}

