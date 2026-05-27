<?php

namespace Tests\Feature;

use App\Enums\EmploymentStatus;
use App\Enums\PayrollRunStatus;
use App\Enums\SubscriptionPlan;
use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayrollRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_redirects_to_billing(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $result['organization']->update(['plan' => SubscriptionPlan::Starter]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('payroll.index', $result['organization']))
            ->assertRedirect($this->workspaceRoute('settings.billing', $result['organization']).'#plans')
            ->assertSessionHas('error');
    }

    public function test_pro_plan_can_create_lock_and_view_payslip(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 1000.00,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('payroll.index', $organization))
            ->assertOk();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll.store', $organization), [
                'year' => 2026,
                'month' => 5,
            ])
            ->assertRedirect();

        $run = PayrollRun::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($run);

        $this->assertSame('2026-05', $run->periodSlug());

        $showPath = $this->workspaceRoute('payroll.show', $organization, ['payrollRun' => $run->periodSlug()]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($showPath)
            ->assertOk();

        $this->assertStringContainsString('/payroll/'.$run->periodSlug(), $run->showUrl());
        $this->assertStringNotContainsString('/payroll/'.$run->id, $run->showUrl());

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('payroll.show', $organization, ['payrollRun' => $run->id]))
            ->assertRedirect($run->showUrl());
        $this->assertTrue($run->isDraft());
        $this->assertSame(1, $run->items()->count());

        $item = $run->items()->first();
        $this->assertSame('1000.00', (string) $item->gross_salary);
        $this->assertSame('1000.00', (string) $item->base_gross_salary);
        $this->assertSame('0.00', (string) $item->allowances);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->put($this->workspaceRoute('payroll.update', $organization, ['payrollRun' => $run->id]), [
                'items' => [
                    $item->id => ['gross_salary' => 1200],
                ],
            ])
            ->assertRedirect($run->showUrl());

        $item->refresh();
        $this->assertSame('1200.00', (string) $item->gross_salary);
        $this->assertSame('1200.00', (string) $item->base_gross_salary);
        $this->assertSame('0.00', (string) $item->allowances);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll.lock', $organization, ['payrollRun' => $run]))
            ->assertRedirect($run->showUrl());

        $run->refresh();
        $this->assertTrue($run->isLocked());
        $this->assertSame(PayrollRunStatus::Locked, $run->status);

        $payslipUrl = $this->workspaceRoute('payroll.payslip', $organization, [
            'payrollRun' => $run,
            'item' => $item->id,
        ]);
        $this->assertStringContainsString('/payroll/'.$run->periodSlug().'/', $payslipUrl);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($payslipUrl)
            ->assertOk()
            ->assertSee($employee->fullName());

        $pdfResponse = $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('payroll.payslip.pdf', $organization, [
                'payrollRun' => $run,
                'item' => $item->id,
            ]));
        $pdfResponse->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            strtolower((string) $pdfResponse->headers->get('content-type')),
        );
        $this->assertNotEmpty($pdfResponse->getContent());

        $zipResponse = $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->get($this->workspaceRoute('payroll.export-pdfs', $organization, [
                'payrollRun' => $run,
            ]));
        $zipResponse->assertOk();
        $this->assertStringContainsString(
            'zip',
            strtolower((string) $zipResponse->headers->get('content-type')),
        );
        $zipBinary = $zipResponse->streamedContent();
        $this->assertGreaterThan(100, strlen($zipBinary));
        $this->assertSame('PK', substr($zipBinary, 0, 2));

        Mail::fake();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll.payslip.email', $organization, [
                'payrollRun' => $run,
                'item' => $item->id,
            ]))
            ->assertRedirect($run->showUrl())
            ->assertSessionHas('alert');

        Mail::assertSent(PayslipMail::class, 1);
        Mail::assertSent(PayslipMail::class, fn (PayslipMail $mail) => $mail->hasTo((string) $employee->email));
    }

    public function test_bulk_payslip_email_skips_employees_without_email(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@bulk-mail.test',
            'password123',
            'Bulk Mail SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
            'email' => 'payrolled@test.example',
            'gross_salary' => 800,
        ]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
            'email' => null,
            'gross_salary' => 900,
        ]);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($result['user'])
            ->withSession($session)
            ->post($this->workspaceRoute('payroll.store', $organization), [
                'year' => 2026,
                'month' => 8,
            ])
            ->assertRedirect();

        $run = PayrollRun::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($run);
        $this->assertSame(2, $run->items()->count());

        $this->actingAs($result['user'])
            ->withSession($session)
            ->post($this->workspaceRoute('payroll.lock', $organization, ['payrollRun' => $run->id]))
            ->assertRedirect();

        Mail::fake();

        $this->actingAs($result['user'])
            ->withSession($session)
            ->post($this->workspaceRoute('payroll.email-payslips', $organization, ['payrollRun' => $run]))
            ->assertRedirect($run->showUrl())
            ->assertSessionHas('alert');

        Mail::assertSent(PayslipMail::class, 1);
        Mail::assertSent(PayslipMail::class, fn (PayslipMail $mail) => $mail->hasTo('payrolled@test.example'));
    }

    public function test_duplicate_period_is_rejected(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
        ]);

        $session = ['current_organization_id' => $organization->id];
        $payload = ['year' => 2026, 'month' => 3];

        $this->actingAs($result['user'])
            ->withSession($session)
            ->post($this->workspaceRoute('payroll.store', $organization), $payload)
            ->assertRedirect();

        $this->actingAs($result['user'])
            ->withSession($session)
            ->from($this->workspaceRoute('payroll.create', $organization))
            ->post($this->workspaceRoute('payroll.store', $organization), $payload)
            ->assertRedirect($this->workspaceRoute('payroll.create', $organization))
            ->assertSessionHasErrors('month');
    }

    public function test_payroll_run_includes_employee_allowances_in_taxable_gross(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@allowances.test',
            'password123',
            'Allow Co',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 900,
            'monthly_allowances' => 100,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll.store', $organization), [
                'year' => 2027,
                'month' => 2,
            ])
            ->assertRedirect();

        $run = PayrollRun::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($run);
        $item = $run->items()->first();
        $this->assertSame('900.00', (string) $item->base_gross_salary);
        $this->assertSame('100.00', (string) $item->allowances);
        $this->assertSame('1000.00', (string) $item->gross_salary);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->put($this->workspaceRoute('payroll.update', $organization, ['payrollRun' => $run->id]), [
                'items' => [
                    $item->id => [
                        'base_gross_salary' => 800,
                        'allowances' => 250,
                    ],
                ],
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('800.00', (string) $item->base_gross_salary);
        $this->assertSame('250.00', (string) $item->allowances);
        $this->assertSame('1050.00', (string) $item->gross_salary);
    }

    public function test_payroll_exempt_allowance_adds_to_net_without_taxable_gross(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@exempt.test',
            'password123',
            'Exempt Co',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Pro]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 600,
            'monthly_allowances' => 0,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->post($this->workspaceRoute('payroll.store', $organization), [
                'year' => 2028,
                'month' => 4,
            ])
            ->assertRedirect();

        $run = PayrollRun::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($run);
        $item = $run->items()->first();

        $baselineNet = (float) $item->net_salary;
        $this->assertSame('600.00', (string) $item->gross_salary);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $organization->id])
            ->put($this->workspaceRoute('payroll.update', $organization, ['payrollRun' => $run->id]), [
                'items' => [
                    $item->id => [
                        'base_gross_salary' => 600,
                        'allowance_lines' => [
                            [
                                'label' => 'Statutory exempt reimbursement',
                                'amount' => 40,
                                'tax_treatment' => 'exempt_statutory',
                                'kind' => 'one_off',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('600.00', (string) $item->gross_salary);
        $this->assertSame('0.00', (string) $item->allowances);
        $this->assertSame('40.00', (string) $item->exempt_allowances_total);
        $this->assertEquals(round($baselineNet + 40, 2), round((float) $item->net_salary, 2));
    }
}
