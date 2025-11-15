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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->string('product_sku');
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 3);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 3)->default(0);
            $table->decimal('subtotal', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
