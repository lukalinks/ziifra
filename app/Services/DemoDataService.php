<?php

namespace App\Services;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\PayrollAllowanceKind;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\OrganizationRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTaskPriority;
use App\Enums\PayrollRunStatus;
use App\Enums\ProjectTaskStatus;
use App\Enums\SubscriptionPlan;
use App\Models\ChatMessage;
use App\Models\Department;
use App\Models\PayrollRun;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoDataService
{
    /**
     * @return array{
     *     super_admin: User,
     *     organization: Organization,
     *     owner: User,
     *     hr: User,
     *     manager: User,
     *     employee_user: User,
     *     staff: Employee,
     *     employees: list<Employee>,
     *     project: Project,
     *     invoice: Invoice,
     *     expense_claim: ExpenseClaim,
     *     leave_request: LeaveRequest|null,
     *     payroll_run: PayrollRun,
     *     department: Department,
     * }
     */
    public function seed(): array
    {
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'admin@ziifra.com'],
            [
                'name' => 'ZIIFRA Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ],
        );

        if (! $superAdmin->isSuperAdmin()) {
            $superAdmin->update(['is_super_admin' => true]);
        }

        $existingOwner = User::query()->where('email', 'owner@demo.test')->first();
        $existingOrg = Organization::query()->where('slug', 'demo-corp')->first();

        if ($existingOwner !== null && $existingOrg !== null) {
            $this->ensureSampleRecords($existingOrg, $existingOwner);

            return $this->assembleExistingDemo($superAdmin, $existingOrg, $existingOwner);
        }

        $register = app(RegisterOrganizationService::class)->register(
            'Demo Owner',
            'owner@demo.test',
            'password',
            'Demo Corporation SHPK',
        );

        $organization = $register['organization'];
        $owner = $register['user'];

        $organization->update([
            'slug' => 'demo-corp',
            'plan' => SubscriptionPlan::Pro->value,
            'trial_ends_at' => null,
            'suspended_at' => null,
        ]);

        $hr = User::query()->firstOrCreate(
            ['email' => 'hr@demo.test'],
            ['name' => 'Demo HR', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $organization->users()->syncWithoutDetaching([
            $hr->id => ['role' => OrganizationRole::Hr->value, 'joined_at' => now()],
        ]);

        $managerUser = User::query()->firstOrCreate(
            ['email' => 'manager@demo.test'],
            ['name' => 'Demo Manager', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $organization->users()->syncWithoutDetaching([
            $managerUser->id => ['role' => OrganizationRole::Manager->value, 'joined_at' => now()],
        ]);

        $employeeUser = User::query()->firstOrCreate(
            ['email' => 'employee@demo.test'],
            ['name' => 'Demo Employee', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $organization->users()->syncWithoutDetaching([
            $employeeUser->id => ['role' => OrganizationRole::Employee->value, 'joined_at' => now()],
        ]);

        $department = Department::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Operations',
        ]);

        Employee::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'email' => 'hr@demo.test'],
            [
                'first_name' => 'Drita',
                'last_name' => 'HR',
                'user_id' => $hr->id,
                'department_id' => $department->id,
                'gross_salary' => 1100,
            ],
        );

        $manager = Employee::query()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Mira',
            'last_name' => 'Manager',
            'email' => 'manager@demo.test',
            'user_id' => $managerUser->id,
            'department_id' => $department->id,
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 1200,
        ]);

        $staff = Employee::query()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Era',
            'last_name' => 'Staff',
            'email' => 'employee@demo.test',
            'user_id' => $employeeUser->id,
            'manager_id' => $manager->id,
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 800,
            'monthly_allowances' => 75,
        ]);

        $other = Employee::query()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Luan',
            'last_name' => 'Krasniqi',
            'manager_id' => $manager->id,
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'gross_salary' => 900,
        ]);

        $leaveType = LeaveType::query()
            ->where('organization_id', $organization->id)
            ->first();

        $leaveRequest = null;

        if ($leaveType !== null) {
            $leaveRequest = LeaveRequest::query()->create([
                'organization_id' => $organization->id,
                'employee_id' => $staff->id,
                'leave_type_id' => $leaveType->id,
                'submitted_by_user_id' => $employeeUser->id,
                'start_date' => now()->addWeeks(2)->startOfWeek(),
                'end_date' => now()->addWeeks(2)->startOfWeek()->addDays(2),
                'days' => 3,
                'status' => LeaveRequestStatus::Pending,
                'notes' => 'Family visit',
            ]);
        }

        $invoice = Invoice::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
            'invoice_number' => 'INV-'.now()->year.'-DEMO-001',
            'client_name' => 'Beta Client SHPK',
            'client_email' => 'billing@beta.test',
            'title' => 'March consulting',
            'amount' => 2500,
            'tax_percent' => 18,
            'currency' => 'EUR',
            'issue_date' => now()->startOfMonth(),
            'due_date' => now()->endOfMonth(),
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);

        $expenseClaim = ExpenseClaim::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $staff->id,
            'submitted_by_user_id' => $staff->user_id,
            'category' => ExpenseCategory::Travel,
            'title' => 'Client visit taxi',
            'amount' => 35.50,
            'currency' => 'EUR',
            'expense_date' => now()->subDays(3),
            'status' => ExpenseClaimStatus::Pending,
        ]);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
            'name' => 'Website refresh',
            'description' => 'Marketing site Q2',
            'status' => ProjectStatus::Active,
            'start_date' => now()->startOfMonth(),
            'budget' => 5000,
            'currency' => 'EUR',
        ]);
        $project->members()->sync([$manager->id, $staff->id]);

        ProjectTask::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Homepage wireframes',
            'status' => ProjectTaskStatus::InProgress,
            'priority' => ProjectTaskPriority::High,
            'assigned_employee_id' => $manager->id,
            'sort_order' => 1,
        ]);

        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $staff->id,
            'recorded_by_user_id' => $staff->user_id,
            'clock_in' => now()->subHours(4),
            'clock_out' => now()->subHours(1),
        ]);

        ChatMessage::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'body' => 'Welcome to the Demo Corporation workspace!',
        ]);

        ChatMessage::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $hr->id,
            'body' => 'HR is here if you need help with leave or documents.',
        ]);

        $payrollMonth = (int) now()->subMonth()->month;
        $payrollYear = (int) now()->subMonth()->year;

        $payrollRun = PayrollRun::query()->create([
            'organization_id' => $organization->id,
            'year' => $payrollYear,
            'month' => $payrollMonth,
            'status' => PayrollRunStatus::Draft,
            'rules_snapshot' => config('payroll.kosovo'),
        ]);

        $payrollSvc = app(PayrollRunService::class);

        foreach ([$manager, $staff, $other] as $employee) {
            $item = $payrollRun->items()->create([
                'organization_id' => $organization->id,
                'employee_id' => $employee->id,
                'base_gross_salary' => (float) ($employee->gross_salary ?? 0),
                'allowances' => 0,
                'exempt_allowances_total' => 0,
                'gross_salary' => 0,
                'employee_pension' => 0,
                'employer_pension' => 0,
                'income_tax' => 0,
                'net_salary' => 0,
                'employee_snapshot' => [
                    'name' => $employee->fullName(),
                ],
            ]);

            $allow = (float) ($employee->monthly_allowances ?? 0);

            if ($allow > 0) {
                $item->allowanceLines()->create([
                    'label' => 'Monthly allowances',
                    'amount' => $allow,
                    'tax_treatment' => AllowanceTaxTreatment::Taxable,
                    'kind' => PayrollAllowanceKind::Recurring,
                    'sort_order' => 0,
                ]);
            }

            $payrollSvc->recalculateDraftItem($item->fresh(['allowanceLines']));
        }

        return [
            'super_admin' => $superAdmin,
            'organization' => $organization->fresh(),
            'owner' => $owner,
            'hr' => $hr,
            'manager' => $managerUser,
            'employee_user' => $employeeUser,
            'staff' => $staff,
            'employees' => [$manager, $staff, $other],
            'project' => $project,
            'invoice' => $invoice,
            'expense_claim' => $expenseClaim,
            'leave_request' => $leaveRequest,
            'payroll_run' => $payrollRun,
            'department' => $department,
        ];
    }

    protected function ensureSampleRecords(Organization $organization, User $owner): void
    {
        $employees = Employee::query()->where('organization_id', $organization->id)->get();

        if ($employees->isEmpty()) {
            return;
        }

        $department = Department::query()->where('organization_id', $organization->id)->first();

        if ($department === null) {
            $department = Department::query()->create([
                'organization_id' => $organization->id,
                'name' => 'Operations',
            ]);
        }

        $hrUser = User::query()->where('email', 'hr@demo.test')->first();

        if ($hrUser !== null && ! Employee::query()->where('email', 'hr@demo.test')->exists()) {
            Employee::query()->create([
                'organization_id' => $organization->id,
                'first_name' => 'Drita',
                'last_name' => 'HR',
                'email' => 'hr@demo.test',
                'user_id' => $hrUser->id,
                'department_id' => $department->id,
                'gross_salary' => 1100,
            ]);
        }

        if (! PayrollRun::query()->where('organization_id', $organization->id)->exists()) {
            $payrollMonth = (int) now()->subMonth()->month;
            $payrollYear = (int) now()->subMonth()->year;
            $payrollRun = PayrollRun::query()->create([
                'organization_id' => $organization->id,
                'year' => $payrollYear,
                'month' => $payrollMonth,
                'status' => PayrollRunStatus::Draft,
                'rules_snapshot' => config('payroll.kosovo'),
            ]);

            $payrollSvc = app(PayrollRunService::class);

            foreach ($employees as $employee) {
                $item = $payrollRun->items()->create([
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                    'base_gross_salary' => (float) ($employee->gross_salary ?? 0),
                    'allowances' => 0,
                    'exempt_allowances_total' => 0,
                    'gross_salary' => 0,
                    'employee_pension' => 0,
                    'employer_pension' => 0,
                    'income_tax' => 0,
                    'net_salary' => 0,
                    'employee_snapshot' => ['name' => $employee->fullName()],
                ]);

                $allow = (float) ($employee->monthly_allowances ?? 0);

                if ($allow > 0) {
                    $item->allowanceLines()->create([
                        'label' => 'Monthly allowances',
                        'amount' => $allow,
                        'tax_treatment' => AllowanceTaxTreatment::Taxable,
                        'kind' => PayrollAllowanceKind::Recurring,
                        'sort_order' => 0,
                    ]);
                }

                $payrollSvc->recalculateDraftItem($item->fresh(['allowanceLines']));
            }
        }

        if (! Invoice::query()->where('organization_id', $organization->id)->exists()) {
            Invoice::query()->create([
                'organization_id' => $organization->id,
                'created_by_user_id' => $owner->id,
                'invoice_number' => 'INV-'.now()->year.'-DEMO-001',
                'client_name' => 'Beta Client SHPK',
                'client_email' => 'billing@beta.test',
                'title' => 'March consulting',
                'amount' => 2500,
                'tax_percent' => 18,
                'currency' => 'EUR',
                'issue_date' => now()->startOfMonth(),
                'due_date' => now()->endOfMonth(),
                'status' => InvoiceStatus::Sent,
                'sent_at' => now(),
            ]);
        }

        if (! Project::query()->where('organization_id', $organization->id)->exists()) {
            $project = Project::query()->create([
                'organization_id' => $organization->id,
                'created_by_user_id' => $owner->id,
                'name' => 'Website refresh',
                'status' => ProjectStatus::Active,
            ]);
            $project->members()->sync($employees->pluck('id'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function assembleExistingDemo(User $superAdmin, Organization $organization, User $owner): array
    {
        $organization->update([
            'plan' => SubscriptionPlan::Pro->value,
            'trial_ends_at' => null,
            'suspended_at' => null,
        ]);

        $employees = Employee::query()
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->get()
            ->all();

        return [
            'super_admin' => $superAdmin,
            'organization' => $organization->fresh(),
            'owner' => $owner,
            'hr' => User::query()->where('email', 'hr@demo.test')->first(),
            'manager' => User::query()->where('email', 'manager@demo.test')->first(),
            'employee_user' => User::query()->where('email', 'employee@demo.test')->first(),
            'staff' => Employee::query()->where('email', 'employee@demo.test')->first(),
            'employees' => $employees,
            'project' => Project::query()->where('organization_id', $organization->id)->first(),
            'invoice' => Invoice::query()->where('organization_id', $organization->id)->first(),
            'expense_claim' => ExpenseClaim::query()->where('organization_id', $organization->id)->first(),
            'leave_request' => LeaveRequest::query()->where('organization_id', $organization->id)->first(),
            'payroll_run' => PayrollRun::query()->where('organization_id', $organization->id)->first(),
            'department' => Department::query()->where('organization_id', $organization->id)->first(),
        ];
    }
}
