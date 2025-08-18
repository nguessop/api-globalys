<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_category_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sub_category_id')
                ->constrained('sub_categories')
                ->onDelete('cascade');

            $table->string('path', 2048);     // chemin ou URL
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['sub_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_category_images');
    }
};
