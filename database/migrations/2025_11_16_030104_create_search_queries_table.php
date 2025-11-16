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
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Search details
            $table->string('query_text');
            $table->string('normalized_query')->index();
            $table->json('filters')->nullable();
            $table->integer('results_count')->default(0);

            // Search behavior
            $table->boolean('has_results')->default(true);
            $table->integer('clicked_result_position')->nullable();
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->boolean('resulted_in_order')->default(false);

            // Context
            $table->string('search_context', 50)->nullable()->comment('catalog, rfq, reorder, etc');
            $table->string('session_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Performance
            $table->integer('response_time_ms')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['normalized_query', 'has_results']);
            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
