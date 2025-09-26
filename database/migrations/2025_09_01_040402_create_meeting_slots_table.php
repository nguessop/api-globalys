<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingSlotsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('meeting_slots', function (Blueprint $t) {
            $t->id();

            // Liens
            $t->foreignId('meeting_id')->constrained()->cascadeOnDelete();   // -> meetings.id
            $t->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete(); // utilisateur proposant le créneau

            // Horaires
            $t->dateTime('start_at');
            $t->dateTime('end_at');

            // Métadonnées de lieu
            $t->string('timezone', 64)->default('UTC');        // IANA (ex: Europe/Paris)
            $t->string('location', 512)->nullable();           // URL visio ou adresse physique

            // Statut du créneau
            $t->enum('status', ['proposed','accepted','declined','cancelled','expired'])->default('proposed');

            // Notes libres (raison d’un refus, précision de lieu, etc.)
            $t->text('notes')->nullable();

            $t->timestamps();

            /* ------------------------------- Indexes ------------------------------- */
            $t->index(['meeting_id', 'start_at']);     // recherche par réunion + chronologie
            $t->index(['status']);                     // filtres rapides par statut
            $t->index(['start_at']);                   // prochaines dispos
            $t->unique(['meeting_id', 'start_at', 'end_at']); // évite les doublons exacts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('meeting_slots');
    }
}
