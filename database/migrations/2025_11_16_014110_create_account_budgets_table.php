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
        Schema::create('account_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_user_id')->constrained()->onDelete('cascade');

            // Budget configuration
            $table->enum('budget_period', ['daily', 'weekly', 'monthly', 'yearly', 'unlimited'])
                ->default('monthly');
            $table->decimal('budget_limit', 15, 3)->default(0);
            $table->decimal('budget_used', 15, 3)->default(0);

            // Period tracking
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('last_reset_at')->nullable();

            // Alerts
            $table->boolean('alert_enabled')->default(true);
            $table->decimal('alert_threshold', 5, 2)->default(80.00)
                ->comment('Alert when % of budget used');
            $table->boolean('alert_sent')->default(false);

            // Auto-reset
            $table->boolean('auto_reset')->default(true);

            $table->timestamps();

            $table->index(['account_user_id', 'budget_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_budgets');
    }
};
