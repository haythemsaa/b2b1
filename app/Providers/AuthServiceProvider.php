<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Order;
use App\Models\ChatConversation;
use App\Policies\ProductPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ChatConversationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        ChatConversation::class => ChatConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
