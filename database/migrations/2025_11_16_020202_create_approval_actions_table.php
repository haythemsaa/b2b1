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
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');

            // Action details
            $table->integer('step_number')->comment('Step in the approval chain');
            $table->enum('action', ['approved', 'rejected', 'delegated', 'commented']);
            $table->text('comments')->nullable();
            $table->json('action_data')->nullable()->comment('Additional action metadata');

            // Delegation
            $table->foreignId('delegated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('delegation_reason')->nullable();

            // Metadata
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('action_time')->useCurrent();

            $table->timestamps();

            $table->index(['approval_request_id', 'step_number']);
            $table->index(['approver_id', 'action_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
