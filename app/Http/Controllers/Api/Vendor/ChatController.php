<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Get vendor's conversation
     */
    public function index(Request $request)
    {
        $conversation = $this->chatService->getVendorConversation($request->user());

        if (!$conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
            ]);
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'unread_count' => $conversation->unread_vendor_count,
                'last_message_at' => $conversation->last_message_at,
            ],
        ]);
    }

    /**
     * Get messages
     */
    public function messages(Request $request)
    {
        $conversation = $this->chatService->getOrCreateConversation($request->user());
        $perPage = $request->input('per_page', 50);
        $beforeMessageId = $request->input('before_message_id');

        $messages = $this->chatService->getMessages($conversation, $request->user(), $perPage, $beforeMessageId);

        return response()->json($messages);
    }

    /**
     * Get recent messages
     */
    public function recent(Request $request)
    {
        $conversation = $this->chatService->getOrCreateConversation($request->user());
        $limit = $request->input('limit', 50);

        $messages = $this->chatService->getRecentMessages($conversation, $limit);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages->map(fn($msg) => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender->name,
                'sender_role' => $msg->sender->role,
                'message' => $msg->message,
                'attachments' => $msg->attachments,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at,
            ]),
        ]);
    }

    /**
     * Send message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        try {
            $conversation = $this->chatService->getOrCreateConversation($request->user());

            // Handle attachments
            $attachments = null;
            if ($request->hasFile('attachments')) {
                $attachments = $this->chatService->uploadAttachments(
                    $request->file('attachments'),
                    $request->user()
                );
            }

            $message = $this->chatService->sendMessage(
                $conversation,
                $request->user(),
                $validated['message'],
                $attachments
            );

            return response()->json([
                'message' => 'Message envoyé avec succès',
                'data' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'message' => $message->message,
                    'attachments' => $message->attachments,
                    'created_at' => $message->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Request $request)
    {
        $conversation = $this->chatService->getVendorConversation($request->user());

        if ($conversation) {
            $this->chatService->markAsRead($conversation, $request->user());
        }

        return response()->json([
            'message' => 'Messages marqués comme lus',
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request)
    {
        $count = $this->chatService->getUnreadCount($request->user());

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
