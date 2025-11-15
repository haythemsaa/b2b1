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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name_fr');
            $table->string('name_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_ar')->nullable();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('brand')->nullable();
            $table->string('unit');
            $table->decimal('base_price', 10, 3);
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('order_multiple')->default(1);
            $table->integer('alert_stock_level')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
