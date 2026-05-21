<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_organizations(): void
    {
        $admin = User::factory()->superAdmin()->create();
        app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($admin)
            ->get('/admin/organizations')
            ->assertOk()
            ->assertSee('Acme SHPK', false);
    }

    public function test_super_admin_can_change_plan_and_suspend(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];

        $this->actingAs($admin)
            ->put("/admin/organizations/{$organization->id}/plan", [
                'plan' => SubscriptionPlan::Pro->value,
            ])
            ->assertRedirect();

        $this->assertSame(SubscriptionPlan::Pro, $organization->fresh()->plan);

        $this->actingAs($admin)
            ->post("/admin/organizations/{$organization->id}/suspend")
            ->assertRedirect();

        $this->assertNotNull($organization->fresh()->suspended_at);

        $this->actingAs($result['user'])
            ->get($this->workspaceRoute('dashboard', $organization))
            ->assertForbidden();
    }

    public function test_super_admin_can_impersonate_owner(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($admin)
            ->post("/admin/organizations/{$result['organization']->id}/impersonate")
            ->assertRedirect($this->workspaceRoute('dashboard', $result['organization']));

        $this->assertAuthenticatedAs($result['user']);
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'impersonation.started',
            'organization_id' => $result['organization']->id,
        ]);

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));

        $this->assertTrue(
            AdminAuditLog::query()->where('action', 'impersonation.ended')->exists()
        );
    }

    public function test_impersonation_bypasses_suspended_organization(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['suspended_at' => now()]);

        $this->actingAs($admin)
            ->post("/admin/organizations/{$organization->id}/impersonate")
            ->assertRedirect($this->workspaceRoute('dashboard', $organization));

        $this->actingAs($result['user'])
            ->withSession(['impersonator_id' => $admin->id, 'current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('dashboard', $organization))
            ->assertOk();
    }

    public function test_super_admin_can_search_users_and_extend_trial(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $trialEnd = now()->addDays(30)->format('Y-m-d');

        $this->actingAs($admin)
            ->get('/admin/users?search=owner@acme')
            ->assertOk()
            ->assertSee('owner@acme.test', false);

        $this->actingAs($admin)
            ->put("/admin/organizations/{$organization->id}/trial", [
                'trial_ends_at' => $trialEnd,
            ])
            ->assertRedirect();

        $this->assertSame($trialEnd, $organization->fresh()->trial_ends_at->format('Y-m-d'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'organization.trial_extended',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_super_admin_can_grant_super_admin_to_another_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->create(['email' => 'hr@acme.test']);

        $this->actingAs($admin)
            ->put(route('admin.users.super-admin', $target), ['is_super_admin' => '1'])
            ->assertRedirect();

        $this->assertTrue($target->fresh()->isSuperAdmin());

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'user.super_admin_granted',
            'target_user_id' => $target->id,
        ]);
    }

    public function test_super_admin_cannot_change_own_super_admin_status(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.super-admin', $admin), ['is_super_admin' => '0'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_audit_log_page_is_available(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee(__('admin.audit.heading'), false);
    }
}
