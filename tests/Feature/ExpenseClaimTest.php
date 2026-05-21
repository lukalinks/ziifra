<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseClaimStatus;
use App\Enums\OrganizationRole;
use App\Enums\SubscriptionPlan;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_expense_and_hr_approves(): void
    {
        Storage::fake('local');

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employeeUser = User::factory()->create(['email' => 'staff@acme.test']);
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'email' => 'staff@acme.test',
            'user_id' => $employeeUser->id,
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('expenses.store', $result['organization']), [
                'category' => ExpenseCategory::Travel->value,
                'title' => 'Taxi to client',
                'amount' => 25.50,
                'expense_date' => now()->format('Y-m-d'),
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect();

        $claim = ExpenseClaim::query()->first();
        $this->assertNotNull($claim);
        $this->assertSame(ExpenseClaimStatus::Pending, $claim->status);
        Storage::disk('local')->assertExists($claim->receipt_path);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('expenses.approve', $result['organization'], ['expenseClaim' => $claim]))
            ->assertRedirect();

        $this->assertSame(ExpenseClaimStatus::Approved, $claim->fresh()->status);
    }

    public function test_hr_can_submit_expense_for_employee(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('expenses.store', $result['organization']), [
                'employee_id' => $employee->id,
                'category' => ExpenseCategory::Office->value,
                'title' => 'Office supplies',
                'amount' => 80,
                'expense_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_claims', [
            'employee_id' => $employee->id,
            'title' => 'Office supplies',
        ]);
    }

    public function test_manager_can_view_team_expense_but_not_invoices(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Pro);

        $managerUser = User::factory()->create();
        $result['organization']->users()->attach($managerUser->id, [
            'role' => OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($managerUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('expenses.index', $result['organization']))
            ->assertOk();

        $this->actingAs($managerUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('invoices.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_receipt_scan_returns_service_unavailable_without_api_key(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        config(['services.openai.key' => null]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('expenses.scan-receipt', $result['organization']), [
                'receipt' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(503)
            ->assertJson(['success' => false]);
    }

    public function test_receipt_scan_extracts_fields_from_image(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->useOrganizationPlan($result['organization'], SubscriptionPlan::Starter);

        config([
            'services.openai.key' => 'test-key',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'Coffee Shop',
                                'amount' => 14.75,
                                'expense_date' => '2026-05-10',
                                'category' => 'meals',
                                'notes' => 'Receipt #1234',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('expenses.scan-receipt', $result['organization']), [
                'receipt' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Coffee Shop',
                    'amount' => 14.75,
                    'expense_date' => '2026-05-10',
                    'category' => 'meals',
                    'notes' => 'Receipt #1234',
                ],
            ]);
    }
}
