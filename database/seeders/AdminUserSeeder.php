<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@b2bplatform.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '+216 71 123 456',
            'locale' => 'fr',
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@b2bplatform.com');
        $this->command->info('Password: password');
    }
}
