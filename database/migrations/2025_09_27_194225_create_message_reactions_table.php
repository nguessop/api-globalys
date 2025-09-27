<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageReactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('emoji', 16); // ex: 👍, ❤️

            $table->timestamps();

            // Un utilisateur ne peut avoir qu'UNE réaction par message (WhatsApp-like)
            $table->unique(['message_id', 'user_id']);

            $table->index(['message_id', 'emoji']);

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_reactions');
    }
}
