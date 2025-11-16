<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories with unlimited hierarchy
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_data')->nullable(); // SEO, custom fields
            $table->timestamps();
            
            $table->index(['parent_id', 'is_active']);
        });

        // Product Attributes (configurable per category)
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // text, number, select, multiselect, color, boolean
            $table->json('options')->nullable(); // For select/multiselect types
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variant')->default(false); // Can create variants
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Category Attributes (which attributes apply to which category)
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('product_attributes')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['category_id', 'attribute_id']);
        });

        // Add columns to products table
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('product_categories')->onDelete('set null');
            $table->string('type')->default('simple')->after('category_id'); // simple, variable, bundle, configurable
            $table->json('attributes')->nullable()->after('description'); // Product attribute values
            $table->json('specifications')->nullable(); // Technical specs
            $table->json('dimensions')->nullable(); // L x W x H, weight
            $table->json('images')->nullable(); // Multiple images
            $table->string('video_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_new')->default(false);
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();
            $table->json('tags')->nullable();
            
            $table->index(['category_id', 'is_active']);
            $table->index('type');
        });

        // Product Variants (for variable products)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->json('attributes'); // Variant attributes (e.g., color: red, size: L)
            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('moq')->default(1);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
        });

        // Product Bundles (for bundle products)
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['bundle_product_id', 'product_id']);
        });

        // Product Options (for configurable products)
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Engraving Text", "Gift Wrapping"
            $table->string('type'); // text, select, checkbox
            $table->json('values')->nullable(); // For select type
            $table->decimal('price_modifier', 10, 2)->default(0);
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Product Reviews & Ratings
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->text('review')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'is_approved']);
        });

        // Product Collections/Tags
        Schema::create('product_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('collection_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('product_collections')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['collection_id', 'product_id']);
        });

        // Product Price Tiers (volume discounts)
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->index('product_id');
        });

        // Product Cross-sells & Upsells
        Schema::create('product_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_product_id')->constrained('products')->onDelete('cascade');
            $table->string('relation_type'); // cross_sell, up_sell, alternative, accessory
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['product_id', 'related_product_id', 'relation_type']);
        });

        // Product Inventory Tracking
        Schema::create('product_inventory_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->integer('quantity_change');
            $table->integer('quantity_after');
            $table->string('type'); // adjustment, sale, return, damaged, transfer
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory_log');
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('product_collections');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_variants');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'category_id', 'type', 'attributes', 'specifications', 
                'dimensions', 'images', 'video_url', 'is_featured', 
                'is_bestseller', 'is_new', 'available_from', 
                'available_until', 'tags'
            ]);
        });
        
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_categories');
    }
};
