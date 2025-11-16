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
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained('automation_rules')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // Execution details
            $table->json('trigger_data')->nullable()->comment('Data that triggered the rule');
            $table->json('execution_result')->nullable()->comment('Result of execution');
            $table->enum('status', ['success', 'failed', 'partial', 'skipped'])->default('success');
            $table->text('error_message')->nullable();

            // Execution timing
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_ms')->nullable();

            // Actions performed
            $table->json('actions_performed')->nullable();
            $table->integer('actions_count')->default(0);

            $table->timestamps();

            $table->index(['automation_rule_id', 'created_at']);
            $table->index(['vendor_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_executions');
    }
};
