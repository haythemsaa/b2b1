<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductAttribute;
use Illuminate\Support\Str;

class AdvancedProductSystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCategories();
        $this->createAttributes();
        $this->assignAttributesToCategories();
    }

    private function createCategories(): void
    {
        // Electronics
        $electronics = ProductCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
            'order' => 1,
            'is_active' => true,
        ]);

        $computers = ProductCategory::create([
            'name' => 'Computers',
            'slug' => 'computers',
            'parent_id' => $electronics->id,
            'order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'parent_id' => $computers->id,
            'order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'Desktops',
            'slug' => 'desktops',
            'parent_id' => $computers->id,
            'order' => 2,
        ]);

        $smartphones = ProductCategory::create([
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'parent_id' => $electronics->id,
            'order' => 2,
        ]);

        // Fashion
        $fashion = ProductCategory::create([
            'name' => 'Fashion & Apparel',
            'slug' => 'fashion',
            'description' => 'Clothing and accessories',
            'order' => 2,
        ]);

        $menClothing = ProductCategory::create([
            'name' => 'Men\'s Clothing',
            'slug' => 'mens-clothing',
            'parent_id' => $fashion->id,
            'order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'T-Shirts',
            'slug' => 'mens-tshirts',
            'parent_id' => $menClothing->id,
            'order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'Shirts',
            'slug' => 'mens-shirts',
            'parent_id' => $menClothing->id,
            'order' => 2,
        ]);

        // Home & Furniture
        $home = ProductCategory::create([
            'name' => 'Home & Furniture',
            'slug' => 'home-furniture',
            'description' => 'Furniture and home decor',
            'order' => 3,
        ]);

        $furniture = ProductCategory::create([
            'name' => 'Furniture',
            'slug' => 'furniture',
            'parent_id' => $home->id,
            'order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'Office Furniture',
            'slug' => 'office-furniture',
            'parent_id' => $furniture->id,
            'order' => 1,
        ]);

        // Tools & Equipment
        $tools = ProductCategory::create([
            'name' => 'Tools & Equipment',
            'slug' => 'tools-equipment',
            'description' => 'Professional tools and equipment',
            'order' => 4,
        ]);

        $powerTools = ProductCategory::create([
            'name' => 'Power Tools',
            'slug' => 'power-tools',
            'parent_id' => $tools->id,
            'order' => 1,
        ]);
    }

    private function createAttributes(): void
    {
        // Electronics attributes
        ProductAttribute::create([
            'name' => 'Processor',
            'slug' => 'processor',
            'type' => 'select',
            'options' => ['Intel i5', 'Intel i7', 'Intel i9', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);

        ProductAttribute::create([
            'name' => 'RAM',
            'slug' => 'ram',
            'type' => 'select',
            'options' => ['4GB', '8GB', '16GB', '32GB', '64GB'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);

        ProductAttribute::create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => 'select',
            'options' => ['128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);

        ProductAttribute::create([
            'name' => 'Screen Size',
            'slug' => 'screen-size',
            'type' => 'select',
            'options' => ['13"', '14"', '15"', '16"', '17"'],
            'is_filterable' => true,
            'is_variant' => false,
        ]);

        // Fashion attributes
        ProductAttribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'is_filterable' => true,
            'is_variant' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_filterable' => true,
            'is_variant' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'select',
            'options' => ['Cotton', 'Polyester', 'Cotton-Polyester Blend', 'Wool', 'Silk', 'Linen'],
            'is_filterable' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Season',
            'slug' => 'season',
            'type' => 'select',
            'options' => ['Spring', 'Summer', 'Fall', 'Winter', 'All Season'],
            'is_filterable' => true,
        ]);

        // Furniture attributes
        ProductAttribute::create([
            'name' => 'Dimensions',
            'slug' => 'dimensions',
            'type' => 'text',
            'is_filterable' => false,
        ]);

        ProductAttribute::create([
            'name' => 'Weight Capacity',
            'slug' => 'weight-capacity',
            'type' => 'number',
            'is_filterable' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Material Type',
            'slug' => 'material-type',
            'type' => 'select',
            'options' => ['Wood', 'Metal', 'Plastic', 'Glass', 'Composite'],
            'is_filterable' => true,
        ]);

        // Tools attributes
        ProductAttribute::create([
            'name' => 'Power',
            'slug' => 'power',
            'type' => 'number',
            'is_filterable' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Voltage',
            'slug' => 'voltage',
            'type' => 'select',
            'options' => ['110V', '220V', '110-220V'],
            'is_filterable' => true,
        ]);

        ProductAttribute::create([
            'name' => 'Certification',
            'slug' => 'certification',
            'type' => 'multiselect',
            'options' => ['CE', 'UL', 'FCC', 'RoHS', 'ISO'],
            'is_filterable' => true,
        ]);
    }

    private function assignAttributesToCategories(): void
    {
        // Laptops
        $laptops = ProductCategory::where('slug', 'laptops')->first();
        if ($laptops) {
            $laptops->attributes()->attach([
                ProductAttribute::where('slug', 'processor')->first()->id => ['is_required' => true, 'order' => 1],
                ProductAttribute::where('slug', 'ram')->first()->id => ['is_required' => true, 'order' => 2],
                ProductAttribute::where('slug', 'storage')->first()->id => ['is_required' => true, 'order' => 3],
                ProductAttribute::where('slug', 'screen-size')->first()->id => ['is_required' => false, 'order' => 4],
            ]);
        }

        // T-Shirts
        $tshirts = ProductCategory::where('slug', 'mens-tshirts')->first();
        if ($tshirts) {
            $tshirts->attributes()->attach([
                ProductAttribute::where('slug', 'size')->first()->id => ['is_required' => true, 'order' => 1],
                ProductAttribute::where('slug', 'color')->first()->id => ['is_required' => true, 'order' => 2],
                ProductAttribute::where('slug', 'material')->first()->id => ['is_required' => false, 'order' => 3],
                ProductAttribute::where('slug', 'season')->first()->id => ['is_required' => false, 'order' => 4],
            ]);
        }

        // Office Furniture
        $officeFurniture = ProductCategory::where('slug', 'office-furniture')->first();
        if ($officeFurniture) {
            $officeFurniture->attributes()->attach([
                ProductAttribute::where('slug', 'dimensions')->first()->id => ['is_required' => true, 'order' => 1],
                ProductAttribute::where('slug', 'weight-capacity')->first()->id => ['is_required' => false, 'order' => 2],
                ProductAttribute::where('slug', 'material-type')->first()->id => ['is_required' => true, 'order' => 3],
                ProductAttribute::where('slug', 'color')->first()->id => ['is_required' => false, 'order' => 4],
            ]);
        }

        // Power Tools
        $powerTools = ProductCategory::where('slug', 'power-tools')->first();
        if ($powerTools) {
            $powerTools->attributes()->attach([
                ProductAttribute::where('slug', 'power')->first()->id => ['is_required' => true, 'order' => 1],
                ProductAttribute::where('slug', 'voltage')->first()->id => ['is_required' => true, 'order' => 2],
                ProductAttribute::where('slug', 'certification')->first()->id => ['is_required' => false, 'order' => 3],
            ]);
        }
    }
}
