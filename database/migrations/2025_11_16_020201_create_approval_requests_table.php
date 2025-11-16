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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');

            // Approvable polymorphic relationship
            $table->morphs('approvable'); // approvable_id, approvable_type

            // Request details
            $table->string('request_type'); // order, rfq, budget_increase, etc.
            $table->text('request_reason')->nullable();
            $table->json('request_data')->nullable()->comment('Original request data for reference');
            $table->decimal('request_amount', 15, 3)->nullable();

            // Approval status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'expired'
            ])->default('pending');

            // Current approval step
            $table->integer('current_step')->default(0)->comment('Current step in approval chain');
            $table->json('approval_history')->nullable()->comment('History of approvals/rejections');

            // Timestamps
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status', 'submitted_at']);
            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['requester_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
