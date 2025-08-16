<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_offering_id')->constrained()->onDelete('cascade');
            $t->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $t->unsignedInteger('quantity')->default(1);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('total_price', 12, 2);
            $t->enum('status',['pending','confirmed','in_progress','completed','cancelled'])->default('pending');
            $t->text('notes_client')->nullable();
            $t->text('notes_provider')->nullable();
            $t->string('cancellation_reason')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            // new adding

            $t->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $t->string('code')->unique();
            $t->timestamp('start_at')->nullable();
            $t->timestamp('end_at')->nullable();
            $t->string('city')->nullable();
            $t->string('address')->nullable();

            $t->string('currency', 10)->default('XAF');
           // $t->decimal('quantity', 10, 2)->default(1);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);

            // $t->enum('status', ['pending','confirmed','in_progress','completed','cancelled'])->default('pending');
            $t->enum('payment_status', ['unpaid','paid','refunded','partial'])->default('unpaid');

            $t->timestamps();

            $t->index(['client_id','provider_id','status']);
            $t->index(['service_offering_id','start_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
