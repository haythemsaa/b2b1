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
        Schema::create('order_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Prediction
            $table->date('predicted_order_date');
            $table->integer('predicted_quantity');
            $table->decimal('predicted_amount', 15, 3);
            $table->decimal('confidence_score', 5, 4)->comment('0-1 confidence score');

            // Historical patterns
            $table->decimal('average_order_interval_days', 10, 2)->nullable();
            $table->decimal('average_quantity', 10, 2)->nullable();
            $table->decimal('quantity_std_dev', 10, 2')->nullable();
            $table->integer('historical_order_count')->default(0);

            // Seasonality
            $table->boolean('has_seasonal_pattern')->default(false);
            $table->json('seasonal_factors')->nullable();

            // Trend
            $table->enum('trend_direction', ['increasing', 'stable', 'decreasing'])->default('stable');
            $table->decimal('trend_slope', 10, 4)->nullable();

            // Status
            $table->enum('status', ['pending', 'ordered', 'skipped', 'expired'])->default('pending');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'predicted_order_date', 'status']);
            $table->index(['product_id', 'status', 'confidence_score']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_predictions');
    }
};
