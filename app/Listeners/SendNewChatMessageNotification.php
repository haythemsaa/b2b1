<?php

namespace App\Listeners;

use App\Events\NewChatMessage;
use App\Notifications\NewChatMessageNotification;
use App\Models\User;

class SendNewChatMessageNotification
{
    /**
     * Handle the event.
     */
    public function handle(NewChatMessage $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;
        $sender = $message->sender;

        // Notify the recipient
        if ($sender->isAdmin()) {
            // Admin sent message, notify vendor
            $conversation->vendor->notify(new NewChatMessageNotification($message));
        } else {
            // Vendor sent message, notify all active admins
            $admins = User::admins()->active()->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewChatMessageNotification($message));
            }
        }
    }
}
