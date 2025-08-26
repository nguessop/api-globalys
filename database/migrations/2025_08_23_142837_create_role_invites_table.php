<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleInvitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_invites', function (Blueprint $t) {
            $t->id();
            $t->uuid('token')->unique();
            $t->string('role'); // 'prestataire' | 'entreprise'
            $t->string('email')->nullable();       // si tu veux cibler un email
            $t->timestamp('expires_at')->nullable();
            $t->unsignedInteger('max_uses')->default(1);
            $t->unsignedInteger('used_count')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_invites');
    }
}
