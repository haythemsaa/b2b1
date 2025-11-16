<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Get all conversations
     */
    public function index(Request $request)
    {
        $filters = $request->only(['has_unread', 'is_active', 'search']);
        $perPage = $request->input('per_page', 20);

        $conversations = $this->chatService
            ->getAllConversations($filters)
            ->paginate($perPage);

        $conversations->getCollection()->transform(function($conversation) {
            return [
                'id' => $conversation->id,
                'vendor' => [
                    'id' => $conversation->vendor->id,
                    'name' => $conversation->vendor->name,
                    'email' => $conversation->vendor->email,
                    'company_name' => $conversation->vendor->vendorProfile?->company_name,
                ],
                'unread_count' => $conversation->unread_admin_count,
                'last_message_at' => $conversation->last_message_at,
                'is_active' => $conversation->is_active,
                'latest_message' => $conversation->latestMessage ? [
                    'message' => $conversation->latestMessage->message,
                    'sender_name' => $conversation->latestMessage->sender->name,
                    'sender_role' => $conversation->latestMessage->sender->role,
                    'created_at' => $conversation->latestMessage->created_at,
                ] : null,
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get single conversation
     */
    public function show(ChatConversation $conversation)
    {
        $conversation->load(['vendor.vendorProfile', 'latestMessage.sender']);

        return response()->json([
            'id' => $conversation->id,
            'vendor' => [
                'id' => $conversation->vendor->id,
                'name' => $conversation->vendor->name,
                'email' => $conversation->vendor->email,
                'company_name' => $conversation->vendor->vendorProfile?->company_name,
            ],
            'unread_count' => $conversation->unread_admin_count,
            'last_message_at' => $conversation->last_message_at,
            'is_active' => $conversation->is_active,
        ]);
    }

    /**
     * Get conversation messages
     */
    public function messages(Request $request, ChatConversation $conversation)
    {
        $perPage = $request->input('per_page', 50);
        $beforeMessageId = $request->input('before_message_id');

        $messages = $this->chatService->getMessages($conversation, $request->user(), $perPage, $beforeMessageId);

        return response()->json($messages);
    }

    /**
     * Get recent messages
     */
    public function recent(Request $request, ChatConversation $conversation)
    {
        $limit = $request->input('limit', 50);
        $messages = $this->chatService->getRecentMessages($conversation, $limit);

        return response()->json(
            $messages->map(fn($msg) => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender->name,
                'sender_role' => $msg->sender->role,
                'message' => $msg->message,
                'attachments' => $msg->attachments,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at,
            ])
        );
    }

    /**
     * Send message
     */
    public function send(Request $request, ChatConversation $conversation)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240',
        ]);

        try {
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
    public function markAsRead(Request $request, ChatConversation $conversation)
    {
        $this->chatService->markAsRead($conversation, $request->user());

        return response()->json([
            'message' => 'Messages marqués comme lus',
        ]);
    }

    /**
     * Archive conversation
     */
    public function archive(ChatConversation $conversation)
    {
        $this->chatService->archiveConversation($conversation);

        return response()->json([
            'message' => 'Conversation archivée avec succès',
        ]);
    }

    /**
     * Reactivate conversation
     */
    public function reactivate(ChatConversation $conversation)
    {
        $this->chatService->reactivateConversation($conversation);

        return response()->json([
            'message' => 'Conversation réactivée avec succès',
        ]);
    }

    /**
     * Get chat statistics
     */
    public function stats()
    {
        $stats = $this->chatService->getChatStats();

        return response()->json($stats);
    }

    /**
     * Get conversations with unread messages
     */
    public function unread()
    {
        $conversations = $this->chatService->getConversationsWithUnreadMessages();

        return response()->json(
            $conversations->map(fn($conv) => [
                'id' => $conv->id,
                'vendor' => [
                    'id' => $conv->vendor->id,
                    'name' => $conv->vendor->name,
                    'company_name' => $conv->vendor->vendorProfile?->company_name,
                ],
                'unread_count' => $conv->unread_admin_count,
                'last_message_at' => $conv->last_message_at,
            ])
        );
    }
}
