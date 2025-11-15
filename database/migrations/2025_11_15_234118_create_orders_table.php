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
            $table->string('order_number')->unique();
            $table->foreignId('vendor_id')->constrained('users');
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ])->default('pending');
            $table->decimal('subtotal', 12, 3);
            $table->decimal('discount_amount', 12, 3)->default(0);
            $table->decimal('tax_amount', 12, 3)->default(0);
            $table->decimal('shipping_cost', 10, 3)->default(0);
            $table->decimal('total', 12, 3);
            $table->text('shipping_address');
            $table->text('vendor_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->timestamps();
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
