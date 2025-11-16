<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductAttributeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function text_attribute_validates_any_string()
    {
        $attribute = ProductAttribute::factory()->create(['type' => 'text']);

        $this->assertTrue($attribute->validateValue('Any string value'));
        $this->assertTrue($attribute->validateValue('123'));
        $this->assertTrue($attribute->validateValue(''));
    }

    /** @test */
    public function number_attribute_validates_numeric_values()
    {
        $attribute = ProductAttribute::factory()->create(['type' => 'number']);

        $this->assertTrue($attribute->validateValue('42'));
        $this->assertTrue($attribute->validateValue('3.14'));
        $this->assertTrue($attribute->validateValue(100));
        $this->assertFalse($attribute->validateValue('not a number'));
        $this->assertFalse($attribute->validateValue('abc123'));
    }

    /** @test */
    public function color_attribute_validates_hex_colors()
    {
        $attribute = ProductAttribute::factory()->create(['type' => 'color']);

        $this->assertTrue($attribute->validateValue('#FF5733'));
        $this->assertTrue($attribute->validateValue('#000000'));
        $this->assertTrue($attribute->validateValue('#FFFFFF'));
        $this->assertFalse($attribute->validateValue('red'));
        $this->assertFalse($attribute->validateValue('#FFF'));
        $this->assertFalse($attribute->validateValue('FF5733'));
    }

    /** @test */
    public function select_attribute_validates_against_options()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'select',
            'options' => ['S', 'M', 'L', 'XL'],
        ]);

        $this->assertTrue($attribute->validateValue('S'));
        $this->assertTrue($attribute->validateValue('M'));
        $this->assertTrue($attribute->validateValue('XL'));
        $this->assertFalse($attribute->validateValue('XXL'));
        $this->assertFalse($attribute->validateValue('small'));
    }

    /** @test */
    public function multiselect_attribute_validates_array_against_options()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'multiselect',
            'options' => ['Bluetooth', 'WiFi', 'USB-C', 'Waterproof'],
        ]);

        $this->assertTrue($attribute->validateValue(['Bluetooth', 'WiFi']));
        $this->assertTrue($attribute->validateValue(['USB-C']));
        $this->assertFalse($attribute->validateValue(['NFC']));
        $this->assertFalse($attribute->validateValue(['Bluetooth', 'Invalid']));
    }

    /** @test */
    public function boolean_attribute_validates_boolean_values()
    {
        $attribute = ProductAttribute::factory()->create(['type' => 'boolean']);

        $this->assertTrue($attribute->validateValue(true));
        $this->assertTrue($attribute->validateValue(false));
        $this->assertTrue($attribute->validateValue(1));
        $this->assertTrue($attribute->validateValue(0));
        $this->assertTrue($attribute->validateValue('1'));
        $this->assertTrue($attribute->validateValue('0'));
    }

    /** @test */
    public function get_options_array_returns_options_as_array()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'select',
            'options' => ['S', 'M', 'L'],
        ]);

        $options = $attribute->getOptionsArray();

        $this->assertIsArray($options);
        $this->assertCount(3, $options);
        $this->assertEquals(['S', 'M', 'L'], $options);
    }

    /** @test */
    public function get_options_array_returns_empty_for_non_option_types()
    {
        $attribute = ProductAttribute::factory()->create(['type' => 'text']);

        $options = $attribute->getOptionsArray();

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    /** @test */
    public function options_are_cast_to_array()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'select',
            'options' => ['S', 'M', 'L'],
        ]);

        $this->assertIsArray($attribute->options);
        $this->assertEquals(['S', 'M', 'L'], $attribute->options);
    }

    /** @test */
    public function attribute_can_be_filterable()
    {
        $filterable = ProductAttribute::factory()->create(['is_filterable' => true]);
        $nonFilterable = ProductAttribute::factory()->create(['is_filterable' => false]);

        $this->assertTrue($filterable->is_filterable);
        $this->assertFalse($nonFilterable->is_filterable);
    }

    /** @test */
    public function attribute_can_be_variant_attribute()
    {
        $variant = ProductAttribute::factory()->create(['is_variant' => true]);
        $nonVariant = ProductAttribute::factory()->create(['is_variant' => false]);

        $this->assertTrue($variant->is_variant);
        $this->assertFalse($nonVariant->is_variant);
    }

    /** @test */
    public function attribute_can_belong_to_multiple_categories()
    {
        $attribute = ProductAttribute::factory()->create(['name' => 'Color']);
        $category1 = ProductCategory::factory()->create(['name' => 'Clothing']);
        $category2 = ProductCategory::factory()->create(['name' => 'Accessories']);

        $category1->attributes()->attach($attribute->id);
        $category2->attributes()->attach($attribute->id);

        $this->assertCount(2, $attribute->categories);
        $this->assertTrue($attribute->categories->contains($category1));
        $this->assertTrue($attribute->categories->contains($category2));
    }

    /** @test */
    public function slug_is_automatically_generated_if_not_provided()
    {
        $attribute = ProductAttribute::factory()->create(['name' => 'Product Color', 'slug' => null]);

        $this->assertNotNull($attribute->slug);
        $this->assertEquals('product-color', $attribute->slug);
    }

    /** @test */
    public function attribute_type_constants_are_defined()
    {
        $this->assertEquals('text', ProductAttribute::TYPE_TEXT);
        $this->assertEquals('number', ProductAttribute::TYPE_NUMBER);
        $this->assertEquals('select', ProductAttribute::TYPE_SELECT);
        $this->assertEquals('multiselect', ProductAttribute::TYPE_MULTISELECT);
        $this->assertEquals('color', ProductAttribute::TYPE_COLOR);
        $this->assertEquals('boolean', ProductAttribute::TYPE_BOOLEAN);
    }

    /** @test */
    public function attribute_has_unit_field_for_measurements()
    {
        $attribute = ProductAttribute::factory()->create([
            'type' => 'number',
            'unit' => 'kg',
        ]);

        $this->assertEquals('kg', $attribute->unit);
    }

    /** @test */
    public function attribute_can_be_required()
    {
        $required = ProductAttribute::factory()->create(['is_required' => true]);
        $optional = ProductAttribute::factory()->create(['is_required' => false]);

        $this->assertTrue($required->is_required);
        $this->assertFalse($optional->is_required);
    }

    /** @test */
    public function attribute_order_can_be_set()
    {
        $attr1 = ProductAttribute::factory()->create(['order' => 0]);
        $attr2 = ProductAttribute::factory()->create(['order' => 1]);
        $attr3 = ProductAttribute::factory()->create(['order' => 2]);

        $orderedAttributes = ProductAttribute::orderBy('order')->get();

        $this->assertEquals($attr1->id, $orderedAttributes->first()->id);
        $this->assertEquals($attr3->id, $orderedAttributes->last()->id);
    }

    /** @test */
    public function meta_is_cast_to_array()
    {
        $attribute = ProductAttribute::factory()->create([
            'meta' => ['min' => 0, 'max' => 100, 'step' => 5],
        ]);

        $this->assertIsArray($attribute->meta);
        $this->assertEquals(0, $attribute->meta['min']);
        $this->assertEquals(100, $attribute->meta['max']);
        $this->assertEquals(5, $attribute->meta['step']);
    }
}
