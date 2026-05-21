<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\DemoDataService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function demo(): array
    {
        return app(DemoDataService::class)->seed();
    }

    public function test_guest_cannot_access_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_super_admin_dashboard_and_organization_detail(): void
    {
        $demo = $this->demo();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Demo Corporation SHPK', false);

        $this->actingAs($admin)
            ->get(route('admin.organizations.show', $organization))
            ->assertOk()
            ->assertSee('owner@demo.test', false);
    }

    public function test_super_admin_can_unsuspend_organization(): void
    {
        $demo = $this->demo();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];
        $organization->update(['suspended_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.organizations.unsuspend', $organization))
            ->assertRedirect();

        $this->assertNull($organization->fresh()->suspended_at);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'organization.unsuspended',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_super_admin_user_show_revoke_and_filter(): void
    {
        $demo = $this->demo();
        $admin = $demo['super_admin'];
        $target = User::factory()->create(['email' => 'temp@demo.test']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('temp@demo.test', false);

        $this->actingAs($admin)
            ->put(route('admin.users.super-admin', $target), ['is_super_admin' => '1'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get('/admin/users?super_admin=1')
            ->assertOk()
            ->assertSee('temp@demo.test', false);

        $this->actingAs($admin)
            ->put(route('admin.users.super-admin', $target), ['is_super_admin' => '0'])
            ->assertRedirect();

        $this->assertFalse($target->fresh()->isSuperAdmin());

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'user.super_admin_revoked',
            'target_user_id' => $target->id,
        ]);
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create(['email' => 'other-admin@test.com']);

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@linked.test',
            'password123',
            'Linked Org',
        );

        $result['organization']->users()->attach($other->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $other), [
                'organization_id' => $result['organization']->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_super_admin_can_impersonate_hr_user_in_workspace(): void
    {
        $demo = $this->demo();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];
        $hr = $demo['hr'];

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $hr), [
                'organization_id' => $organization->id,
            ])
            ->assertRedirect($this->workspaceRoute('dashboard', $organization));

        $this->assertAuthenticatedAs($hr);
        $this->assertSame($admin->id, session('impersonator_id'));
    }

    public function test_audit_log_filters(): void
    {
        $demo = $this->demo();
        $admin = $demo['super_admin'];
        $organization = $demo['organization'];

        $this->actingAs($admin)
            ->put("/admin/organizations/{$organization->id}/plan", [
                'plan' => SubscriptionPlan::Enterprise->value,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', [
                'action' => 'organization.plan_changed',
                'organization_id' => $organization->id,
            ]))
            ->assertOk()
            ->assertSee('organization.plan_changed', false);
    }

    public function test_demo_data_seeder_builds_expected_records(): void
    {
        $demo = $this->demo();

        $this->assertTrue($demo['super_admin']->isSuperAdmin());
        $this->assertSame(SubscriptionPlan::Pro, $demo['organization']->plan);
        $this->assertDatabaseCount('employees', 4);
        $this->assertDatabaseHas('payroll_runs', ['organization_id' => $demo['organization']->id]);
        $this->assertDatabaseHas('departments', ['name' => 'Operations']);
        $this->assertDatabaseHas('invoices', ['client_name' => 'Beta Client SHPK']);
        $this->assertDatabaseHas('expense_claims', ['title' => 'Client visit taxi']);
        $this->assertDatabaseHas('projects', ['name' => 'Website refresh']);
        $this->assertDatabaseHas('chat_messages', ['body' => 'Welcome to the Demo Corporation workspace!']);
    }
}
