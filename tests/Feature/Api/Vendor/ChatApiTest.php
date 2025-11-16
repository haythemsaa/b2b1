<?php

namespace Tests\Feature\Api\Vendor;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;
    protected User $admin;
    protected ChatConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $this->vendor->id]);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->conversation = ChatConversation::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function vendor_can_get_their_conversation()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat');

        $response->assertStatus(200)
            ->assertJson([
                'vendor_id' => $this->vendor->id,
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_chat()
    {
        $response = $this->getJson('/api/vendor/chat');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_cannot_access_vendor_chat_endpoint()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/vendor/chat');

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_send_message()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', [
                'message' => 'Hello, I have a question about my order',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'message',
                'sender_id',
                'created_at',
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->vendor->id,
            'message' => 'Hello, I have a question about my order',
        ]);
    }

    /** @test */
    public function message_is_required_when_sending()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function vendor_can_get_messages()
    {
        ChatMessage::factory()->count(5)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat/messages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'message', 'sender_id', 'created_at'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function vendor_can_get_recent_messages()
    {
        ChatMessage::factory()->count(10)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat/recent?limit=5');

        $response->assertStatus(200);

        $this->assertEquals(5, count($response->json()));
    }

    /** @test */
    public function vendor_can_mark_conversation_as_read()
    {
        $this->conversation->update(['unread_vendor_count' => 5]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/mark-as-read');

        $response->assertStatus(200);

        $this->conversation->refresh();
        $this->assertEquals(0, $this->conversation->unread_vendor_count);
    }

    /** @test */
    public function vendor_can_get_unread_count()
    {
        $this->conversation->update(['unread_vendor_count' => 7]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat/unread-count');

        $response->assertStatus(200)
            ->assertJson(['count' => 7]);
    }

    /** @test */
    public function sending_message_creates_conversation_if_not_exists()
    {
        $newVendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $newVendor->id]);

        $response = $this->actingAs($newVendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', [
                'message' => 'First message',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('chat_conversations', [
            'vendor_id' => $newVendor->id,
        ]);
    }

    /** @test */
    public function sending_message_increments_admin_unread_count()
    {
        $this->conversation->update(['unread_admin_count' => 0]);

        $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', [
                'message' => 'Hello',
            ]);

        $this->conversation->refresh();
        $this->assertEquals(1, $this->conversation->unread_admin_count);
    }

    /** @test */
    public function vendor_can_paginate_messages()
    {
        ChatMessage::factory()->count(30)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat/messages?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /** @test */
    public function vendor_cannot_access_other_vendor_messages()
    {
        $otherVendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $otherVendor->id]);

        $response = $this->actingAs($otherVendor, 'sanctum')
            ->getJson('/api/vendor/chat/messages');

        $response->assertStatus(200);

        // Should not see messages from other vendor's conversation
        $messages = $response->json('data');
        foreach ($messages as $message) {
            $this->assertNotEquals($this->conversation->id, $message['conversation_id'] ?? null);
        }
    }

    /** @test */
    public function inactive_vendor_cannot_send_messages()
    {
        $this->vendor->update(['status' => 'inactive']);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', [
                'message' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function message_with_attachments_can_be_sent()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/vendor/chat/send', [
                'message' => 'Please see attached file',
                'attachments' => [
                    ['path' => 'uploads/file1.pdf', 'name' => 'invoice.pdf'],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('chat_messages', [
            'message' => 'Please see attached file',
        ]);
    }

    /** @test */
    public function recent_messages_are_ordered_by_date()
    {
        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_at' => now()->subHours(5),
            'message' => 'Old message',
        ]);

        ChatMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_at' => now()->subHours(1),
            'message' => 'Recent message',
        ]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/vendor/chat/recent');

        $response->assertStatus(200);

        $messages = $response->json();
        // First message should be older (ascending order)
        $this->assertEquals('Old message', $messages[0]['message']);
    }
}
