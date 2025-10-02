<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Relations hiérarchiques
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            // si le parent est supprimé, l'enfant garde NULL

            // Infos principales
            $table->string('slug')->unique(); // ex: technical, beauty, ...
            $table->string('name'); // Nom lisible
            $table->string('icon')->nullable(); // clé d'icône pour le front
            $table->string('color_class')->nullable(); // ex: from-blue-500 to-blue-600
            $table->text('description')->nullable();

            // Timestamps
            $table->timestamps();

            // Index
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
