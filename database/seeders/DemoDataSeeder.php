<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@b2b-platform.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create demo vendors
        $vendor1 = User::firstOrCreate(
            ['email' => 'vendor1@example.com'],
            [
                'name' => 'Demo Vendor 1',
                'password' => Hash::make('password'),
                'role' => 'vendor',
            ]
        );

        $vendor2 = User::firstOrCreate(
            ['email' => 'vendor2@example.com'],
            [
                'name' => 'Demo Vendor 2',
                'password' => Hash::make('password'),
                'role' => 'vendor',
            ]
        );

        // Create demo products for vendor1
        $categories = ['Electronics', 'Furniture', 'Office Supplies', 'Equipment'];
        
        foreach ($categories as $category) {
            for ($i = 1; $i <= 10; $i++) {
                Product::firstOrCreate(
                    [
                        'vendor_id' => $vendor1->id,
                        'sku' => strtoupper(substr($category, 0, 3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    ],
                    [
                        'name' => "{$category} Product {$i}",
                        'description' => "High-quality {$category} product for B2B wholesale",
                        'category' => $category,
                        'price' => rand(50, 1000),
                        'stock' => rand(10, 100),
                        'moq' => rand(5, 20),
                    ]
                );
            }
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Admin: admin@b2b-platform.com / password');
        $this->command->info('Vendor 1: vendor1@example.com / password');
        $this->command->info('Vendor 2: vendor2@example.com / password');
    }
}
