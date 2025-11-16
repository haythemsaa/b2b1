<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    /**
     * Get or create conversation for a vendor
     */
    public function getOrCreateConversation(User $vendor): ChatConversation
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        return ChatConversation::firstOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'is_active' => true,
                'unread_vendor_count' => 0,
                'unread_admin_count' => 0,
            ]
        );
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(
        ChatConversation $conversation,
        User $sender,
        string $message,
        ?array $attachments = null
    ): ChatMessage {
        return DB::transaction(function() use ($conversation, $sender, $message, $attachments) {
            // Create message
            $chatMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message' => $message,
                'attachments' => $attachments,
                'is_read' => false,
            ]);

            // Update conversation metadata
            $isFromAdmin = $sender->isAdmin();
            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Increment unread count for the recipient
            $conversation->incrementUnreadCount($isFromAdmin);

            // Fire event for real-time notifications
            event(new \App\Events\NewChatMessage($chatMessage));

            return $chatMessage->load('sender');
        });
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages(
        ChatConversation $conversation,
        User $user,
        int $perPage = 50,
        ?int $beforeMessageId = null
    ) {
        $query = ChatMessage::where('conversation_id', $conversation->id)
            ->with('sender')
            ->oldest();

        if ($beforeMessageId) {
            $query->where('id', '<', $beforeMessageId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get recent messages for a conversation
     */
    public function getRecentMessages(ChatConversation $conversation, int $limit = 50): Collection
    {
        return ChatMessage::where('conversation_id', $conversation->id)
            ->with('sender')
            ->recent()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Mark conversation as read for a user
     */
    public function markAsRead(ChatConversation $conversation, User $user): void
    {
        if ($user->isVendor()) {
            $conversation->markAsReadForVendor();
        } elseif ($user->isAdmin()) {
            $conversation->markAsReadForAdmin();
        }
    }

    /**
     * Mark specific message as read
     */
    public function markMessageAsRead(ChatMessage $message, User $user): void
    {
        // Only mark as read if the user is not the sender
        if ($message->sender_id !== $user->id && !$message->is_read) {
            $message->markAsRead();

            // Decrement unread count
            $conversation = $message->conversation;
            $isUserAdmin = $user->isAdmin();

            if ($isUserAdmin && $conversation->unread_admin_count > 0) {
                $conversation->decrement('unread_admin_count');
            } elseif (!$isUserAdmin && $conversation->unread_vendor_count > 0) {
                $conversation->decrement('unread_vendor_count');
            }
        }
    }

    /**
     * Get all conversations (admin view)
     */
    public function getAllConversations(array $filters = [])
    {
        $query = ChatConversation::with(['vendor', 'latestMessage.sender'])
            ->recent();

        if (isset($filters['has_unread']) && $filters['has_unread']) {
            $query->withUnreadMessages(true);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('vendor', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('vendorProfile', function($vp) use ($search) {
                      $vp->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Get vendor's conversation
     */
    public function getVendorConversation(User $vendor)
    {
        if (!$vendor->isVendor()) {
            throw new \InvalidArgumentException('User must be a vendor');
        }

        return ChatConversation::where('vendor_id', $vendor->id)
            ->with('latestMessage.sender')
            ->first();
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(User $user): int
    {
        if ($user->isVendor()) {
            $conversation = ChatConversation::where('vendor_id', $user->id)->first();
            return $conversation?->unread_vendor_count ?? 0;
        } elseif ($user->isAdmin()) {
            return ChatConversation::sum('unread_admin_count');
        }

        return 0;
    }

    /**
     * Get conversations with unread messages (admin)
     */
    public function getConversationsWithUnreadMessages(): Collection
    {
        return ChatConversation::withUnreadMessages(true)
            ->with(['vendor', 'latestMessage.sender'])
            ->recent()
            ->get();
    }

    /**
     * Archive/deactivate a conversation
     */
    public function archiveConversation(ChatConversation $conversation): void
    {
        $conversation->update(['is_active' => false]);
    }

    /**
     * Reactivate a conversation
     */
    public function reactivateConversation(ChatConversation $conversation): void
    {
        $conversation->update(['is_active' => true]);
    }

    /**
     * Delete a message (soft delete - mark as deleted)
     */
    public function deleteMessage(ChatMessage $message, User $user): void
    {
        // Only sender or admin can delete
        if ($message->sender_id !== $user->id && !$user->isAdmin()) {
            throw new \Exception('Unauthorized to delete this message');
        }

        $message->delete();
    }

    /**
     * Upload attachment and return path
     */
    public function uploadAttachment($file, User $user): array
    {
        $path = $file->store('chat-attachments/' . $user->id, 'public');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'url' => asset('storage/' . $path),
        ];
    }

    /**
     * Upload multiple attachments
     */
    public function uploadAttachments(array $files, User $user): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $attachments[] = $this->uploadAttachment($file, $user);
        }

        return $attachments;
    }

    /**
     * Get chat statistics for admin
     */
    public function getChatStats(): array
    {
        $totalConversations = ChatConversation::count();
        $activeConversations = ChatConversation::active()->count();
        $conversationsWithUnread = ChatConversation::withUnreadMessages(true)->count();
        $totalMessages = ChatMessage::count();
        $unreadMessages = ChatMessage::unread()->count();

        return [
            'total_conversations' => $totalConversations,
            'active_conversations' => $activeConversations,
            'conversations_with_unread' => $conversationsWithUnread,
            'total_messages' => $totalMessages,
            'unread_messages' => $unreadMessages,
            'total_unread_for_admin' => ChatConversation::sum('unread_admin_count'),
        ];
    }

    /**
     * Search messages in a conversation
     */
    public function searchMessages(ChatConversation $conversation, string $query, int $perPage = 20)
    {
        return ChatMessage::where('conversation_id', $conversation->id)
            ->where('message', 'like', "%{$query}%")
            ->with('sender')
            ->recent()
            ->paginate($perPage);
    }

    /**
     * Get messages by date range
     */
    public function getMessagesByDateRange(
        ChatConversation $conversation,
        string $fromDate,
        string $toDate
    ): Collection {
        return ChatMessage::where('conversation_id', $conversation->id)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->with('sender')
            ->oldest()
            ->get();
    }

    /**
     * Check if user can access conversation
     */
    public function canAccessConversation(ChatConversation $conversation, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor() && $conversation->vendor_id === $user->id) {
            return true;
        }

        return false;
    }
}
