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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with')->constrained('users')->onDelete('cascade');

            // Share settings
            $table->enum('permission', ['view', 'download', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->text('share_message')->nullable();

            // Access tracking
            $table->boolean('is_accessed')->default(false);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);

            $table->timestamps();

            $table->unique(['document_id', 'shared_with']);
            $table->index(['shared_with', 'is_accessed']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
