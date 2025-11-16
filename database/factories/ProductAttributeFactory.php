<?php

namespace Database\Factories;

use App\Models\Product\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product\ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $type = $this->faker->randomElement([
            ProductAttribute::TYPE_TEXT,
            ProductAttribute::TYPE_SELECT,
        ]);

        $options = null;
        if (in_array($type, [ProductAttribute::TYPE_SELECT, ProductAttribute::TYPE_MULTISELECT])) {
            $options = $this->faker->randomElements(
                ['Option 1', 'Option 2', 'Option 3', 'Option 4', 'Option 5'],
                $this->faker->numberBetween(2, 5)
            );
        }

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'type' => $type,
            'options' => $options,
            'is_filterable' => $this->faker->boolean(70),
            'is_required' => $this->faker->boolean(30),
            'is_variant' => $this->faker->boolean(40),
            'order' => $this->faker->numberBetween(0, 100),
            'unit' => null,
            'meta' => [],
        ];
    }

    /**
     * Text attribute.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_TEXT,
            'options' => null,
        ]);
    }

    /**
     * Number attribute with optional unit.
     */
    public function number(?string $unit = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_NUMBER,
            'options' => null,
            'unit' => $unit ?? $this->faker->optional()->randomElement(['kg', 'g', 'cm', 'm', 'L', 'ml']),
        ]);
    }

    /**
     * Select attribute with predefined options.
     */
    public function select(array $options = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_SELECT,
            'options' => $options ?? ['S', 'M', 'L', 'XL'],
        ]);
    }

    /**
     * Multiselect attribute with predefined options.
     */
    public function multiselect(array $options = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_MULTISELECT,
            'options' => $options ?? ['Feature 1', 'Feature 2', 'Feature 3', 'Feature 4'],
        ]);
    }

    /**
     * Color attribute.
     */
    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_COLOR,
            'options' => null,
            'is_variant' => true,
        ]);
    }

    /**
     * Boolean attribute.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductAttribute::TYPE_BOOLEAN,
            'options' => null,
        ]);
    }

    /**
     * Filterable attribute.
     */
    public function filterable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_filterable' => true,
        ]);
    }

    /**
     * Required attribute.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Variant attribute.
     */
    public function variant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_variant' => true,
        ]);
    }

    /**
     * Create a Size attribute (select type).
     */
    public function size(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Size',
            'slug' => 'size',
            'type' => ProductAttribute::TYPE_SELECT,
            'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'is_filterable' => true,
            'is_variant' => true,
        ]);
    }

    /**
     * Create a Color attribute.
     */
    public function colorAttribute(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Color',
            'slug' => 'color',
            'type' => ProductAttribute::TYPE_COLOR,
            'options' => null,
            'is_filterable' => true,
            'is_variant' => true,
        ]);
    }

    /**
     * Create a Brand attribute (text type).
     */
    public function brand(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Brand',
            'slug' => 'brand',
            'type' => ProductAttribute::TYPE_TEXT,
            'options' => null,
            'is_filterable' => true,
            'is_variant' => false,
        ]);
    }

    /**
     * Create a Weight attribute (number type).
     */
    public function weight(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => ProductAttribute::TYPE_NUMBER,
            'options' => null,
            'unit' => 'kg',
            'is_filterable' => true,
            'is_variant' => false,
        ]);
    }

    /**
     * Create a Material attribute (select type).
     */
    public function material(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Material',
            'slug' => 'material',
            'type' => ProductAttribute::TYPE_SELECT,
            'options' => ['Cotton', 'Polyester', 'Wool', 'Silk', 'Leather'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);
    }

    /**
     * Create a Features attribute (multiselect type).
     */
    public function features(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Features',
            'slug' => 'features',
            'type' => ProductAttribute::TYPE_MULTISELECT,
            'options' => ['Bluetooth', 'WiFi', 'USB-C', 'Waterproof', 'Wireless', 'Fast Charging'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);
    }

    /**
     * Create an Is Organic attribute (boolean type).
     */
    public function organic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Is Organic',
            'slug' => 'is-organic',
            'type' => ProductAttribute::TYPE_BOOLEAN,
            'options' => null,
            'is_filterable' => true,
            'is_variant' => false,
        ]);
    }
}
