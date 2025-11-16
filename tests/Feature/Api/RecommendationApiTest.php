<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecommendationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_get_personalized_recommendations()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        Sanctum::actingAs($vendor);

        $response = $this->getJson('/api/vendor/recommendations/personalized');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data'
                ]);
    }

    public function test_vendor_can_get_product_recommendations()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        
        Sanctum::actingAs($vendor);

        $response = $this->getJson("/api/vendor/recommendations/product/{$product->id}");

        $response->assertStatus(200);
    }

    public function test_vendor_can_calculate_recommendations()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        Sanctum::actingAs($vendor);

        $response = $this->postJson('/api/vendor/recommendations/calculate');

        $response->assertStatus(200)
                ->assertJsonStructure(['status', 'count']);
    }
}
