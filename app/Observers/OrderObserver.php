<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     * Store the old status for comparison in events
     */
    public function updating(Order $order): void
    {
        // Store original status in a temporary attribute for event listeners
        if ($order->isDirty('status')) {
            $order->old_status = $order->getOriginal('status');
        }
    }

    /**
     * Handle the Order "created" event.
     * Set initial timestamps
     */
    public function created(Order $order): void
    {
        // Order created, no additional timestamp needed as created_at is set automatically
    }
}
