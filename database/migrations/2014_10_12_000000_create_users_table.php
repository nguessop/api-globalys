<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Champs de base
            $table->string('first_name');         // longueur par défaut = 191 (cf. AppServiceProvider)
            $table->string('last_name');
            $table->string('email')->unique();    // restera à 191 → unique OK en utf8mb4
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->string('preferred_language')->default('français');

            // ↓↓↓ IMPORTANT : réduire la longueur pour l'index composite ↓↓↓
            $table->string('country', 100)->nullable();
            $table->string('company_city', 140)->nullable();

            // Type de compte : entreprise ou particulier
            $table->enum('account_type', ['entreprise', 'particulier'])->index();

            // Rôle (1 seul)
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();

            // Abonnement (1 seul) → table subscriptions
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            // Champs spécifiques aux entreprises
            $table->string('company_name')->nullable();
            $table->string('sector')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('website', 2048)->nullable(); // pas indexé → 2048 OK
            $table->string('company_logo')->nullable();
            $table->text('company_description')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_size')->nullable();
            $table->string('preferred_contact_method')->nullable();

            // Champs spécifiques aux particuliers
            $table->enum('gender', ['Homme', 'Femme', 'Autre'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('job')->nullable();
            $table->string('personal_address')->nullable();

            // Rôle d’usage (client / prestataire)
            $table->enum('user_type', ['client', 'prestataire'])->nullable()->index();

            // Photo de profil
            $table->string('profile_photo')->nullable();

            // RGPD
            $table->boolean('accepts_terms')->default(false);
            $table->boolean('wants_newsletter')->default(false);

            $table->unsignedBigInteger('profile_views')->default(0);

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            // $table->softDeletes();

            // Index composite (sous 1000 octets en utf8mb4)
            $table->index(['country', 'company_city'], 'users_country_company_city_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
