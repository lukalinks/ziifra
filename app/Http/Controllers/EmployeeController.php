<?php

namespace App\Http\Controllers;

use App\Enums\AllowanceTaxTreatment;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeFieldDefinition;
use App\Models\EmployeeFieldValue;
use App\Models\Organization;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeHourlyRate;
use App\Models\Position;
use App\Models\Project;
use App\Services\BillingNotificationService;
use App\Services\EmployeeCustomFieldService;
use App\Services\EmployeeLoginActivationService;
use App\Services\EmployeeRateService;
use App\Services\OrganizationBillingService;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request, EmployeeLoginActivationService $loginActivations): View
    {
        $this->authorize('viewAny', Employee::class);

        $organization = CurrentOrganization::check();
        $role = $request->user()->roleIn($organization);
        $canManage = $role?->canManageEmployees() ?? false;
        $canActivateLogin = $canManage && $request->user()->can('inviteMembers', $organization);

        $query = Employee::query()
            ->with(['department', 'position', 'manager', 'user'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($projectId = $request->integer('project_id')) {
            $query->whereHas('projects', fn ($q) => $q->where('projects.id', $projectId));
        }

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, array_column(EmploymentStatus::cases(), 'value'), true)) {
                $query->where('employment_status', $status);
            }
        }

        if ($type = $request->string('type')->toString()) {
            if (in_array($type, array_column(EmploymentType::cases(), 'value'), true)) {
                $query->where('employment_type', $type);
            }
        }

        if ($departmentId = $request->integer('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($request->boolean('missing_login')) {
            $query
                ->whereNull('user_id')
                ->whereNotNull('email')
                ->where('email', '!=', '');
        }

        $employees = $query->paginate(20)->withQueryString();
        $projects = Project::query()->orderBy('name')->get(['id', 'name']);
        $selectedProject = $request->integer('project_id')
            ? $projects->firstWhere('id', $request->integer('project_id'))
            : null;

        return view('app.employees.index', [
            'organization' => $organization,
            'employees' => $employees,
            'pendingLoginInvites' => $loginActivations->pendingInvitationsByEmail($employees->getCollection()),
            'departments' => Department::query()->orderBy('name')->get(),
            'projects' => $projects,
            'selectedProject' => $selectedProject,
            'statuses' => EmploymentStatus::cases(),
            'types' => EmploymentType::cases(),
            'canManage' => $canManage,
            'canActivateLogin' => $canActivateLogin,
            'filterMissingLogin' => $request->boolean('missing_login'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Employee::class);

        return view('app.employees.create', $this->formData($request));
    }

    public function store(
        StoreEmployeeRequest $request,
        EmployeeCustomFieldService $customFields,
        BillingNotificationService $billingNotifications,
    ): RedirectResponse {
        $data = $request->validated();
        $allowanceTemplates = null;

        if (array_key_exists('allowance_templates', $data)) {
            $allowanceTemplates = $data['allowance_templates'];
            unset($data['allowance_templates']);
        }

        [$custom, $newCustom] = $this->extractCustomFieldInput($data);
        $projectIds = $data['project_ids'] ?? null;
        unset($data['project_ids']);

        if ($data['employment_status'] === EmploymentStatus::Terminated->value) {
            $data['terminated_at'] = now();
        }

        $employee = Employee::query()->create($data);
        $customFields->syncForEmployee($employee, $custom, $newCustom, $request);

        if (is_array($allowanceTemplates)) {
            $this->syncEmployeeAllowances($employee, $allowanceTemplates);
        }

        if (is_array($projectIds)) {
            $employee->projects()->sync($projectIds);
        }

        $organization = CurrentOrganization::check();
        $billingNotifications->notifyEmployeeLimitApproaching($organization->fresh());

        return redirect()
            ->route('employees.index')
            ->with('status', 'Employee created successfully.');
    }

    public function show(
        Organization $organization,
        Employee $employee,
        EmployeeLoginActivationService $loginActivations,
    ): View {
        $this->authorize('view', $employee);

        $employee->load(['department', 'position', 'manager', 'user', 'directReports', 'fieldValues.definition', 'documents.uploadedBy', 'organization', 'hourlyRates', 'projects']);
        $org = CurrentOrganization::check();
        $user = auth()->user();
        $canManage = $user->roleIn($org)?->canManageEmployees() ?? false;

        return view('app.employees.show', [
            'organization' => $org,
            'employee' => $employee,
            'canManage' => $canManage,
            'canActivateLogin' => $canManage && $user->can('inviteMembers', $org),
            'loginStatus' => $loginActivations->statusFor($employee),
            'pendingLoginInvitation' => $loginActivations->pendingInvitation($employee),
        ]);
    }

    public function activateLogin(
        Organization $organization,
        Employee $employee,
        EmployeeLoginActivationService $loginActivations,
    ): RedirectResponse {
        $this->authorize('activateLogin', $employee);

        $invitation = $loginActivations->activate($employee, auth()->user());

        $message = $invitation === null
            ? __('employees.login_linked', ['email' => $employee->email])
            : __('employees.login_activated', ['email' => $employee->email]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', $message);
    }

    public function resendLoginInvitation(
        Organization $organization,
        Employee $employee,
        EmployeeLoginActivationService $loginActivations,
    ): RedirectResponse {
        $this->authorize('activateLogin', $employee);

        $invitation = $loginActivations->resend($employee, auth()->user());

        $message = $invitation === null
            ? __('employees.login_linked', ['email' => $employee->email])
            : __('employees.login_invitation_resent', ['email' => $employee->email]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', $message);
    }

    public function edit(Request $request, Organization $organization, Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load('fieldValues.definition');

        return view('app.employees.edit', array_merge(
            $this->formData($request, $employee),
            ['employee' => $employee->load(['user', 'projects'])],
        ));
    }

    public function update(UpdateEmployeeRequest $request, Organization $organization, Employee $employee, EmployeeCustomFieldService $customFields): RedirectResponse
    {
        $data = $request->validated();
        $allowanceTemplates = null;

        if (array_key_exists('allowance_templates', $data)) {
            $allowanceTemplates = $data['allowance_templates'];
            unset($data['allowance_templates']);
        }

        [$custom, $newCustom] = $this->extractCustomFieldInput($data);
        $projectIds = $data['project_ids'] ?? null;
        unset($data['project_ids']);

        if ($data['employment_status'] === EmploymentStatus::Terminated->value) {
            $data['terminated_at'] = $employee->terminated_at ?? now();
        } else {
            $data['terminated_at'] = null;
        }

        $employee->update($data);
        $customFields->syncForEmployee($employee, $custom, $newCustom, $request);

        if (is_array($allowanceTemplates)) {
            $this->syncEmployeeAllowances($employee, $allowanceTemplates);
        }

        if (is_array($projectIds)) {
            $employee->projects()->sync($projectIds);
        }

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'Employee updated successfully.');
    }

    public function downloadCustomFieldFile(
        Organization $organization,
        Employee $employee,
        EmployeeFieldDefinition $fieldDefinition,
    ): StreamedResponse
    {
        $this->authorize('view', $employee);

        $fieldValue = EmployeeFieldValue::query()
            ->where('employee_id', $employee->id)
            ->where('employee_field_definition_id', $fieldDefinition->id)
            ->firstOrFail();

        abort_unless($fieldValue->hasStoredFile(), 404);

        $meta = $fieldValue->fileMetadata();

        return Storage::disk('local')->download($meta['path'], $meta['name']);
    }

    public function destroy(Organization $organization, Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->update([
            'employment_status' => EmploymentStatus::Terminated,
            'terminated_at' => now(),
        ]);
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', 'Employee removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Request $request, ?Employee $employee = null): array
    {
        $organization = CurrentOrganization::check();

        $linkedUserIds = Employee::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('user_id')
            ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
            ->pluck('user_id');

        return [
            'organization' => $organization,
            'linkableUsers' => $organization->users()
                ->whereNotIn('users.id', $linkedUserIds)
                ->orderBy('name')
                ->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'positions' => Position::query()->orderBy('title')->get(),
            'managers' => Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'statuses' => EmploymentStatus::cases(),
            'types' => EmploymentType::cases(),
            'defaultEmploymentType' => $organization->default_employment_type ?? EmploymentType::FullTime->value,
            'fieldDefinitions' => EmployeeFieldDefinition::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'fieldTypes' => \App\Enums\CustomFieldType::cases(),
            'showPayrollFields' => app(OrganizationBillingService::class)->hasPayroll($organization),
            'allowanceTemplateRows' => $employee
                ? $employee->employeeAllowances()->orderBy('sort_order')->get()
                : collect(),
            'projects' => Project::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $templates
     */
    protected function syncEmployeeAllowances(Employee $employee, array $templates): void
    {
        $employee->employeeAllowances()->delete();

        foreach (array_values($templates) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $amount = round(max(0, (float) ($row['amount'] ?? 0)), 2);

            if ($label === '' && $amount <= 0) {
                continue;
            }

            if ($label === '') {
                $label = __('employees.allowance_default_label');
            }

            $taxTreatment = AllowanceTaxTreatment::tryFrom((string) ($row['tax_treatment'] ?? ''))
                ?? AllowanceTaxTreatment::Taxable;

            EmployeeAllowance::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'label' => $label,
                'amount' => $amount,
                'tax_treatment' => $taxTreatment,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function extractCustomFieldInput(array &$data): array
    {
        $custom = $data['custom_fields'] ?? [];
        $newCustom = $data['new_custom_fields'] ?? [];
        unset($data['custom_fields'], $data['new_custom_fields']);

        return [$custom, $newCustom];
    }

    public function storeHourlyRate(
        Request $request,
        Organization $organization,
        Employee $employee,
        EmployeeRateService $rates,
    ): RedirectResponse {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $rates->upsert($employee, $validated);

        return back()->with('status', __('employees.rate_saved'));
    }

    public function destroyHourlyRate(
        Organization $organization,
        Employee $employee,
        EmployeeHourlyRate $rate,
    ): RedirectResponse {
        $this->authorize('update', $employee);

        abort_unless($rate->employee_id === $employee->id, 404);

        $rate->delete();

        return back()->with('status', __('employees.rate_deleted'));
    }
}
