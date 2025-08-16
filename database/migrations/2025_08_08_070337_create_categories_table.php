<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // ex: technical, beauty, ...
            $table->string('name');
            $table->string('icon')->nullable(); // nom/clé d'icône côté front
            $table->string('color_class')->nullable(); // ex: from-blue-500 to-blue-600
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
