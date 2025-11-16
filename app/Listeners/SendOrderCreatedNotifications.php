<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Notifications\OrderCreatedNotification;
use App\Models\User;

class SendOrderCreatedNotifications
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Notify the vendor
        $order->vendor->notify(new OrderCreatedNotification($order));

        // Notify admins
        $admins = User::admins()->active()->get();
        foreach ($admins as $admin) {
            $admin->notify(new OrderCreatedNotification($order));
        }
    }
}
