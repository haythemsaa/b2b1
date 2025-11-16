<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    /**
     * Determine if the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the conversation.
     */
    public function view(User $user, ChatConversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $conversation->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can send messages in the conversation.
     */
    public function sendMessage(User $user, ChatConversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $conversation->vendor_id === $user->id && $conversation->is_active;
        }

        return false;
    }

    /**
     * Determine if the user can archive the conversation.
     */
    public function archive(User $user, ChatConversation $conversation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can reactivate the conversation.
     */
    public function reactivate(User $user, ChatConversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
