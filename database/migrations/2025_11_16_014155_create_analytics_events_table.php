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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Event details
            $table->string('event_type', 50); // page_view, product_view, order_created, etc
            $table->string('event_category', 50)->nullable(); // products, orders, etc
            $table->string('event_action', 100)->nullable(); // view, create, update, delete
            $table->string('event_label')->nullable();

            // Event data
            $table->json('event_data')->nullable(); // Additional context
            $table->decimal('event_value', 15, 3)->nullable(); // Monetary value if applicable

            // Context
            $table->string('session_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('referrer')->nullable();
            $table->string('url')->nullable();

            // Timing
            $table->timestamp('event_time')->useCurrent();

            $table->index(['vendor_id', 'event_type', 'event_time']);
            $table->index(['event_type', 'event_time']);
            $table->index('session_id');
            $table->index('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
