<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecommendationService;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecommendationService::class);
    }

    public function test_can_get_recommendations_for_product()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $recommendations = $this->service->getRecommendations($product);

        $this->assertIsObject($recommendations);
    }

    public function test_can_get_trending_products()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        
        $trending = $this->service->getTrendingProducts($vendor);

        $this->assertIsObject($trending);
    }

    public function test_can_get_stats()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        
        $stats = $this->service->getStats($vendor);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_recommendations', $stats);
    }
}
