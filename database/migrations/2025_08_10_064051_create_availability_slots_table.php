<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();

            // Liens
            $table->foreignId('service_offering_id')->constrained('service_offerings')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');

            // Fenêtre de disponibilité (créneau ponctuel)
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone', 64)->nullable(); // ex: "Africa/Douala"

            // Capacité & remplissage
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);

            // Override tarifaire (facultatif)
            $table->decimal('price_override', 12, 2)->nullable();
            $table->string('currency', 3)->nullable(); // si tu veux forcer une devise pour ce créneau

            // Récurrence (optionnelle)
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable(); // ex: RRULE iCal (FREQ=WEEKLY;BYDAY=MO,TU;...)

            // Statut
            $table->enum('status', ['available','blocked','full','cancelled'])->default('available');
            $table->string('notes')->nullable();

            // Template/parent (si tu veux des séries récurrentes avec exceptions)
            $table->foreignId('parent_id')->nullable()->constrained('availability_slots')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Index usuels
            $table->index(['provider_id', 'start_at']);
            $table->index(['service_offering_id', 'start_at']);
            $table->index(['status', 'start_at']);
            $table->index('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
