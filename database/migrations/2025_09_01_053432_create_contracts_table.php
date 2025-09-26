<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();

            // Liens fonctionnels
            $t->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $t->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();

            // Participants
            $t->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            // Ciblage métier (selon votre modèle de données)
            // $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();        // si vous avez "services"
            $t->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete(); // sinon utilisez celui-ci

            // Métadonnées
            $t->string('number', 40)->unique();              // ex: CTR-2025-000123
            $t->string('title', 200);
            $t->unsignedInteger('version')->default(1);      // version interne du contrat
            $t->string('locale', 10)->default('fr');         // fr, fr_FR, en, ...
            $t->string('currency', 3)->default('XOF');       // ISO 4217 (ex: XOF, EUR, USD)

            // Contenu (snapshot) & variables
            $t->longText('body');                            // contenu final (Markdown/HTML/Blade rendu)
            $t->json('variables')->nullable();               // schéma/variables prévues par le template
            $t->json('filled_values')->nullable();           // valeurs remplies pour ce contrat

            // Signatures & cycle de vie
            $t->boolean('require_provider_signature')->default(true);
            $t->boolean('require_client_signature')->default(true);

            $t->enum('status', [
                'draft',            // en préparation
                'sent',             // envoyé pour signature
                'partially_signed', // une partie signée
                'signed',           // toutes signatures requises
                'cancelled',        // annulé
                'expired',          // expiré
            ])->default('draft');

            $t->timestamp('provider_signed_at')->nullable();
            $t->timestamp('client_signed_at')->nullable();
            $t->timestamp('activated_at')->nullable();       // date d’entrée en vigueur
            $t->timestamp('expires_at')->nullable();         // validité max de signature

            // Montants & conditions de paiement
            $t->decimal('amount_subtotal', 12, 2)->nullable();
            $t->decimal('amount_tax', 12, 2)->nullable();
            $t->decimal('amount_total', 12, 2)->nullable();
            $t->decimal('deposit_amount', 12, 2)->nullable(); // acompte
            $t->json('payment_terms')->nullable();            // ex: {"due_in_days":30,"late_fee":"2.5%/mois"}

            // Fichiers/trace & intégrité
            $t->string('hashed_body', 64)->nullable();        // SHA-256 du contenu rendu
            $t->string('signed_pdf_path')->nullable();        // chemin du PDF signé (storage)
            $t->json('meta')->nullable();                     // métadonnées e-sign provider, etc.

            $t->timestamps();
            $t->softDeletes();

            // Index usuels
            $t->index(['provider_id', 'client_id']);
            $t->index(['status']);
            $t->index(['meeting_id']);
            //$t->index(['service_id']);
            $t->index(['sub_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
