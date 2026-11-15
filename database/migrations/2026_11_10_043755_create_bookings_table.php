<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Users -> courts (Foreign key)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            
            // Booking schedule
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');

            // Payment information
            $table->decimal('total_price', 12, 2);
            $table->string('payment_status')->default('unpaid');

            // Booking status
            $table->string('status')->default('confirmed');

            // Add notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
