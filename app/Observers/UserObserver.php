<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Avant la création d'un utilisateur
     */
    public function creating(User $user)
    {
        // Logique avant création
    }

    /**
     * Après la création d'un utilisateur
     */
    public function created(User $user)
    {
        // Logique après création (ex: envoi d'email)
    }

    /**
     * Avant la mise à jour d'un utilisateur
     */
    public function updating(User $user)
    {
        // Logique avant mise à jour
    }

    /**
     * Après la mise à jour d'un utilisateur
     */
    public function updated(User $user)
    {
        // Logique après mise à jour
    }

    /**
     * Avant la suppression d'un utilisateur
     */
    public function deleting(User $user)
    {
        // Logique avant suppression
    }

    /**
     * Après la suppression d'un utilisateur
     */
    public function deleted(User $user)
    {
        // Logique après suppression
    }
}