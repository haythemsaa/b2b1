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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');

            // Notifiable polymorphic relationship
            $table->morphs('notifiable'); // notifiable_id, notifiable_type

            // Notification details
            $table->enum('notification_type', [
                'order_status_change',
                'approval_required',
                'approval_approved',
                'approval_rejected',
                'budget_alert',
                'budget_exceeded',
                'negotiation_received',
                'negotiation_accepted',
                'negotiation_rejected',
                'rfq_quote_received',
                'invoice_due_soon',
                'invoice_overdue',
                'document_shared',
                'document_expiring',
                'chat_message',
                'system_announcement',
                'account_suspended',
                'low_stock_alert'
            ]);

            $table->string('title');
            $table->text('message');
            $table->json('action_data')->nullable()->comment('Data for action buttons');
            $table->string('action_url')->nullable()->comment('URL to navigate when clicked');

            // Priority
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            // Channels
            $table->boolean('sent_in_app')->default(true);
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_sms')->default(false);
            $table->boolean('sent_push')->default(false);

            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();

            // Grouping
            $table->string('group_key')->nullable()->comment('Key for grouping related notifications');
            $table->foreignId('parent_notification_id')->nullable()->constrained('notifications')->onDelete('cascade');

            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['vendor_id', 'notification_type', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['group_key', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
