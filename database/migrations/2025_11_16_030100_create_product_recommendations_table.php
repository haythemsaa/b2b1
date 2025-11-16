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
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('recommended_product_id')->constrained('products')->onDelete('cascade');

            // Recommendation type
            $table->enum('recommendation_type', [
                'frequently_bought_together',
                'similar_products',
                'trending',
                'seasonal',
                'personalized',
                'complementary',
                'alternative'
            ]);

            // Scoring
            $table->decimal('confidence_score', 5, 4)->comment('0-1 confidence score');
            $table->integer('support_count')->default(0)->comment('Number of co-occurrences');
            $table->decimal('lift', 10, 4)->default(1)->comment('Lift metric for association');

            // Metadata
            $table->json('recommendation_data')->nullable()->comment('Additional recommendation metadata');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_calculated_at')->useCurrent();

            $table->timestamps();

            $table->unique(['vendor_id', 'product_id', 'recommended_product_id', 'recommendation_type'], 'unique_recommendation');
            $table->index(['product_id', 'recommendation_type', 'confidence_score']);
            $table->index(['vendor_id', 'is_active', 'confidence_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
    }
};
