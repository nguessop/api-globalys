<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('service_offering_id');
            $table->unsignedBigInteger('customer_id'); // utilisateur authentifié (demandeur)
            $table->unsignedBigInteger('provider_id'); // propriétaire du service
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['service_offering_id', 'customer_id']); // 1 fil par client & service
            $table->index(['provider_id']);

            $table->foreign('service_offering_id')->references('id')->on('service_offerings')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('provider_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_threads');
    }
}


