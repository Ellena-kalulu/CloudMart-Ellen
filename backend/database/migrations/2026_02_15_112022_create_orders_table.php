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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique(); // CLM-YYYYMMDD-XXXX
            $table->decimal('total_amount', 10, 2);
            $table->text('delivery_address');
            $table->string('delivery_location'); // e.g., "Mzuzu University", "Luwinga"
            $table->string('phone');
            $table->decimal('latitude', 10, 8); // For location validation
            $table->decimal('longitude', 11, 8);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'processing', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('order_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
