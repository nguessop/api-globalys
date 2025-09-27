<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id');

            $table->text('body');
            $table->dateTime('scheduled_at');
            $table->json('attachment_ids')->nullable();
            $table->string('status', 16)->default('scheduled'); // scheduled|sent|canceled|failed

            $table->timestamps();

            $table->index(['thread_id']);
            $table->index(['status', 'scheduled_at']);

            $table->foreign('thread_id')->references('id')->on('message_threads')->onDelete('cascade');
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
        Schema::dropIfExists('scheduled_messages');
    }
}
