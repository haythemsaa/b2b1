<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;

class ProductObserver
{
    /**
     * Handle the Product "updated" event.
     * Send low stock alert when stock drops below threshold
     */
    public function updated(Product $product): void
    {
        // Check if stock_quantity changed and is now low
        if ($product->wasChanged('stock_quantity')) {
            $oldStock = $product->getOriginal('stock_quantity');
            $newStock = $product->stock_quantity;

            // If stock just went below threshold, send alert
            if ($oldStock >= $product->low_stock_threshold && $newStock < $product->low_stock_threshold) {
                $this->sendLowStockAlert($product);
            }
        }
    }

    /**
     * Send low stock alert to all admins
     */
    protected function sendLowStockAlert(Product $product): void
    {
        $admins = User::admins()->active()->get();

        foreach ($admins as $admin) {
            $admin->notify(new LowStockAlertNotification($product));
        }
    }
}
