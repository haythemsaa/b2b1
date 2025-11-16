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
        Schema::create('vendor_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // Time period
            $table->date('metric_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('daily');

            // Order metrics
            $table->integer('orders_count')->default(0);
            $table->decimal('orders_total', 15, 3)->default(0);
            $table->decimal('orders_avg', 15, 3')->default(0);
            $table->integer('orders_completed')->default(0);
            $table->integer('orders_cancelled')->default(0);

            // Product metrics
            $table->integer('products_viewed')->default(0);
            $table->integer('products_added_to_cart')->default(0);
            $table->integer('unique_products_ordered')->default(0);

            // RFQ metrics
            $table->integer('rfqs_submitted')->default(0);
            $table->integer('rfqs_quoted')->default(0);
            $table->integer('rfqs_accepted')->default(0);
            $table->decimal('rfqs_conversion_rate', 5, 2)->default(0);

            // Invoice metrics
            $table->integer('invoices_created')->default(0);
            $table->integer('invoices_paid')->default(0);
            $table->decimal('invoices_total', 15, 3)->default(0);
            $table->decimal('invoices_paid_total', 15, 3)->default(0);

            // Engagement metrics
            $table->integer('login_count')->default(0);
            $table->integer('page_views')->default(0);
            $table->integer('chat_messages')->default(0);
            $table->integer('active_sessions')->default(0);

            // Performance metrics
            $table->decimal('avg_order_processing_time', 10, 2)->default(0)->comment('in minutes');
            $table->decimal('customer_satisfaction', 3, 2)->nullable()->comment('0-5 rating');

            $table->timestamps();

            $table->unique(['vendor_id', 'metric_date', 'period_type']);
            $table->index(['vendor_id', 'period_type', 'metric_date']);
            $table->index('metric_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_metrics');
    }
};
