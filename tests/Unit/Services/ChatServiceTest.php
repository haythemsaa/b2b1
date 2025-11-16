<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Chat\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChatService $chatService;
    protected User $vendor;
    protected User $admin;
    protected ChatConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chatService = new ChatService();

        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $this->vendor->id]);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->conversation = ChatConversation::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function it_can_get_or_create_conversation_for_vendor()
    {
        $newVendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $newVendor->id]);

        $conversation = $this->chatService->getOrCreateConversation($newVendor);

        $this->assertInstanceOf(ChatConversation::class, $conversation);
        $this->assertEquals($newVendor->id, $conversation->vendor_id);
        $this->assertTrue($conversation->is_active);
    }

    /** @test */
    public function it_throws_exception_when_non_vendor_creates_conversation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must be a vendor');

        $this->chatService->getOrCreateConversation($this->admin);
    }

    /** @test */
    public function it_returns_existing_conversation_if_already_exists()
    {
        $conversation1 = $this->chatService->getOrCreateConversation($this->vendor);
        $conversation2 = $this->chatService->getOrCreateConversation($this->vendor);

        $this->assertEquals($conversation1->id, $conversation2->id);
    }

    /** @test */
    public function it_can_send_message_from_vendor()
    {
        $this->actingAs($this->vendor);

        $message = $this->chatService->sendMessage(
            $this->conversation,
            $this->vendor,
            'Hello, I have a question'
        );

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals('Hello, I have a question', $message->message);
        $this->assertEquals($this->vendor->id, $message->sender_id);
        $this->assertFalse($message->is_read);
    }

    /** @test */
    public function it_can_send_message_from_admin()
    {
        $this->actingAs($this->admin);

        $message = $this->chatService->sendMessage(
            $this->conversation,
            $this->admin,
            'Hello, how can I help you?'
        );

        $this->assertEquals($this->admin->id, $message->sender_id);
    }

    /** @test */
    public function sending_message_updates_conversation_last_message_time()
    {
        $this->actingAs($this->vendor);

        $oldTime = $this->conversation->last_message_at;

        sleep(1);

        $this->chatService->sendMessage(
            $this->conversation,
            $this->vendor,
            'Test message'
        );

        $this->conversation->refresh();
        $this->assertNotEquals($oldTime, $this->conversation->last_message_at);
    }

    /** @test */
    public function sending_message_increments_unread_count_for_recipient()
    {
        $this->actingAs($this->vendor);

        $this->conversation->update([
            'unread_admin_count' => 0,
            'unread_vendor_count' => 0,
        ]);

        // Vendor sends message - should increment admin unread count
        $this->chatService->sendMessage(
            $this->conversation,
            $this->vendor,
            'Message from vendor'
        );

        $this->conversation->refresh();
        $this->assertEquals(1, $this->conversation->unread_admin_count);
        $this->assertEquals(0, $this->conversation->unread_vendor_count);
    }

    /** @test */
    public function admin_message_increments_vendor_unread_count()
    {
        $this->actingAs($this->admin);

        $this->conversation->update([
            'unread_admin_count' => 0,
            'unread_vendor_count' => 0,
        ]);

        $this->chatService->sendMessage(
            $this->conversation,
            $this->admin,
            'Message from admin'
        );

        $this->conversation->refresh();
        $this->assertEquals(0, $this->conversation->unread_admin_count);
        $this->assertEquals(1, $this->conversation->unread_vendor_count);
    }

    /** @test */
    public function it_can_get_messages_for_conversation()
    {
        ChatMessage::factory()->count(5)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $messages = $this->chatService->getMessages($this->conversation, $this->vendor);

        $this->assertEquals(5, $messages->count());
    }

    /** @test */
    public function it_can_get_recent_messages()
    {
        ChatMessage::factory()->count(10)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $messages = $this->chatService->getRecentMessages($this->conversation, 5);

        $this->assertEquals(5, $messages->count());
    }

    /** @test */
    public function it_can_mark_conversation_as_read_for_vendor()
    {
        $this->conversation->update(['unread_vendor_count' => 5]);

        $this->chatService->markAsRead($this->conversation, $this->vendor);

        $this->conversation->refresh();
        $this->assertEquals(0, $this->conversation->unread_vendor_count);
    }

    /** @test */
    public function it_can_mark_conversation_as_read_for_admin()
    {
        $this->conversation->update(['unread_admin_count' => 3]);

        $this->chatService->markAsRead($this->conversation, $this->admin);

        $this->conversation->refresh();
        $this->assertEquals(0, $this->conversation->unread_admin_count);
    }

    /** @test */
    public function it_can_mark_specific_message_as_read()
    {
        $message = ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->admin->id,
            'is_read' => false,
        ]);

        $this->conversation->update(['unread_vendor_count' => 1]);

        $this->chatService->markMessageAsRead($message, $this->vendor);

        $message->refresh();
        $this->assertTrue($message->is_read);
    }

    /** @test */
    public function marking_own_message_as_read_does_nothing()
    {
        $message = ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->vendor->id,
            'is_read' => false,
        ]);

        $this->chatService->markMessageAsRead($message, $this->vendor);

        $message->refresh();
        $this->assertFalse($message->is_read);
    }

    /** @test */
    public function it_can_get_all_conversations_for_admin()
    {
        ChatConversation::factory()->count(5)->create();

        $conversations = $this->chatService->getAllConversations()->get();

        $this->assertGreaterThanOrEqual(5, $conversations->count());
    }

    /** @test */
    public function it_can_filter_conversations_with_unread_messages()
    {
        ChatConversation::factory()->create(['unread_admin_count' => 5]);
        ChatConversation::factory()->create(['unread_admin_count' => 0]);
        ChatConversation::factory()->create(['unread_admin_count' => 3]);

        $conversations = $this->chatService->getAllConversations(['has_unread' => true])->get();

        foreach ($conversations as $conversation) {
            $this->assertGreaterThan(0, $conversation->unread_admin_count);
        }
    }

    /** @test */
    public function it_can_get_vendor_conversation()
    {
        $conversation = $this->chatService->getVendorConversation($this->vendor);

        $this->assertNotNull($conversation);
        $this->assertEquals($this->vendor->id, $conversation->vendor_id);
    }

    /** @test */
    public function it_can_get_unread_count_for_vendor()
    {
        $this->conversation->update(['unread_vendor_count' => 7]);

        $count = $this->chatService->getUnreadCount($this->vendor);

        $this->assertEquals(7, $count);
    }

    /** @test */
    public function it_can_get_unread_count_for_admin()
    {
        ChatConversation::factory()->create(['unread_admin_count' => 2]);
        ChatConversation::factory()->create(['unread_admin_count' => 3]);

        $count = $this->chatService->getUnreadCount($this->admin);

        // Should be sum of all conversations' unread_admin_count
        $this->assertGreaterThanOrEqual(5, $count);
    }

    /** @test */
    public function it_can_get_conversations_with_unread_messages()
    {
        ChatConversation::factory()->create(['unread_admin_count' => 0]);
        ChatConversation::factory()->create(['unread_admin_count' => 5]);

        $conversations = $this->chatService->getConversationsWithUnreadMessages();

        $this->assertGreaterThanOrEqual(1, $conversations->count());

        foreach ($conversations as $conversation) {
            $this->assertGreaterThan(0, $conversation->unread_admin_count);
        }
    }

    /** @test */
    public function it_can_archive_conversation()
    {
        $this->assertTrue($this->conversation->is_active);

        $this->chatService->archiveConversation($this->conversation);

        $this->conversation->refresh();
        $this->assertFalse($this->conversation->is_active);
    }

    /** @test */
    public function it_can_reactivate_conversation()
    {
        $this->conversation->update(['is_active' => false]);

        $this->chatService->reactivateConversation($this->conversation);

        $this->conversation->refresh();
        $this->assertTrue($this->conversation->is_active);
    }

    /** @test */
    public function sender_can_delete_message()
    {
        $message = ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->vendor->id,
        ]);

        $this->chatService->deleteMessage($message, $this->vendor);

        $this->assertSoftDeleted('chat_messages', ['id' => $message->id]);
    }

    /** @test */
    public function admin_can_delete_any_message()
    {
        $message = ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->vendor->id,
        ]);

        $this->chatService->deleteMessage($message, $this->admin);

        $this->assertSoftDeleted('chat_messages', ['id' => $message->id]);
    }

    /** @test */
    public function non_sender_non_admin_cannot_delete_message()
    {
        $otherVendor = User::factory()->create(['role' => 'vendor']);

        $message = ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->vendor->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized to delete this message');

        $this->chatService->deleteMessage($message, $otherVendor);
    }

    /** @test */
    public function it_can_upload_attachment()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('screenshot.jpg');

        $attachment = $this->chatService->uploadAttachment($file, $this->vendor);

        $this->assertArrayHasKey('path', $attachment);
        $this->assertArrayHasKey('original_name', $attachment);
        $this->assertArrayHasKey('mime_type', $attachment);
        $this->assertArrayHasKey('size', $attachment);
        $this->assertArrayHasKey('url', $attachment);

        Storage::disk('public')->assertExists($attachment['path']);
    }

    /** @test */
    public function it_can_upload_multiple_attachments()
    {
        Storage::fake('public');

        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.jpg'),
        ];

        $attachments = $this->chatService->uploadAttachments($files, $this->vendor);

        $this->assertCount(2, $attachments);

        foreach ($attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment['path']);
        }
    }

    /** @test */
    public function it_can_get_chat_statistics()
    {
        ChatConversation::factory()->count(3)->create(['is_active' => true]);
        ChatConversation::factory()->count(2)->create(['is_active' => false]);

        $stats = $this->chatService->getChatStats();

        $this->assertArrayHasKey('total_conversations', $stats);
        $this->assertArrayHasKey('active_conversations', $stats);
        $this->assertArrayHasKey('conversations_with_unread', $stats);
        $this->assertArrayHasKey('total_messages', $stats);
        $this->assertArrayHasKey('unread_messages', $stats);
        $this->assertArrayHasKey('total_unread_for_admin', $stats);

        $this->assertGreaterThanOrEqual(5, $stats['total_conversations']);
        $this->assertGreaterThanOrEqual(3, $stats['active_conversations']);
    }

    /** @test */
    public function it_can_search_messages_in_conversation()
    {
        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'message' => 'I need help with my order',
        ]);

        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'message' => 'Product pricing question',
        ]);

        $results = $this->chatService->searchMessages($this->conversation, 'order');

        $this->assertEquals(1, $results->count());
        $this->assertStringContainsString('order', $results->first()->message);
    }

    /** @test */
    public function it_can_get_messages_by_date_range()
    {
        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_at' => now()->subDays(5),
        ]);

        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_at' => now()->subDays(2),
        ]);

        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_at' => now()->subDays(10),
        ]);

        $messages = $this->chatService->getMessagesByDateRange(
            $this->conversation,
            now()->subDays(6)->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertEquals(2, $messages->count());
    }

    /** @test */
    public function admin_can_access_any_conversation()
    {
        $canAccess = $this->chatService->canAccessConversation($this->conversation, $this->admin);

        $this->assertTrue($canAccess);
    }

    /** @test */
    public function vendor_can_access_own_conversation()
    {
        $canAccess = $this->chatService->canAccessConversation($this->conversation, $this->vendor);

        $this->assertTrue($canAccess);
    }

    /** @test */
    public function vendor_cannot_access_other_conversation()
    {
        $otherVendor = User::factory()->create(['role' => 'vendor']);

        $canAccess = $this->chatService->canAccessConversation($this->conversation, $otherVendor);

        $this->assertFalse($canAccess);
    }
}
