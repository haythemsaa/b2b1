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
        Schema::create('rfq_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->string('quote_number')->unique(); // QUO-2025-00001
            $table->foreignId('quoted_by')->constrained('users')->onDelete('cascade');

            // Quote details
            $table->timestamp('quoted_at');
            $table->timestamp('valid_until')->nullable();

            // Pricing
            $table->decimal('subtotal', 15, 3);
            $table->decimal('tax', 15, 3);
            $table->decimal('total', 15, 3);

            // Terms
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90'])
                ->default('net_30');
            $table->integer('delivery_days')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();

            // Status
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])
                ->default('draft');

            // Acceptance tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['rfq_id', 'status']);
            $table->index('quote_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_quotes');
    }
};
