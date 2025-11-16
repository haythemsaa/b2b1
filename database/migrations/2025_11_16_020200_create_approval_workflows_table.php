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
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

            // Workflow configuration
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('workflow_type', [
                'order_approval',
                'rfq_approval',
                'budget_approval',
                'invoice_payment_approval',
                'account_creation_approval'
            ]);

            // Trigger conditions
            $table->json('trigger_conditions')->nullable()->comment('Conditions that trigger this workflow');
            $table->decimal('amount_threshold', 15, 3)->nullable()->comment('Trigger if amount exceeds this');
            $table->integer('quantity_threshold')->nullable()->comment('Trigger if quantity exceeds this');

            // Approval chain
            $table->json('approval_chain')->comment('Array of approver IDs in order');
            $table->boolean('requires_all_approvers')->default(false)->comment('All must approve vs any approver');
            $table->integer('approval_timeout_hours')->nullable()->comment('Auto-reject after X hours');

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Higher priority workflows checked first');

            $table->timestamps();

            $table->index(['vendor_id', 'workflow_type', 'is_active']);
            $table->index(['vendor_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
