<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractEventsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contract_events', function (Blueprint $t) {
            $t->id();

            // Contrat concerné
            $t->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();

            // Typologie d’événement (timeline)
            $t->enum('type', [
                'created',              // Contrat créé
                'updated',              // Métadonnées/contenu modifiés
                'sent',                 // Envoyé aux parties
                'viewed',               // Vu/consulté
                'downloaded',           // Téléchargé
                'reminder_sent',        // Relance envoyée
                'signature_requested',  // Demande de signature envoyée
                'signature_signed',     // Signature effectuée (une partie)
                'signature_declined',   // Signature refusée
                'partially_signed',     // Au moins une signature requise posée
                'fully_signed',         // Toutes les signatures requises posées
                'cancelled',            // Contrat annulé
                'expired',              // Contrat expiré
                'reopened',             // Ré-ouvert
                'comment_added',        // Commentaire ajouté
                'attachment_added',     // Pièce jointe ajoutée
            ])->index();

            // Contexte acteur / rattachements optionnels
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('contract_partie_id')->nullable()->constrained('contract_parties')->nullOnDelete();
            $t->foreignId('contract_signature_id')->nullable()->constrained('contract_signatures')->nullOnDelete();

            // Canal & traces techniques
            $t->enum('channel', ['system','web','api','mobile','email','bot','other'])->default('system');
            $t->timestamp('occurred_at')->nullable(); // horodatage "réel" si connu (sinon created_at fait foi)
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 255)->nullable();

            // Message lisible + métadonnées libres
            $t->text('message')->nullable();
            $t->json('meta')->nullable();

            $t->timestamps();

            // Index utiles
            $t->index(['contract_id', 'occurred_at']);
            $t->index(['actor_user_id']);
            $t->index(['contract_partie_id']);
            $t->index(['contract_signature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contract_events');
    }
}
