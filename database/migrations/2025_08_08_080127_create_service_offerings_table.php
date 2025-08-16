<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();

            // FK
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');

            // Contenu
            $table->string('title');
            $table->text('description')->nullable();

            // Tarifs
            $table->decimal('price_amount', 12, 2);
            $table->enum('price_unit', ['hour','service','km','course','kg','jour'])->default('service');
            $table->string('currency', 3)->default('XAF');          // XAF/EUR/...
            $table->decimal('tax_rate', 5, 2)->nullable();          // ex: 19.25
            $table->decimal('discount_amount', 12, 2)->nullable();  // ou discount_percent

            // Zone d'intervention
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable(); // ISO-3166-1 alpha-2 (CM, FR, ...)
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('coverage_km')->nullable();
            $table->boolean('on_site')->default(true);   // chez le client
            $table->boolean('at_provider')->default(false); // chez le prestataire
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Délais / SLA & capacité
            $table->unsignedSmallInteger('min_delay_hours')->nullable();
            $table->unsignedSmallInteger('max_delay_hours')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable(); // durée typique d’une prestation
            $table->unsignedSmallInteger('capacity')->default(1);    // nb de clients par créneau (si besoin)

            // Statut / publication / modération
            $table->enum('status', ['draft','active','paused','archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('featured')->default(false);      // mise en avant
            $table->boolean('is_verified')->default(false);   // vérification KYC/licence interne
            $table->string('status_reason')->nullable();      // raison d’un pause/refus

            // Dérivés / perfs
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('bookings_count')->default(0);
            $table->unsignedBigInteger('favorites_count')->default(0);

            // Médias / extra
            $table->json('attachments')->nullable(); // photos, certificats, PDF…
            $table->json('metadata')->nullable();    // extensible sans migration

            $table->timestamps();
            $table->softDeletes();

            // Index & contraintes
            $table->unique(['provider_id','sub_category_id','title']);
            $table->index(['sub_category_id','status','city']);
            $table->index(['provider_id','status']);
            $table->index('price_amount');
            $table->index(['lat','lng']);
            $table->index('published_at');
            $table->index(['featured','is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offerings');
    }
};
