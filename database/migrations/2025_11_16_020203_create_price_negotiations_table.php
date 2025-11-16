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
        Schema::create('price_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');

            // Negotiation details
            $table->enum('negotiation_type', [
                'price_reduction',
                'volume_discount',
                'payment_terms',
                'delivery_terms',
                'product_specification'
            ]);

            // Current offer
            $table->decimal('original_price', 15, 3);
            $table->decimal('proposed_price', 15, 3);
            $table->decimal('final_price', 15, 3)->nullable();
            $table->json('terms')->nullable()->comment('Additional terms being negotiated');

            // Status
            $table->enum('status', [
                'active',
                'accepted',
                'rejected',
                'countered',
                'expired',
                'withdrawn'
            ])->default('active');

            // Rounds
            $table->integer('round_number')->default(1);
            $table->integer('max_rounds')->default(5);
            $table->timestamp('expires_at')->nullable();

            // Decision
            $table->foreignId('decided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['rfq_id', 'status']);
            $table->index(['vendor_id', 'status', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_negotiations');
    }
};
