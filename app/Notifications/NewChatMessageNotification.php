<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ChatMessage $message
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_chat_message',
            'conversation_id' => $this->message->conversation_id,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender->name,
            'sender_role' => $this->message->sender->role,
            'message_preview' => substr($this->message->message, 0, 100),
            'has_attachments' => $this->message->hasAttachments(),
            'created_at' => $this->message->created_at,
        ];
    }
}
