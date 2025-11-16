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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // INV-2025-00001
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();

            // Amounts
            $table->decimal('subtotal', 15, 3);
            $table->decimal('tax', 15, 3);
            $table->decimal('total', 15, 3);
            $table->decimal('paid_amount', 15, 3)->default(0);

            // Payment terms
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90']);
            $table->decimal('early_payment_discount', 5, 2)->default(0);
            $table->integer('early_payment_days')->default(0);
            $table->date('early_payment_deadline')->nullable();

            // Status
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'cancelled'])
                ->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('due_date');
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
