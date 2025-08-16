<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // FK : l’utilisateur qui laisse l’avis (client)
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');

            // FK : le prestataire évalué
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');

            // FK : service concerné
            $table->foreignId('service_offering_id')->constrained('service_offerings')->onDelete('cascade');

            // FK : réservation liée (facultatif mais recommandé)
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');

            // Contenu
            $table->unsignedTinyInteger('rating')->comment('Note de 1 à 5');
            $table->text('comment')->nullable();

            // Modération
            $table->boolean('is_approved')->default(true);

            // Statistiques
            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['provider_id', 'rating']);
            $table->index(['service_offering_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
