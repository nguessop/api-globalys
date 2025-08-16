<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Liens principaux
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');   // payeur (client)
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade'); // prestataire (bénéficiaire)

            // Montants
            $table->decimal('amount', 12, 2);        // montant payé par le client (devise ci-dessous)
            $table->string('currency', 3)->default('XAF');
            $table->decimal('processor_fee', 12, 2)->nullable(); // frais du PSP (si dispo)
            $table->decimal('net_amount', 12, 2)->nullable();     // amount - processor_fee (si utile)

            // Moyen & passerelle
            $table->enum('method', ['card','mobile_money','bank_transfer','cash','wallet'])->default('mobile_money');
            $table->string('gateway')->nullable();      // stripe, cm_momo, orange_money, paypal, bank, ...

            // Références & idempotence
            $table->string('reference')->unique();      // référence transaction (PSP) - unique
            $table->string('idempotency_key')->nullable()->unique(); // pour éviter doublons côté API
            $table->string('external_id')->nullable();  // autre identifiant externe si besoin

            // Statut
            $table->enum('status', ['pending','authorized','succeeded','failed','refunded','cancelled'])
                ->default('pending');

            // Dates de lifecycle
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Détails
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->json('payload')->nullable();   // raw réponse du PSP
            $table->json('metadata')->nullable();  // extensible

            $table->timestamps();
            $table->softDeletes();

            // Index utiles
            $table->index(['booking_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
