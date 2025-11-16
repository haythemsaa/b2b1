<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CatalogService;

class ProductPolicy
{
    protected CatalogService $catalogService;

    public function __construct()
    {
        $this->catalogService = app(CatalogService::class);
    }

    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->isVendor() || $user->isAdmin();
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->catalogService->isProductVisible($product, $user);
        }

        return false;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can manage stock for the product.
     */
    public function manageStock(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can manage pricing for the product.
     */
    public function managePricing(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
