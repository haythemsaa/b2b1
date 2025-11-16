<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttributeApiTest extends TestCase
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
    public function admin_can_list_all_attributes()
    {
        ProductAttribute::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/attributes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'type', 'is_filterable', 'is_variant'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function admin_can_create_text_attribute()
    {
        $attributeData = [
            'name' => 'Brand',
            'slug' => 'brand',
            'type' => 'text',
            'is_filterable' => true,
            'is_required' => false,
            'is_variant' => false,
            'order' => 0,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Brand', 'type' => 'text']);

        $this->assertDatabaseHas('product_attributes', [
            'name' => 'Brand',
            'slug' => 'brand',
            'type' => 'text',
        ]);
    }

    /** @test */
    public function admin_can_create_select_attribute_with_options()
    {
        $attributeData = [
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'options' => ['S', 'M', 'L', 'XL'],
            'is_filterable' => true,
            'is_variant' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Size', 'type' => 'select']);

        $attribute = ProductAttribute::where('slug', 'size')->first();
        $this->assertEquals(['S', 'M', 'L', 'XL'], $attribute->options);
    }

    /** @test */
    public function admin_can_create_multiselect_attribute()
    {
        $attributeData = [
            'name' => 'Features',
            'slug' => 'features',
            'type' => 'multiselect',
            'options' => ['Bluetooth', 'WiFi', 'USB-C', 'Waterproof'],
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'multiselect']);
    }

    /** @test */
    public function admin_can_create_color_attribute()
    {
        $attributeData = [
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_filterable' => true,
            'is_variant' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'color']);
    }

    /** @test */
    public function admin_can_create_number_attribute()
    {
        $attributeData = [
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => 'number',
            'unit' => 'kg',
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'number']);
    }

    /** @test */
    public function admin_can_create_boolean_attribute()
    {
        $attributeData = [
            'name' => 'Is Organic',
            'slug' => 'is-organic',
            'type' => 'boolean',
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'boolean']);
    }

    /** @test */
    public function select_attribute_must_have_options()
    {
        $attributeData = [
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'options' => [],
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Select and Multiselect attributes must have options']);
    }

    /** @test */
    public function multiselect_attribute_must_have_options()
    {
        $attributeData = [
            'name' => 'Features',
            'slug' => 'features',
            'type' => 'multiselect',
            'options' => [],
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Select and Multiselect attributes must have options']);
    }

    /** @test */
    public function attribute_slug_must_be_unique()
    {
        ProductAttribute::factory()->create(['slug' => 'color']);

        $attributeData = [
            'name' => 'Colour',
            'slug' => 'color',
            'type' => 'text',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function admin_can_update_attribute()
    {
        $attribute = ProductAttribute::factory()->create([
            'name' => 'Size',
            'type' => 'select',
            'options' => ['S', 'M', 'L'],
        ]);

        $updateData = [
            'name' => 'Product Size',
            'options' => ['S', 'M', 'L', 'XL', 'XXL'],
            'is_filterable' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/attributes/{$attribute->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Product Size']);

        $attribute->refresh();
        $this->assertEquals(['S', 'M', 'L', 'XL', 'XXL'], $attribute->options);
    }

    /** @test */
    public function admin_can_delete_attribute_not_in_use()
    {
        $attribute = ProductAttribute::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/attributes/{$attribute->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('product_attributes', [
            'id' => $attribute->id,
        ]);
    }

    /** @test */
    public function cannot_delete_attribute_assigned_to_category()
    {
        $attribute = ProductAttribute::factory()->create();
        $category = ProductCategory::factory()->create();

        $category->attributes()->attach($attribute->id);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/attributes/{$attribute->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot delete attribute assigned to categories']);

        $this->assertDatabaseHas('product_attributes', [
            'id' => $attribute->id,
        ]);
    }

    /** @test */
    public function admin_can_validate_attribute_value()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'color',
        ]);

        // Valid color
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => '#FF5733',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => true]);

        // Invalid color
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => 'not-a-color',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => false]);
    }

    /** @test */
    public function admin_can_validate_select_attribute_value()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'select',
            'options' => ['S', 'M', 'L'],
        ]);

        // Valid option
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => 'M',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => true]);

        // Invalid option
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => 'XL',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => false]);
    }

    /** @test */
    public function admin_can_validate_number_attribute_value()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'number',
        ]);

        // Valid number
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => '42.5',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => true]);

        // Invalid number
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/attributes/{$attribute->id}/validate", [
                'value' => 'not-a-number',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['valid' => false]);
    }

    /** @test */
    public function admin_can_filter_attributes_by_type()
    {
        ProductAttribute::factory()->create(['type' => 'text']);
        ProductAttribute::factory()->create(['type' => 'select']);
        ProductAttribute::factory()->create(['type' => 'color']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/attributes?type=select');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_filter_filterable_attributes()
    {
        ProductAttribute::factory()->create(['is_filterable' => true]);
        ProductAttribute::factory()->create(['is_filterable' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/attributes?filterable=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_filter_variant_attributes()
    {
        ProductAttribute::factory()->create(['is_variant' => true]);
        ProductAttribute::factory()->create(['is_variant' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/attributes?variant=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function vendor_cannot_create_attributes()
    {
        $attributeData = [
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
        ];

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/admin/attributes', $attributeData);

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_view_attributes()
    {
        ProductAttribute::factory()->count(3)->create();

        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/admin/attributes');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_attributes()
    {
        $response = $this->getJson('/api/admin/attributes');

        $response->assertStatus(401);
    }
}
