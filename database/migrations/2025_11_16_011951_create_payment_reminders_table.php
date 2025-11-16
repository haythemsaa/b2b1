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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

            // Reminder configuration
            $table->enum('reminder_type', [
                'before_due_7',   // 7 days before due date
                'before_due_3',   // 3 days before due date
                'on_due',         // On due date
                'overdue_7',      // 7 days overdue
                'overdue_15',     // 15 days overdue
                'overdue_30',     // 30 days overdue
            ]);

            // Timing
            $table->date('reminder_date');
            $table->integer('days_until_due')->comment('Negative for overdue');
            $table->timestamp('sent_at')->nullable();

            // Status tracking
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['invoice_id', 'reminder_type']);
            $table->index(['status', 'reminder_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
