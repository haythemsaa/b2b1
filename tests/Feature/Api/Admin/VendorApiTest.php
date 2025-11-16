<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected VendorGroup $vendorGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->vendorGroup = VendorGroup::factory()->create(['name' => 'Standard']);
    }

    /** @test */
    public function admin_can_list_all_vendors()
    {
        $vendors = User::factory()->count(5)->create(['role' => 'vendor']);

        foreach ($vendors as $vendor) {
            VendorProfile::factory()->create(['user_id' => $vendor->id]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/vendors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status', 'vendor_profile'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function vendor_cannot_access_admin_vendors_endpoint()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $response = $this->actingAs($vendor, 'sanctum')
            ->getJson('/api/admin/vendors');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_vendors()
    {
        $response = $this->getJson('/api/admin/vendors');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_create_vendor()
    {
        $vendorData = [
            'name' => 'John Doe',
            'email' => 'vendor@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'ABC Trading',
            'tax_id' => 'TAX123456',
            'phone' => '+21612345678',
            'vendor_group_id' => $this->vendorGroup->id,
            'credit_limit' => 5000.000,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', $vendorData);

        $response->assertStatus(201)
            ->assertJson([
                'email' => 'vendor@example.com',
                'name' => 'John Doe',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'vendor@example.com',
            'role' => 'vendor',
        ]);

        $this->assertDatabaseHas('vendor_profiles', [
            'company_name' => 'ABC Trading',
            'tax_id' => 'TAX123456',
        ]);
    }

    /** @test */
    public function vendor_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'company_name']);
    }

    /** @test */
    public function vendor_email_must_be_unique()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', [
                'name' => 'Test Vendor',
                'email' => 'existing@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'company_name' => 'Test Company',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function vendor_password_must_be_confirmed()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', [
                'name' => 'Test Vendor',
                'email' => 'test@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'DifferentPassword',
                'company_name' => 'Test Company',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function admin_can_view_specific_vendor()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'email' => 'vendor@test.com',
        ]);

        VendorProfile::factory()->create([
            'user_id' => $vendor->id,
            'company_name' => 'Test Company',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/vendors/{$vendor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'email' => 'vendor@test.com',
                'vendor_profile' => [
                    'company_name' => 'Test Company',
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_vendor()
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'name' => 'Old Name']);
        VendorProfile::factory()->create([
            'user_id' => $vendor->id,
            'credit_limit' => 1000.000,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/vendors/{$vendor->id}", [
                'name' => 'New Name',
                'credit_limit' => 2000.000,
            ]);

        $response->assertStatus(200);

        $vendor->refresh();
        $this->assertEquals('New Name', $vendor->name);
        $this->assertEquals(2000.000, $vendor->vendorProfile->credit_limit);
    }

    /** @test */
    public function admin_can_update_vendor_status()
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/vendors/{$vendor->id}", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(200);

        $vendor->refresh();
        $this->assertEquals('inactive', $vendor->status);
    }

    /** @test */
    public function admin_can_update_vendor_group()
    {
        $newGroup = VendorGroup::factory()->create(['name' => 'VIP']);

        $vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $vendor->id,
            'vendor_group_id' => $this->vendorGroup->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/vendors/{$vendor->id}", [
                'vendor_group_id' => $newGroup->id,
            ]);

        $response->assertStatus(200);

        $vendor->vendorProfile->refresh();
        $this->assertEquals($newGroup->id, $vendor->vendorProfile->vendor_group_id);
    }

    /** @test */
    public function admin_can_delete_vendor()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create(['user_id' => $vendor->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/vendors/{$vendor->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $vendor->id]);
    }

    /** @test */
    public function admin_can_filter_vendors_by_status()
    {
        User::factory()->count(3)->create(['role' => 'vendor', 'status' => 'active']);
        User::factory()->count(2)->create(['role' => 'vendor', 'status' => 'inactive']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/vendors?status=active');

        $response->assertStatus(200);

        $vendors = $response->json('data');
        foreach ($vendors as $vendor) {
            $this->assertEquals('active', $vendor['status']);
        }
    }

    /** @test */
    public function admin_can_filter_vendors_by_group()
    {
        $vipGroup = VendorGroup::factory()->create(['name' => 'VIP']);

        $vendor1 = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $vendor1->id,
            'vendor_group_id' => $this->vendorGroup->id,
        ]);

        $vendor2 = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $vendor2->id,
            'vendor_group_id' => $vipGroup->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/vendors?vendor_group_id={$vipGroup->id}");

        $response->assertStatus(200);

        $vendors = $response->json('data');
        foreach ($vendors as $vendor) {
            $this->assertEquals($vipGroup->id, $vendor['vendor_profile']['vendor_group_id']);
        }
    }

    /** @test */
    public function admin_can_search_vendors()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'name' => 'Special Vendor Name',
        ]);
        VendorProfile::factory()->create(['user_id' => $vendor->id]);

        User::factory()->count(5)->create(['role' => 'vendor']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/vendors?search=Special');

        $response->assertStatus(200);

        $vendors = $response->json('data');
        $this->assertTrue(count($vendors) > 0);
        $this->assertStringContainsString('Special', $vendors[0]['name']);
    }

    /** @test */
    public function admin_can_get_vendor_groups()
    {
        VendorGroup::factory()->count(4)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/vendors/groups');

        $response->assertStatus(200);

        $this->assertGreaterThanOrEqual(4, count($response->json()));
    }

    /** @test */
    public function admin_can_paginate_vendors()
    {
        $vendors = User::factory()->count(30)->create(['role' => 'vendor']);
        foreach ($vendors as $vendor) {
            VendorProfile::factory()->create(['user_id' => $vendor->id]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/vendors?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /** @test */
    public function created_vendor_password_is_hashed()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', [
                'name' => 'Test Vendor',
                'email' => 'test@example.com',
                'password' => 'PlainPassword123!',
                'password_confirmation' => 'PlainPassword123!',
                'company_name' => 'Test Company',
            ]);

        $response->assertStatus(201);

        $vendor = User::where('email', 'test@example.com')->first();
        $this->assertNotEquals('PlainPassword123!', $vendor->password);
        $this->assertTrue(Hash::check('PlainPassword123!', $vendor->password));
    }

    /** @test */
    public function admin_cannot_view_deleted_vendor()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        VendorProfile::factory()->create(['user_id' => $vendor->id]);

        $vendor->delete();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/vendors/{$vendor->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function vendor_profile_is_created_with_default_values()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/vendors', [
                'name' => 'Test Vendor',
                'email' => 'test@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'company_name' => 'Test Company',
            ]);

        $response->assertStatus(201);

        $vendor = User::where('email', 'test@example.com')->first();
        $profile = $vendor->vendorProfile;

        $this->assertNotNull($profile);
        $this->assertEquals(0, $profile->current_balance);
        $this->assertFalse($profile->priority_shipping);
    }
}
