<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function category_can_have_parent()
    {
        $parent = ProductCategory::factory()->create(['name' => 'Electronics']);
        $child = ProductCategory::factory()->create([
            'name' => 'Laptops',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals('Electronics', $child->parent->name);
    }

    /** @test */
    public function category_can_have_children()
    {
        $parent = ProductCategory::factory()->create(['name' => 'Electronics']);
        $child1 = ProductCategory::factory()->create(['parent_id' => $parent->id, 'name' => 'Laptops']);
        $child2 = ProductCategory::factory()->create(['parent_id' => $parent->id, 'name' => 'Phones']);

        $this->assertCount(2, $parent->children);
        $this->assertTrue($parent->children->contains($child1));
        $this->assertTrue($parent->children->contains($child2));
    }

    /** @test */
    public function path_attribute_returns_category_hierarchy()
    {
        $grandparent = ProductCategory::factory()->create(['name' => 'All']);
        $parent = ProductCategory::factory()->create(['name' => 'Electronics', 'parent_id' => $grandparent->id]);
        $child = ProductCategory::factory()->create(['name' => 'Laptops', 'parent_id' => $parent->id]);

        $path = $child->path;

        $this->assertCount(3, $path);
        $this->assertEquals('All', $path[0]->name);
        $this->assertEquals('Electronics', $path[1]->name);
        $this->assertEquals('Laptops', $path[2]->name);
    }

    /** @test */
    public function breadcrumb_attribute_returns_formatted_string()
    {
        $grandparent = ProductCategory::factory()->create(['name' => 'All']);
        $parent = ProductCategory::factory()->create(['name' => 'Electronics', 'parent_id' => $grandparent->id]);
        $child = ProductCategory::factory()->create(['name' => 'Laptops', 'parent_id' => $parent->id]);

        $breadcrumb = $child->breadcrumb;

        $this->assertEquals('All > Electronics > Laptops', $breadcrumb);
    }

    /** @test */
    public function get_all_children_returns_nested_categories()
    {
        $root = ProductCategory::factory()->create(['name' => 'Root']);
        $child1 = ProductCategory::factory()->create(['name' => 'Child 1', 'parent_id' => $root->id]);
        $child2 = ProductCategory::factory()->create(['name' => 'Child 2', 'parent_id' => $root->id]);
        $grandchild = ProductCategory::factory()->create(['name' => 'Grandchild', 'parent_id' => $child1->id]);

        $allChildren = $root->getAllChildren();

        $this->assertCount(3, $allChildren);
        $this->assertTrue($allChildren->contains($child1));
        $this->assertTrue($allChildren->contains($child2));
        $this->assertTrue($allChildren->contains($grandchild));
    }

    /** @test */
    public function depth_attribute_returns_correct_level()
    {
        $root = ProductCategory::factory()->create();
        $child = ProductCategory::factory()->create(['parent_id' => $root->id]);
        $grandchild = ProductCategory::factory()->create(['parent_id' => $child->id]);

        $this->assertEquals(0, $root->depth);
        $this->assertEquals(1, $child->depth);
        $this->assertEquals(2, $grandchild->depth);
    }

    /** @test */
    public function category_can_have_attributes()
    {
        $category = ProductCategory::factory()->create();
        $attribute1 = ProductAttribute::factory()->create(['name' => 'Size']);
        $attribute2 = ProductAttribute::factory()->create(['name' => 'Color']);

        $category->attributes()->attach($attribute1->id, ['is_required' => true, 'order' => 0]);
        $category->attributes()->attach($attribute2->id, ['is_required' => false, 'order' => 1]);

        $this->assertCount(2, $category->attributes);
        $this->assertEquals('Size', $category->attributes->first()->name);
    }

    /** @test */
    public function category_attributes_are_ordered()
    {
        $category = ProductCategory::factory()->create();
        $attribute1 = ProductAttribute::factory()->create(['name' => 'Size']);
        $attribute2 = ProductAttribute::factory()->create(['name' => 'Color']);
        $attribute3 = ProductAttribute::factory()->create(['name' => 'Brand']);

        $category->attributes()->attach($attribute1->id, ['order' => 2]);
        $category->attributes()->attach($attribute2->id, ['order' => 0]);
        $category->attributes()->attach($attribute3->id, ['order' => 1]);

        $orderedAttributes = $category->attributes()->orderBy('category_attributes.order')->get();

        $this->assertEquals('Color', $orderedAttributes->first()->name);
        $this->assertEquals('Brand', $orderedAttributes->skip(1)->first()->name);
        $this->assertEquals('Size', $orderedAttributes->skip(2)->first()->name);
    }

    /** @test */
    public function category_can_have_required_attributes()
    {
        $category = ProductCategory::factory()->create();
        $requiredAttr = ProductAttribute::factory()->create(['name' => 'Size']);
        $optionalAttr = ProductAttribute::factory()->create(['name' => 'Color']);

        $category->attributes()->attach($requiredAttr->id, ['is_required' => true]);
        $category->attributes()->attach($optionalAttr->id, ['is_required' => false]);

        $requiredAttributes = $category->attributes()->wherePivot('is_required', true)->get();

        $this->assertCount(1, $requiredAttributes);
        $this->assertEquals('Size', $requiredAttributes->first()->name);
    }

    /** @test */
    public function slug_is_automatically_generated_if_not_provided()
    {
        $category = ProductCategory::factory()->create(['name' => 'Test Category', 'slug' => null]);

        $this->assertNotNull($category->slug);
        $this->assertEquals('test-category', $category->slug);
    }

    /** @test */
    public function category_meta_is_cast_to_array()
    {
        $category = ProductCategory::factory()->create([
            'meta' => ['icon' => 'laptop', 'color' => 'blue'],
        ]);

        $this->assertIsArray($category->meta);
        $this->assertEquals('laptop', $category->meta['icon']);
        $this->assertEquals('blue', $category->meta['color']);
    }

    /** @test */
    public function is_root_returns_true_for_root_categories()
    {
        $root = ProductCategory::factory()->create(['parent_id' => null]);
        $child = ProductCategory::factory()->create(['parent_id' => $root->id]);

        $this->assertTrue($root->is_root);
        $this->assertFalse($child->is_root);
    }

    /** @test */
    public function has_children_returns_true_when_category_has_subcategories()
    {
        $parent = ProductCategory::factory()->create();
        $child = ProductCategory::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($parent->has_children);
        $this->assertFalse($child->has_children);
    }
}
