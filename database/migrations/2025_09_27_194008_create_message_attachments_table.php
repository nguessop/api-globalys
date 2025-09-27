<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('message_id')->nullable(); // lié après envoi
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->string('path')->nullable();
            $table->string('url', 2048)->nullable();

            $table->string('name');
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->string('kind', 16)->default('file'); // image|file|video|audio
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();

            $table->timestamps();

            // Index
            $table->index(['thread_id', 'message_id']);
            $table->index(['uploaded_by']);

            // FKs
            $table->foreign('thread_id')->references('id')->on('message_threads')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->nullOnDelete(); // si le message est supprimé, on détache
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_attachments');
    }
}
