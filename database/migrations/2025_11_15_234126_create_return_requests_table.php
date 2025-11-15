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
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('vendor_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->enum('type', ['refund', 'exchange', 'credit']);
            $table->text('reason');
            $table->text('vendor_comments')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
