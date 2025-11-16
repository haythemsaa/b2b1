<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoVendorsSeeder extends Seeder
{
    public function run(): void
    {
        $vendorGroups = VendorGroup::all()->keyBy('slug');

        $vendors = [
            [
                'user' => [
                    'name' => 'Mohamed Ben Ali',
                    'email' => 'vendor1@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'vendor',
                    'status' => 'active',
                    'phone' => '+216 71 234 567',
                    'locale' => 'fr',
                ],
                'profile' => [
                    'company_name' => 'TechStore Tunisia',
                    'tax_id' => 'TN12345678',
                    'vendor_group_id' => $vendorGroups['vip']->id,
                    'credit_limit' => 50000.000,
                    'payment_term' => 'net_30',
                    'minimum_order_amount' => 500.000,
                    'priority_shipping' => true,
                    'shipping_address' => 'Avenue Habib Bourguiba, Tunis 1000',
                    'billing_address' => 'Avenue Habib Bourguiba, Tunis 1000',
                ],
            ],
            [
                'user' => [
                    'name' => 'Ahmed Gharbi',
                    'email' => 'vendor2@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'vendor',
                    'status' => 'active',
                    'phone' => '+216 71 345 678',
                    'locale' => 'fr',
                ],
                'profile' => [
                    'company_name' => 'Mode & Style SARL',
                    'tax_id' => 'TN87654321',
                    'vendor_group_id' => $vendorGroups['gold']->id,
                    'credit_limit' => 30000.000,
                    'payment_term' => 'net_30',
                    'minimum_order_amount' => 300.000,
                    'priority_shipping' => true,
                    'shipping_address' => 'Rue de la Liberté, Sfax 3000',
                    'billing_address' => 'Rue de la Liberté, Sfax 3000',
                ],
            ],
            [
                'user' => [
                    'name' => 'Fatma Mansouri',
                    'email' => 'vendor3@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'vendor',
                    'status' => 'active',
                    'phone' => '+216 71 456 789',
                    'locale' => 'ar',
                ],
                'profile' => [
                    'company_name' => 'Alimentaire Plus',
                    'tax_id' => 'TN11223344',
                    'vendor_group_id' => $vendorGroups['standard']->id,
                    'credit_limit' => 15000.000,
                    'payment_term' => 'immediate',
                    'minimum_order_amount' => 100.000,
                    'priority_shipping' => false,
                    'shipping_address' => 'Avenue de Carthage, Sousse 4000',
                    'billing_address' => 'Avenue de Carthage, Sousse 4000',
                ],
            ],
        ];

        foreach ($vendors as $vendorData) {
            $user = User::create($vendorData['user']);
            $vendorData['profile']['user_id'] = $user->id;
            VendorProfile::create($vendorData['profile']);
        }

        $this->command->info('Demo vendors created successfully!');
        $this->command->info('Vendor emails: vendor1@example.com, vendor2@example.com, vendor3@example.com');
        $this->command->info('Password: password');
    }
}
