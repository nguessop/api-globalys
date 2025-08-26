<?php

// app/Models/RoleInvite.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleInvite extends Model
{
    protected $fillable = [
        'token','role','email','expires_at','max_uses','used_count','created_by','revoked_at','meta'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function isValid(?string $email = null): bool
    {
        if ($this->revoked_at) return false;
        if ($this->expires_at && now()->greaterThan($this->expires_at)) return false;
        if ($this->used_count >= $this->max_uses) return false;
        if ($this->email && $email && strtolower($this->email) !== strtolower($email)) return false;
        return in_array($this->role, ['prestataire','entreprise'], true);
    }
}
