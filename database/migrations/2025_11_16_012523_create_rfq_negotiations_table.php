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
        Schema::create('rfq_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Message content
            $table->text('message');

            // Counter offer details
            $table->boolean('is_counter_offer')->default(false);
            $table->decimal('proposed_price', 15, 3)->nullable();
            $table->string('proposed_terms')->nullable();
            $table->integer('proposed_delivery_days')->nullable();

            // Tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['rfq_id', 'created_at']);
            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_negotiations');
    }
};
