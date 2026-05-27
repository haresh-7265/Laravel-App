<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GatePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_gates(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->actingAs($customer);

        $this->assertFalse(Gate::allows('view-admin-dashboard'));
        $this->assertFalse(Gate::allows('manage-products'));
        $this->assertFalse(Gate::allows('manage-orders'));
        $this->assertFalse(Gate::allows('impersonate-users'));
        $this->assertFalse(Gate::allows('view-analytics'));
    }

    public function test_admin_user_can_access_gates(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('view-admin-dashboard'));
        $this->assertTrue(Gate::allows('manage-products'));
        $this->assertTrue(Gate::allows('manage-orders'));
        $this->assertTrue(Gate::allows('impersonate-users'));
        $this->assertTrue(Gate::allows('view-analytics'));
    }

    public function test_admin_guard_user_can_access_gates_via_before_hook(): void
    {
        $adminGuardUser = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($adminGuardUser, 'admin');

        $this->assertTrue(Gate::allows('view-admin-dashboard'));
        $this->assertTrue(Gate::allows('manage-products'));
        $this->assertTrue(Gate::allows('manage-orders'));
        $this->assertTrue(Gate::allows('impersonate-users'));
        $this->assertTrue(Gate::allows('view-analytics'));
    }

    public function test_super_admin_bypasses_all_gates_via_email(): void
    {
        // Check via email
        $superAdminEmail = User::factory()->create([
            'email' => 'superadmin@example.com',
            'role' => 'customer', // even if role is customer, email bypasses it
        ]);

        $this->actingAs($superAdminEmail);
        $this->assertTrue(Gate::allows('view-admin-dashboard'));
        $this->assertTrue(Gate::allows('manage-products'));
        $this->assertTrue(Gate::allows('manage-orders'));
        $this->assertTrue(Gate::allows('impersonate-users'));
        $this->assertTrue(Gate::allows('view-analytics'));
    }

    public function test_gate_decisions_are_logged_via_after_hook(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug');
        Log::shouldReceive('warning');
        Log::shouldReceive('info')
            ->atLeast()
            ->once()
            ->with('Gate authorization decision', \Mockery::on(function ($data) {
                return isset($data['ability']) && $data['ability'] === 'view-admin-dashboard';
            }));

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->actingAs($customer);
        Gate::allows('view-admin-dashboard');
    }
}
