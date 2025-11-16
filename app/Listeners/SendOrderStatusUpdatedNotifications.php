<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\OrderShippedNotification;

class SendOrderStatusUpdatedNotifications
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // Get old status from order history (simplified - store in event if needed)
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // Send specific notification for shipped orders
        if ($newStatus === 'shipped') {
            $order->vendor->notify(new OrderShippedNotification($order));
        } else {
            // Send general status update notification
            $order->vendor->notify(new OrderStatusUpdatedNotification($order, $oldStatus, $newStatus));
        }
    }
}
