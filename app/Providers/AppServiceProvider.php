<?php

namespace App\Providers;

use App\Models\OrganizationContractTemplate;
use App\Enums\OAuthProvider;
use App\Models\Department;
use App\Support\SocialAuth;
use App\Models\Employee;
use App\Models\DocumentFolder;
use App\Models\EmployeeDocument;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use App\Models\WorkspaceNavItem;
use App\Models\TimeEntry;
use App\Models\ChatMessage;
use App\Models\EmployeeHourlyRate;
use App\Models\EmployeeFieldDefinition;
use App\Models\Invitation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Policies\DocumentFolderPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\PayrollItemPolicy;
use App\Policies\PayrollRunPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\LeaveTypePolicy;
use App\Policies\EmployeeDocumentPolicy;
use App\Policies\ExpenseClaimPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\EmployeeFieldDefinitionPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationContractTemplatePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PositionPolicy;
use App\Policies\ProjectDocumentPolicy;
use App\Policies\WorkspaceNavItemPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use App\Services\NotificationFeedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Support\HttpSslOptions;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            $user = $request->user();

            if ($user?->isSuperAdmin() && ! session('impersonator_id')) {
                return route('admin.dashboard');
            }

            return route('workspace.dashboard');
        });

        Http::globalOptions(app(HttpSslOptions::class)->toArray());

        foreach (OAuthProvider::cases() as $provider) {
            $key = "services.{$provider->value}.redirect";
            if (empty(config($key))) {
                config([$key => SocialAuth::redirectUri($provider)]);
            }
        }

        Gate::policy(OrganizationContractTemplate::class, OrganizationContractTemplatePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(EmployeeDocument::class, EmployeeDocumentPolicy::class);
        Gate::policy(DocumentFolder::class, DocumentFolderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(ExpenseClaim::class, ExpenseClaimPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectDocument::class, ProjectDocumentPolicy::class);
        Gate::policy(WorkspaceNavItem::class, WorkspaceNavItemPolicy::class);
        Gate::policy(TimeEntry::class, TimeEntryPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);
        Gate::policy(EmployeeFieldDefinition::class, EmployeeFieldDefinitionPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(PayrollRun::class, PayrollRunPolicy::class);
        Gate::policy(PayrollItem::class, PayrollItemPolicy::class);

        Route::bind('employee', fn (string $value) => $this->resolveEmployee($value));
        Route::bind('payrollRun', fn (string $value) => $this->resolvePayrollRun($value));
        Route::bind('item', fn (string $value) => $this->resolveTenantModel(PayrollItem::class, $value));
        Route::bind('leaveRequest', fn (string $value) => $this->resolveTenantModel(LeaveRequest::class, $value));
        Route::bind('leaveType', fn (string $value) => $this->resolveTenantModel(LeaveType::class, $value));
        Route::bind('department', fn (string $value) => $this->resolveTenantModel(Department::class, $value));
        Route::bind('position', fn (string $value) => $this->resolveTenantModel(Position::class, $value));
        Route::bind('invitation', fn (string $value) => $this->resolveTenantModel(Invitation::class, $value));
        Route::bind('fieldDefinition', fn (string $value) => $this->resolveTenantModel(EmployeeFieldDefinition::class, $value));
        Route::bind('document', fn (string $value) => $this->resolveTenantModel(EmployeeDocument::class, $value));
        Route::bind('folder', fn (string $value) => $this->resolveTenantModel(DocumentFolder::class, $value));
        Route::bind('invoice', fn (string $value) => $this->resolveTenantModel(Invoice::class, $value));
        Route::bind('expenseClaim', fn (string $value) => $this->resolveTenantModel(ExpenseClaim::class, $value));
        Route::bind('project', fn (string $value) => $this->resolveProject($value));
        Route::bind('projectDocument', fn (string $value) => $this->resolveTenantModel(ProjectDocument::class, $value));
        Route::bind('navItem', fn (string $value) => $this->resolveTenantModel(WorkspaceNavItem::class, $value));
        Route::bind('rate', fn (string $value) => $this->resolveTenantModel(EmployeeHourlyRate::class, $value));
        Route::bind('task', fn (string $value) => $this->resolveTenantModel(ProjectTask::class, $value));
        Route::bind('timeEntry', fn (string $value) => $this->resolveTenantModel(TimeEntry::class, $value));
        Route::bind('chatMessage', fn (string $value) => $this->resolveTenantModel(ChatMessage::class, $value));
        Route::bind('template', fn (string $value) => $this->resolveTenantModelBySlug(OrganizationContractTemplate::class, $value));

        View::composer(['layouts.app', 'admin.layout'], function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $view->with('notificationFeed', app(NotificationFeedService::class)->forAuthenticatedUser(
                \App\Support\CurrentOrganization::get(),
                request()->routeIs('admin.*'),
            ));
        });
    }

    protected function resolveTenantModelBySlug(string $modelClass, string $slug): Model
    {
        $organizationId = \App\Support\CurrentOrganization::id()
            ?? request()->session()->get('current_organization_id');

        if ($organizationId === null) {
            abort(404);
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    protected function resolveEmployee(string $value): Employee
    {
        $organizationId = \App\Support\CurrentOrganization::id()
            ?? request()->session()->get('current_organization_id');

        if ($organizationId === null) {
            abort(404);
        }

        $employee = Employee::query()
            ->where('organization_id', $organizationId)
            ->where('employee_code', $value)
            ->first();

        if ($employee === null && ctype_digit($value)) {
            $employee = Employee::query()
                ->where('organization_id', $organizationId)
                ->whereKey((int) $value)
                ->first();
        }

        if ($employee === null) {
            abort(404);
        }

        return $employee;
    }

    protected function resolvePayrollRun(string $value): PayrollRun
    {
        $organizationId = \App\Support\CurrentOrganization::id()
            ?? request()->session()->get('current_organization_id');

        if ($organizationId === null) {
            abort(404);
        }

        $run = null;

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches) === 1) {
            $run = PayrollRun::query()
                ->where('organization_id', $organizationId)
                ->where('year', (int) $matches[1])
                ->where('month', (int) $matches[2])
                ->first();
        } elseif (ctype_digit($value)) {
            $run = PayrollRun::query()
                ->where('organization_id', $organizationId)
                ->whereKey((int) $value)
                ->first();
        }

        if ($run === null) {
            abort(404);
        }

        return $run;
    }

    protected function resolveProject(string $value): Project
    {
        $organizationId = \App\Support\CurrentOrganization::id()
            ?? request()->session()->get('current_organization_id');

        if ($organizationId === null) {
            abort(404);
        }

        $project = Project::query()
            ->where('organization_id', $organizationId)
            ->where('project_code', $value)
            ->first();

        if ($project === null && ctype_digit($value)) {
            $project = Project::query()
                ->where('organization_id', $organizationId)
                ->whereKey((int) $value)
                ->first();
        }

        if ($project === null) {
            abort(404);
        }

        return $project;
    }

    protected function resolveTenantModel(string $modelClass, string $key): Model
    {
        $organizationId = \App\Support\CurrentOrganization::id()
            ?? request()->session()->get('current_organization_id');

        if ($organizationId === null) {
            abort(404);
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass::query()
            ->where('organization_id', $organizationId)
            ->whereKey($key)
            ->firstOrFail();
    }
}
