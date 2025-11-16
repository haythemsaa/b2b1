<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->isVendor() || $user->isAdmin();
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $order->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->isVendor() && $user->status === 'active';
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admin can update orders
        return $user->isAdmin();
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return $order->canBeCancelled();
        }

        if ($user->isVendor() && $order->vendor_id === $user->id) {
            return $order->canBeCancelled() && $order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine if the user can confirm the order.
     */
    public function confirm(User $user, Order $order): bool
    {
        return $user->isAdmin() && $order->status === 'pending';
    }

    /**
     * Determine if the user can ship the order.
     */
    public function ship(User $user, Order $order): bool
    {
        return $user->isAdmin() && $order->status === 'processing';
    }

    /**
     * Determine if the user can deliver the order.
     */
    public function deliver(User $user, Order $order): bool
    {
        return $user->isAdmin() && $order->status === 'shipped';
    }
}
