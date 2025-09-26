<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contract_templates', function (Blueprint $t) {
            $t->id();

            // Optionnel : rattacher un auteur (garde la template si l'utilisateur est supprimé)
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Optionnel : portée par sous-catégorie (si vous avez la table sub_categories)
            $t->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();

            // Métadonnées
            $t->string('title', 200);                // Titre lisible
            $t->string('code', 100);                 // Code/slug technique (ex: "prestations_standard")
            $t->unsignedInteger('version')->default(1);
            $t->string('locale', 10)->default('fr'); // fr, en, fr_FR, etc.
            $t->text('description')->nullable();

            // Contenu & variables
            $t->longText('body');                    // Markdown/Blade/HTML du contrat
            $t->json('variables')->nullable();       // Schéma/valeurs par défaut (ex: {"price":"number","delay":"string"})

            // Paramètres de signature / visibilité
            $t->enum('visibility', ['provider_only','client_only','both'])->default('both');
            $t->boolean('require_provider_signature')->default(true);
            $t->boolean('require_client_signature')->default(true);

            // Période d’application (facultatif)
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();

            // Activation
            $t->boolean('is_active')->default(true);

            $t->timestamps();
            $t->softDeletes();

            // Index & contraintes
            $t->unique(['code', 'version', 'locale']);   // versioning par locale
            $t->index(['sub_category_id']);
            $t->index(['created_by']);
            $t->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contract_templates');
    }
}
