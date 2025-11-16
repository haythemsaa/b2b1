<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;
    protected ChatConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        VendorProfile::factory()->create(['user_id' => $this->vendor->id]);

        $this->conversation = ChatConversation::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
    }

    /** @test */
    public function admin_can_list_all_conversations()
    {
        ChatConversation::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'vendor', 'last_message_at', 'unread_admin_count'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function vendor_cannot_access_admin_chat_endpoint()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/admin/chat');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_chat()
    {
        $response = $this->getJson('/api/admin/chat');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_view_specific_conversation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/chat/{$this->conversation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->conversation->id,
                'vendor_id' => $this->vendor->id,
            ]);
    }

    /** @test */
    public function admin_can_get_conversation_messages()
    {
        ChatMessage::factory()->count(10)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/chat/{$this->conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'message', 'sender_id', 'created_at'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(10, count($response->json('data')));
    }

    /** @test */
    public function admin_can_get_recent_messages()
    {
        ChatMessage::factory()->count(20)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/chat/{$this->conversation->id}/recent?limit=5");

        $response->assertStatus(200);

        $this->assertEquals(5, count($response->json()));
    }

    /** @test */
    public function admin_can_send_message()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/send", [
                'message' => 'Hello, how can I help you?',
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
            'sender_id' => $this->admin->id,
            'message' => 'Hello, how can I help you?',
        ]);
    }

    /** @test */
    public function admin_message_increments_vendor_unread_count()
    {
        $this->conversation->update(['unread_vendor_count' => 0]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/send", [
                'message' => 'Test message',
            ]);

        $this->conversation->refresh();
        $this->assertEquals(1, $this->conversation->unread_vendor_count);
    }

    /** @test */
    public function admin_can_mark_conversation_as_read()
    {
        $this->conversation->update(['unread_admin_count' => 5]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/mark-as-read");

        $response->assertStatus(200);

        $this->conversation->refresh();
        $this->assertEquals(0, $this->conversation->unread_admin_count);
    }

    /** @test */
    public function admin_can_archive_conversation()
    {
        $this->assertTrue($this->conversation->is_active);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/archive");

        $response->assertStatus(200);

        $this->conversation->refresh();
        $this->assertFalse($this->conversation->is_active);
    }

    /** @test */
    public function admin_can_reactivate_conversation()
    {
        $this->conversation->update(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/reactivate");

        $response->assertStatus(200);

        $this->conversation->refresh();
        $this->assertTrue($this->conversation->is_active);
    }

    /** @test */
    public function admin_can_filter_conversations_with_unread_messages()
    {
        ChatConversation::factory()->create(['unread_admin_count' => 5]);
        ChatConversation::factory()->create(['unread_admin_count' => 0]);
        ChatConversation::factory()->create(['unread_admin_count' => 3]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat?has_unread=1');

        $response->assertStatus(200);

        $conversations = $response->json('data');
        foreach ($conversations as $conversation) {
            $this->assertGreaterThan(0, $conversation['unread_admin_count']);
        }
    }

    /** @test */
    public function admin_can_filter_active_inactive_conversations()
    {
        ChatConversation::factory()->count(3)->create(['is_active' => true]);
        ChatConversation::factory()->count(2)->create(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat?is_active=1');

        $response->assertStatus(200);

        $conversations = $response->json('data');
        foreach ($conversations as $conversation) {
            $this->assertTrue($conversation['is_active']);
        }
    }

    /** @test */
    public function admin_can_search_conversations_by_vendor_name()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'name' => 'Special Vendor Name',
        ]);
        VendorProfile::factory()->create(['user_id' => $vendor->id]);

        ChatConversation::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat?search=Special');

        $response->assertStatus(200);

        $conversations = $response->json('data');
        $this->assertTrue(count($conversations) > 0);
        $this->assertStringContainsString('Special', $conversations[0]['vendor']['name']);
    }

    /** @test */
    public function admin_can_get_unread_conversations()
    {
        ChatConversation::factory()->create(['unread_admin_count' => 3]);
        ChatConversation::factory()->create(['unread_admin_count' => 5]);
        ChatConversation::factory()->create(['unread_admin_count' => 0]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat/unread');

        $response->assertStatus(200);

        $conversations = $response->json();
        $this->assertGreaterThanOrEqual(2, count($conversations));

        foreach ($conversations as $conversation) {
            $this->assertGreaterThan(0, $conversation['unread_admin_count']);
        }
    }

    /** @test */
    public function admin_can_get_chat_statistics()
    {
        ChatConversation::factory()->count(3)->create(['is_active' => true]);
        ChatConversation::factory()->count(2)->create(['is_active' => false]);
        ChatConversation::factory()->create(['unread_admin_count' => 5]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_conversations',
                'active_conversations',
                'conversations_with_unread',
                'total_messages',
                'unread_messages',
                'total_unread_for_admin',
            ]);

        $stats = $response->json();
        $this->assertGreaterThanOrEqual(5, $stats['total_conversations']);
        $this->assertGreaterThanOrEqual(3, $stats['active_conversations']);
    }

    /** @test */
    public function admin_can_paginate_conversations()
    {
        ChatConversation::factory()->count(30)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat?per_page=10');

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
    public function admin_can_paginate_messages()
    {
        ChatMessage::factory()->count(50)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/chat/{$this->conversation->id}/messages?per_page=20");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(20, count($response->json('data')));
    }

    /** @test */
    public function message_requires_content()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/chat/{$this->conversation->id}/send", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function admin_cannot_access_non_existent_conversation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function conversations_are_ordered_by_recent_activity()
    {
        $conv1 = ChatConversation::factory()->create([
            'last_message_at' => now()->subHours(5),
        ]);

        $conv2 = ChatConversation::factory()->create([
            'last_message_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/chat');

        $response->assertStatus(200);

        $conversations = $response->json('data');
        // First conversation should be the most recent
        $this->assertEquals($conv2->id, $conversations[0]['id']);
    }
}
