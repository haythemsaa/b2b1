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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Notification type
            $table->string('notification_type');

            // Channel preferences
            $table->boolean('enabled')->default(true);
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('push')->default(false);

            // Frequency settings
            $table->enum('frequency', ['instant', 'hourly', 'daily', 'weekly'])->default('instant');
            $table->json('quiet_hours')->nullable()->comment('Hours when notifications are muted');

            $table->timestamps();

            $table->unique(['user_id', 'notification_type']);
            $table->index(['user_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
