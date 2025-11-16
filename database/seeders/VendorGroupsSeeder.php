<?php

namespace Database\Seeders;

use App\Models\VendorGroup;
use Illuminate\Database\Seeder;

class VendorGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'default_discount_percentage' => 15.00,
                'minimum_order_amount' => 500.000,
                'allowed_features' => ['priority_shipping', 'extended_credit', 'special_promotions'],
                'priority_level' => 10,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'default_discount_percentage' => 10.00,
                'minimum_order_amount' => 300.000,
                'allowed_features' => ['priority_shipping', 'special_promotions'],
                'priority_level' => 8,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'default_discount_percentage' => 5.00,
                'minimum_order_amount' => 100.000,
                'allowed_features' => [],
                'priority_level' => 5,
            ],
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'default_discount_percentage' => 0.00,
                'minimum_order_amount' => 50.000,
                'allowed_features' => [],
                'priority_level' => 1,
            ],
        ];

        foreach ($groups as $group) {
            VendorGroup::create($group);
        }

        $this->command->info('Vendor groups created successfully!');
    }
}
