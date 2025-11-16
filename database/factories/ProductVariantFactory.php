<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 10, 500);
        $comparePrice = $this->faker->optional(0.3)->randomFloat(2, $price + 10, $price + 100);

        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-' . strtoupper($this->faker->unique()->bothify('???-####')),
            'name' => $this->faker->optional()->words(3, true),
            'attributes' => [
                'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                'color' => $this->faker->hexColor(),
            ],
            'price' => $price,
            'compare_price' => $comparePrice,
            'stock' => $this->faker->numberBetween(0, 500),
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
            'moq' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 50),
            'dimensions' => $this->faker->optional()->passthrough([
                'length' => $this->faker->numberBetween(10, 100),
                'width' => $this->faker->numberBetween(10, 100),
                'height' => $this->faker->numberBetween(5, 50),
            ]),
            'images' => $this->faker->optional(0.5)->passthrough([
                'https://via.placeholder.com/800x800/' . substr($this->faker->hexColor(), 1),
                'https://via.placeholder.com/800x800/' . substr($this->faker->hexColor(), 1),
            ]),
            'meta' => [
                'barcode' => $this->faker->optional()->ean13(),
                'condition' => $this->faker->randomElement(['new', 'refurbished']),
            ],
        ];
    }

    /**
     * Variant with specific attributes.
     */
    public function withAttributes(array $attributes): static
    {
        return $this->state(fn (array $attrs) => [
            'attributes' => $attributes,
        ]);
    }

    /**
     * Variant with Size and Color attributes.
     */
    public function sizeColor(string $size, string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'attributes' => [
                'size' => $size,
                'color' => $color,
            ],
            'sku' => 'VAR-' . strtoupper(substr($size, 0, 1)) . '-' . strtoupper(substr($color, 0, 3)) . '-' . $this->faker->randomNumber(4),
        ]);
    }

    /**
     * Variant with discount (compare price).
     */
    public function withDiscount(float $percentage = 20): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $price = $attributes['price'];
            $comparePrice = $price / (1 - ($percentage / 100));

            return [
                'compare_price' => round($comparePrice, 2),
            ];
        });
    }

    /**
     * Inactive variant.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Out of stock variant.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Low stock variant.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(1, $attributes['low_stock_threshold'] ?? 10),
        ]);
    }

    /**
     * High stock variant.
     */
    public function highStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(500, 2000),
        ]);
    }

    /**
     * Variant with images.
     */
    public function withImages(int $count = 3): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $images = [];
            for ($i = 0; $i < $count; $i++) {
                $images[] = 'https://via.placeholder.com/800x800/' . substr($this->faker->hexColor(), 1);
            }

            return ['images' => $images];
        });
    }

    /**
     * Variant with specific price.
     */
    public function priced(float $price, ?float $comparePrice = null): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
            'compare_price' => $comparePrice,
        ]);
    }

    /**
     * Variant with specific SKU.
     */
    public function sku(string $sku): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => $sku,
        ]);
    }

    /**
     * Variant with specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Small size variant.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'attributes' => array_merge($attributes['attributes'] ?? [], ['size' => 'S']),
        ]);
    }

    /**
     * Medium size variant.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'attributes' => array_merge($attributes['attributes'] ?? [], ['size' => 'M']),
        ]);
    }

    /**
     * Large size variant.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'attributes' => array_merge($attributes['attributes'] ?? [], ['size' => 'L']),
        ]);
    }

    /**
     * Variant with weight.
     */
    public function weighted(float $weight): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $weight,
        ]);
    }

    /**
     * Variant with MOQ.
     */
    public function moq(int $moq): static
    {
        return $this->state(fn (array $attributes) => [
            'moq' => $moq,
        ]);
    }
}
