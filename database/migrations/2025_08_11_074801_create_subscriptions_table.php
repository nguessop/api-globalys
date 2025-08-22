<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Utilisateur abonné
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Plan d'abonnement
            $table->string('plan_name'); // ex: Starter, Standard, Silver...
            $table->string('plan_code')->nullable(); // code interne pour le plan

            // Détails de facturation
            $table->decimal('price', 10, 2); // Prix payé
            $table->string('currency', 10)->default('XAF'); // Devise (ex: XAF, EUR, USD...)

            // Dates d'abonnement
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Statut : actif, expiré, annulé
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');

            // Renouvellement automatique
            $table->boolean('auto_renew')->default(true);

            // Méthode de paiement utilisée (ex: Orange Money, MoMo, Stripe)
            $table->string('payment_method')->nullable();

            // Référence transaction paiement
            $table->string('payment_reference')->nullable();

            $table->enum('commission_type', ['percent', 'fixed'])->default('percent');

            $table->decimal('commission_rate', 5, 2)->nullable();  // ex: 5.00 = 5%

            $table->decimal('commission_fixed', 10, 2)->nullable(); // ex: 500 = 500 XAF

            $table->text('commission_notes')->nullable();

            $table->json('detail')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
