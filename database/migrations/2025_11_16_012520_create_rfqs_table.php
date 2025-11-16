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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique(); // RFQ-2025-00001
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // RFQ details
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_budget', 15, 3)->nullable();
            $table->date('required_delivery_date')->nullable();

            // Status tracking
            $table->enum('status', [
                'draft',        // Being created
                'submitted',    // Submitted to admin
                'quoted',       // Admin has sent quote
                'negotiating',  // In negotiation
                'accepted',     // Vendor accepted quote
                'rejected',     // Vendor rejected quote
                'expired',      // RFQ expired
                'converted',    // Converted to order
            ])->default('draft');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Expiration
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('rfq_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
