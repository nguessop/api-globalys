<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique(); // ex: electricity, plumbing, ...
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('providers_count')->default(0);
            $table->string('average_price')->nullable(); // ex: "45€/h"
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
