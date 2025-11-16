<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\NewChatMessage;
use App\Listeners\SendOrderCreatedNotifications;
use App\Listeners\SendOrderStatusUpdatedNotifications;
use App\Listeners\SendNewChatMessageNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotifications::class,
        ],
        OrderStatusUpdated::class => [
            SendOrderStatusUpdatedNotifications::class,
        ],
        NewChatMessage::class => [
            SendNewChatMessageNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
