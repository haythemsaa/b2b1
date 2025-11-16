<?php

namespace Database\Factories;

use App\Models\Product\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'parent_id' => null,
            'is_active' => true,
            'order' => $this->faker->numberBetween(0, 100),
            'meta' => [
                'icon' => $this->faker->optional()->randomElement([
                    'laptop', 'phone', 'tablet', 'watch', 'headphones',
                    'camera', 'tv', 'speaker', 'keyboard', 'mouse'
                ]),
                'color' => $this->faker->optional()->hexColor(),
                'featured' => $this->faker->boolean(20),
            ],
        ];
    }

    /**
     * Indicate that the category is a root category.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the category is a child of another category.
     */
    public function child(?int $parentId = null): static
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_id' => $parentId ?? ProductCategory::factory()->create()->id,
            ];
        });
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => array_merge($attributes['meta'] ?? [], [
                'featured' => true,
            ]),
        ]);
    }

    /**
     * Create a category with specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Create an electronics category.
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
            'meta' => [
                'icon' => 'laptop',
                'color' => '#3B82F6',
                'featured' => true,
            ],
        ]);
    }

    /**
     * Create a clothing category.
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Clothing',
            'slug' => 'clothing',
            'description' => 'Apparel and fashion items',
            'meta' => [
                'icon' => 'shirt',
                'color' => '#EC4899',
                'featured' => true,
            ],
        ]);
    }

    /**
     * Create a food category.
     */
    public function food(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Food & Beverages',
            'slug' => 'food-beverages',
            'description' => 'Food products and drinks',
            'meta' => [
                'icon' => 'utensils',
                'color' => '#10B981',
                'featured' => true,
            ],
        ]);
    }
}
