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
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // Rule configuration
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', [
                'auto_reorder',
                'price_alert',
                'stock_alert',
                'approval_routing',
                'document_processing',
                'notification_routing',
                'budget_management',
                'custom'
            ]);

            // Trigger conditions
            $table->json('trigger_conditions')->comment('Conditions that trigger this rule');
            $table->enum('trigger_frequency', ['realtime', 'hourly', 'daily', 'weekly', 'monthly'])->default('realtime');

            // Actions
            $table->json('actions')->comment('Actions to execute when triggered');
            $table->json('action_parameters')->nullable();

            // Constraints
            $table->decimal('min_threshold', 15, 3)->nullable();
            $table->decimal('max_threshold', 15, 3)->nullable();
            $table->json('constraints')->nullable();

            // Status and execution
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();

            // Limits
            $table->integer('max_executions_per_day')->nullable();
            $table->integer('executions_today')->default(0);
            $table->date('execution_date')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'rule_type', 'is_active']);
            $table->index(['is_active', 'next_execution_at']);
            $table->index(['vendor_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
