<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractSignaturesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contract_signatures', function (Blueprint $t) {
            $t->id();

            // Liens
            $t->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $t->foreignId('contract_partie_id')->constrained('contract_parties')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // compte qui a réellement signé (si applicable)

            // État & horodatage de la signature
            $t->enum('status', ['pending', 'signed', 'revoked'])->default('pending');
            $t->timestamp('signed_at')->nullable();

            // Méthode & traces techniques
            $t->enum('signature_method', ['click','draw','upload','stamp','external'])->default('click');
            $t->string('signature_ip', 45)->nullable();
            $t->string('signature_user_agent', 255)->nullable();

            // Preuves / intégrité
            $t->string('signature_hash', 64)->nullable();        // ex: SHA-256 d'une payload/empreinte
            $t->string('signature_file_path', 255)->nullable();  // chemin d’un fichier de preuve (png/pdf)

            // Métadonnées libres (payload provider e-sign, evidences additionnelles, etc.)
            $t->json('meta')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // Index utiles
            $t->index(['contract_id', 'contract_partie_id']);
            $t->index(['user_id']);
            $t->index(['status']);
            $t->index(['signed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contract_signatures');
    }
}
