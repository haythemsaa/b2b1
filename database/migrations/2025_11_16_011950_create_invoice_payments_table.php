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
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payment_number')->unique(); // PAY-2025-00001

            // Payment details
            $table->date('payment_date');
            $table->decimal('amount', 15, 3);
            $table->enum('payment_method', ['bank_transfer', 'check', 'cash', 'card', 'other'])
                ->default('bank_transfer');

            // Transaction tracking
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['invoice_id', 'payment_date']);
            $table->index('payment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
