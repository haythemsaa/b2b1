<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
    }

    /** @test */
    public function admin_can_list_all_categories()
    {
        ProductCategory::factory()->count(5)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'parent_id', 'is_active'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function admin_can_create_root_category()
    {
        $categoryData = [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products',
            'is_active' => true,
            'meta' => ['icon' => 'electronics-icon'],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function admin_can_create_subcategory()
    {
        $parent = ProductCategory::factory()->create(['name' => 'Electronics']);

        $categoryData = [
            'name' => 'Laptops',
            'slug' => 'laptops',
            'parent_id' => $parent->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Laptops', 'parent_id' => $parent->id]);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Laptops',
            'parent_id' => $parent->id,
        ]);
    }

    /** @test */
    public function category_slug_must_be_unique()
    {
        ProductCategory::factory()->create(['slug' => 'electronics']);

        $categoryData = [
            'name' => 'Electronic Goods',
            'slug' => 'electronics',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function admin_can_update_category()
    {
        $category = ProductCategory::factory()->create(['name' => 'Electronics']);

        $updateData = [
            'name' => 'Electronic Devices',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Electronic Devices']);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Electronic Devices',
        ]);
    }

    /** @test */
    public function cannot_set_category_as_its_own_parent()
    {
        $category = ProductCategory::factory()->create();

        $updateData = [
            'parent_id' => $category->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot set category as its own parent']);
    }

    /** @test */
    public function cannot_create_circular_reference()
    {
        $parent = ProductCategory::factory()->create(['name' => 'Parent']);
        $child = ProductCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        // Try to set parent's parent as child (circular reference)
        $updateData = [
            'parent_id' => $child->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$parent->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot set category as its own descendant']);
    }

    /** @test */
    public function admin_can_delete_category_without_children()
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function cannot_delete_category_with_children()
    {
        $parent = ProductCategory::factory()->create();
        ProductCategory::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$parent->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot delete category with subcategories']);

        $this->assertDatabaseHas('product_categories', [
            'id' => $parent->id,
        ]);
    }

    /** @test */
    public function admin_can_get_category_tree()
    {
        $parent = ProductCategory::factory()->create(['name' => 'Electronics']);
        $child1 = ProductCategory::factory()->create(['name' => 'Laptops', 'parent_id' => $parent->id]);
        $child2 = ProductCategory::factory()->create(['name' => 'Phones', 'parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/categories/tree');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'children' => [
                            '*' => ['id', 'name'],
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_assign_attribute_to_category()
    {
        $category = ProductCategory::factory()->create();
        $attribute = ProductAttribute::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/categories/{$category->id}/attributes", [
                'attribute_id' => $attribute->id,
                'is_required' => true,
                'order' => 0,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('category_attributes', [
            'category_id' => $category->id,
            'attribute_id' => $attribute->id,
            'is_required' => true,
        ]);
    }

    /** @test */
    public function admin_can_get_category_attributes()
    {
        $category = ProductCategory::factory()->create();
        $attribute1 = ProductAttribute::factory()->create(['name' => 'Size']);
        $attribute2 = ProductAttribute::factory()->create(['name' => 'Color']);

        $category->attributes()->attach($attribute1->id, ['is_required' => true, 'order' => 0]);
        $category->attributes()->attach($attribute2->id, ['is_required' => false, 'order' => 1]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/categories/{$category->id}/attributes");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Size'])
            ->assertJsonFragment(['name' => 'Color']);
    }

    /** @test */
    public function admin_can_update_category_attribute_order()
    {
        $category = ProductCategory::factory()->create();
        $attribute = ProductAttribute::factory()->create();

        $category->attributes()->attach($attribute->id, ['is_required' => false, 'order' => 0]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}/attributes/{$attribute->id}", [
                'order' => 5,
                'is_required' => true,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('category_attributes', [
            'category_id' => $category->id,
            'attribute_id' => $attribute->id,
            'order' => 5,
            'is_required' => true,
        ]);
    }

    /** @test */
    public function admin_can_detach_attribute_from_category()
    {
        $category = ProductCategory::factory()->create();
        $attribute = ProductAttribute::factory()->create();

        $category->attributes()->attach($attribute->id);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}/attributes/{$attribute->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('category_attributes', [
            'category_id' => $category->id,
            'attribute_id' => $attribute->id,
        ]);
    }

    /** @test */
    public function vendor_cannot_create_categories()
    {
        $categoryData = [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_view_categories()
    {
        ProductCategory::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/admin/categories');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_categories()
    {
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_get_category_stats()
    {
        $category = ProductCategory::factory()->create();
        ProductCategory::factory()->count(3)->create(['parent_id' => $category->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/categories/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_categories',
                    'root_categories',
                    'categories_with_products',
                ],
            ]);
    }
}
