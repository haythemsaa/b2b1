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
        Schema::create('negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negotiation_id')->constrained('price_negotiations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');

            // Message details
            $table->enum('message_type', [
                'offer',
                'counter_offer',
                'acceptance',
                'rejection',
                'question',
                'response',
                'update'
            ]);

            $table->text('message');
            $table->json('offer_details')->nullable()->comment('Structured offer data');
            $table->decimal('offered_price', 15, 3)->nullable();
            $table->json('offered_terms')->nullable();

            // Metadata
            $table->boolean('is_admin_message')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['negotiation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negotiation_messages');
    }
};
