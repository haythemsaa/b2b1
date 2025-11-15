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
        Schema::create('product_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('price', 10, 3);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'vendor_group_id', 'min_quantity']);
            $table->index(['product_id', 'vendor_id', 'min_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_pricing');
    }
};
