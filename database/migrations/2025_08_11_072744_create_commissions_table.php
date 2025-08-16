<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');

            // Base de calcul
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('XAF');

            // Règle appliquée (snapshot)
            $table->enum('commission_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('commission_rate', 5, 2)->nullable();   // 15.25% max
            $table->decimal('commission_fixed', 12, 2)->nullable(); // si fixe

            // Montant calculé
            $table->decimal('amount', 12, 2)->default(0);

            // Cycle de vie
            $table->enum('status', [
                'pending',
                'captured',
                'settled',
                'refunded',
                'cancelled'
            ])->default('pending');

            $table->timestamp('captured_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Références & notes
            $table->string('external_reference')->nullable(); // ex: ID transaction PSP
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Index utiles
            $table->index(['provider_id', 'status']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
}
