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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');

            // Preference type
            $table->string('preference_key');
            $table->json('preference_value');

            // Metadata
            $table->string('preference_category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_generated')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'vendor_id', 'preference_key']);
            $table->index(['user_id', 'preference_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
