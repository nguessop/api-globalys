<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractPartiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contract_parties', function (Blueprint $t) {
            $t->id();

            // Rattachement au contrat
            $t->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();

            // Lié à un utilisateur de la plateforme (facultatif : on peut inviter un externe)
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Rôle de la partie dans le contrat
            $t->enum('role', [
                'provider',     // Prestataire
                'client',       // Client
                'witness',      // Témoin
                'approver',     // Valideur (ne signe pas forcément)
                'observer',     // Observateur
                'guarantor',    // Garant
                'other',        // Autre
            ])->default('other');

            // Ordre d’affichage/signature
            $t->unsignedSmallInteger('position')->default(1);

            // Identité/coordonnées (utile si user_id null ou pour figer l'état à la signature)
            $t->string('display_name', 180);         // Ex: "Jean Dupont" ou "SAS ABC - Jean Dupont"
            $t->string('email', 190)->nullable();
            $t->string('phone', 40)->nullable();

            // Entreprise (facultatif)
            $t->string('company_name', 180)->nullable();
            $t->string('company_legal_id', 120)->nullable(); // RCCM/SIREN/N° contribuable...
            $t->json('address')->nullable();                 // {line1, line2, city, zip, country}

            // Signature
            $t->boolean('require_signature')->default(true);
            $t->timestamp('signed_at')->nullable();
            $t->enum('signature_method', ['click','draw','upload','stamp','external'])->default('click');
            $t->string('signature_ip', 45)->nullable();
            $t->string('signature_user_agent', 255)->nullable();

            // Si la signature a été réalisée par un compte précis (utile en entreprise)
            $t->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Métadonnées libres
            $t->json('meta')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // Index utiles
            $t->index(['contract_id', 'role']);
            $t->index(['contract_id', 'position']);
            $t->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contract_parties');
    }
}
