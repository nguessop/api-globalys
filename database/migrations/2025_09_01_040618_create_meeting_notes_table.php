<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meeting_notes', function (Blueprint $t) {
            $t->id();

            $t->foreignId('meeting_id')
                ->constrained('meetings')
                ->cascadeOnDelete();

            // auteur optionnel (note système possible)
            $t->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // visibilité de la note
            $t->enum('visibility', ['internal', 'client', 'provider', 'both'])
                ->default('internal');

            $t->text('body');

            // métadonnées libres (fichiers, tags, références, etc.)
            $t->json('meta')->nullable();

            $t->timestamps();

            $t->index(['meeting_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meeting_notes');
    }
}
