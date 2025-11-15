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
        Schema::create('product_vendor_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'vendor_group_id']);
            $table->index(['product_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_vendor_visibility');
    }
};
