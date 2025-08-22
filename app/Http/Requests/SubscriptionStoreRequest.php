<?php

// app/Http/Requests/SubscriptionStoreRequest.php (ex.)
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // ... autres règles d'abonnement (user_id, plan_name, etc.)

            'detail'             => ['required','array'],
            'detail.title'       => ['required','string','max:60'],      // Titre (ex: "Gold")
            'detail.subtitle'    => ['required','string','max:140'],     // Sous-titre
            'detail.period'      => ['required','string','max:20'],      // ex: "/mois"
            'detail.bullets'     => ['required','array','min:1'],        // Liste des avantages
            'detail.bullets.*'   => ['required','string','max:120'],
            'detail.color'       => ['required','string','in:blue,purple,gold,green,teal'],
            // Clés optionnelles
            'detail.old_price'   => ['nullable','numeric','min:0'],
            'detail.badge'       => ['nullable','string','max:30'],      // ex: "Plus populaire"
        ];
    }
}
