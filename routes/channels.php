<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\ChatConversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Admin can access all chat channels
Broadcast::channel('chat.{conversationId}', function (User $user, int $conversationId) {
    if ($user->isAdmin()) {
        return true;
    }

    // Vendor can only access their own conversation
    if ($user->isVendor()) {
        $conversation = ChatConversation::find($conversationId);
        return $conversation && $conversation->vendor_id === $user->id;
    }

    return false;
});

// Private user channel for notifications
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

// Admin notification channel
Broadcast::channel('admin-notifications', function (User $user) {
    return $user->isAdmin();
});
