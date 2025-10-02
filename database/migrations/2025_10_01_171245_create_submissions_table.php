<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            // Relation : quelle sous-catégorie (donc quel service précis)
            $table->foreignId('sub_category_id')->constrained('sub_categories')->onDelete('cascade');

            // Relation : qui a soumis (client, utilisateur connecté)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Stocker toutes les réponses du formulaire
            $table->json('data');
            // ex: {"surface":120,"budget":"1.000.000 FCFA","delai":"3 mois"}

            // Statut de traitement (workflow interne)
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->timestamps();

            // Index pratiques
            $table->index(['sub_category_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submissions');
    }
}
