<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meetings', function (Blueprint $t) {
            $t->id();

            // ⬅️ On référence la sous-catégorie (remplace service_id)
            $t->foreignId('sub_category_id')
                ->nullable()
                ->constrained('sub_categories') // change si ton nom diffère
                ->nullOnDelete();

            $t->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            $t->string('subject')->nullable();

            // Ajout du cas "assistance contractuelle"
            $t->enum('purpose', ['discovery', 'pre_contract', 'contract_assistance'])
                ->default('pre_contract');

            $t->enum('location_type', ['online','onsite'])->default('online');
            $t->string('location')->nullable();      // URL visio ou adresse
            $t->string('timezone')->default('UTC');
            $t->unsignedInteger('duration_minutes')->default(30);

            $t->enum('status', ['proposed','scheduled','cancelled','completed'])
                ->default('proposed');

            // Attention: assure-toi que la table meeting_slots est migrée AVANT celle-ci.
            $t->foreignId('selected_slot_id')
                ->nullable()
                ->constrained('meeting_slots')
                ->nullOnDelete();

            $t->timestamps();

            // Index utiles
            $t->index(['provider_id', 'status']);
            $t->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meetings');
    }
}
