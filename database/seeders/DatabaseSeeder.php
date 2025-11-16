<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            VendorGroupsSeeder::class,
            AdminUserSeeder::class,
            CategoriesSeeder::class,
            DemoVendorsSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Database seeded successfully!');
        $this->command->info('===========================================');
        $this->command->info('Admin: admin@b2bplatform.com / password');
        $this->command->info('Vendors: vendor1@example.com, vendor2@example.com, vendor3@example.com / password');
        $this->command->info('===========================================');
    }
}
