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
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Item details
            $table->string('product_sku')->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->integer('quantity_requested');
            $table->string('unit')->default('pcs');

            // Custom specifications
            $table->json('specifications')->nullable();

            // Quote response (filled when quote is sent)
            $table->decimal('quoted_unit_price', 15, 3)->nullable();
            $table->decimal('quoted_subtotal', 15, 3)->nullable();
            $table->text('vendor_notes')->nullable();

            $table->timestamps();

            $table->index('rfq_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};
