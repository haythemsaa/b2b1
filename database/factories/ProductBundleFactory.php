<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Product\ProductBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product\ProductBundle>
 */
class ProductBundleFactory extends Factory
{
    protected $model = ProductBundle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bundle_product_id' => Product::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'discount_percentage' => $this->faker->randomFloat(2, 0, 30),
            'custom_price' => null,
            'is_required' => $this->faker->boolean(70),
            'order' => $this->faker->numberBetween(0, 10),
            'meta' => [
                'notes' => $this->faker->optional()->sentence(),
            ],
        ];
    }

    /**
     * Bundle item for a specific bundle product.
     */
    public function forBundle(int $bundleProductId): static
    {
        return $this->state(fn (array $attributes) => [
            'bundle_product_id' => $bundleProductId,
        ]);
    }

    /**
     * Bundle item with a specific product.
     */
    public function withProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Bundle item with specific quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Bundle item with specific discount percentage.
     */
    public function discount(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }

    /**
     * Bundle item with no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => 0,
        ]);
    }

    /**
     * Bundle item with high discount.
     */
    public function highDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $this->faker->randomFloat(2, 30, 50),
        ]);
    }

    /**
     * Bundle item with custom price.
     */
    public function customPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_price' => $price,
        ]);
    }

    /**
     * Required bundle item.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Optional bundle item.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    /**
     * Bundle item with specific order.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Create a Starter Kit bundle item (1 item with 10% discount).
     */
    public function starterKit(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
            'discount_percentage' => 10,
            'is_required' => true,
        ]);
    }

    /**
     * Create a Premium bundle item (2 items with 20% discount).
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 2,
            'discount_percentage' => 20,
            'is_required' => true,
        ]);
    }

    /**
     * Create a Value Pack bundle item (3+ items with 25% discount).
     */
    public function valuePack(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(3, 5),
            'discount_percentage' => 25,
            'is_required' => true,
        ]);
    }

    /**
     * Create a Bonus item (free or heavily discounted).
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
            'discount_percentage' => $this->faker->randomFloat(2, 80, 100),
            'is_required' => false,
            'meta' => [
                'notes' => 'Bonus item - Limited time offer',
                'badge' => 'FREE',
            ],
        ]);
    }

    /**
     * Create an Add-on item (small discount, optional).
     */
    public function addon(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
            'discount_percentage' => $this->faker->randomFloat(2, 5, 15),
            'is_required' => false,
            'meta' => [
                'notes' => 'Optional add-on',
                'badge' => 'Add-on',
            ],
        ]);
    }
}
