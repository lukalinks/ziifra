<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OAuthRegisterController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Enums\OAuthProvider;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationSelectController;
use App\Http\Controllers\OrganizationContractTemplateController;
use App\Http\Controllers\OrganizationBrandController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DailyHoursController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\ExpenseClaimController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\EmployeeFieldDefinitionController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\ChatSettingsController;
use App\Http\Controllers\MailSettingsController;
use App\Models\Organization;
use App\Http\Controllers\InvoiceSettingsController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollSettingsController;
use App\Http\Controllers\EmployeeDailyHoursController;
use App\Http\Controllers\PayrollTimeController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\BillingSettingsController as AdminBillingSettingsController;
use App\Http\Controllers\Admin\LanguageSettingsController as AdminLanguageSettingsController;
use App\Http\Controllers\WorkspaceNavItemController;
use App\Http\Controllers\WorkspaceRedirectController;
use App\Support\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/o/{organization}/brand/logo', [OrganizationBrandController::class, 'logo'])
    ->name('org.brand.logo');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('auth.oauth.redirect')
        ->whereIn('provider', array_column(OAuthProvider::cases(), 'value'));
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('auth.oauth.callback')
        ->whereIn('provider', array_column(OAuthProvider::cases(), 'value'));

    Route::get('/register/oauth/complete', [OAuthRegisterController::class, 'create'])
        ->name('register.oauth.complete');
    Route::post('/register/oauth/complete', [OAuthRegisterController::class, 'store'])
        ->name('register.oauth.complete.store');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::post('/paypal/webhook', PayPalWebhookController::class)->name('paypal.webhook');

Route::get('/invite/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.accept');
Route::post('/invite/{token}', [InvitationAcceptController::class, 'store'])->name('invitations.accept.store');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/organizations', [AdminOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/{organization:id}', [AdminOrganizationController::class, 'show'])->name('organizations.show');
    Route::put('/organizations/{organization:id}/plan', [AdminOrganizationController::class, 'updatePlan'])->name('organizations.plan');
    Route::put('/organizations/{organization:id}/trial', [AdminOrganizationController::class, 'updateTrial'])->name('organizations.trial');
    Route::post('/organizations/{organization:id}/suspend', [AdminOrganizationController::class, 'suspend'])->name('organizations.suspend');
    Route::post('/organizations/{organization:id}/unsuspend', [AdminOrganizationController::class, 'unsuspend'])->name('organizations.unsuspend');
    Route::post('/organizations/{organization:id}/impersonate', [AdminImpersonationController::class, 'store'])->name('organizations.impersonate');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}/super-admin', [AdminUserController::class, 'updateSuperAdmin'])->name('users.super-admin');
    Route::post('/users/{user}/impersonate', [AdminImpersonationController::class, 'storeUser'])->name('users.impersonate');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/languages', [AdminLanguageSettingsController::class, 'edit'])->name('languages.edit');
    Route::put('/languages', [AdminLanguageSettingsController::class, 'update'])->name('languages.update');
    Route::get('/billing', [AdminBillingSettingsController::class, 'edit'])->name('billing.edit');
    Route::put('/billing', [AdminBillingSettingsController::class, 'update'])->name('billing.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::post('/impersonation/stop', [AdminImpersonationController::class, 'destroy'])->name('impersonation.stop');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/organizations/select', [OrganizationSelectController::class, 'index'])
        ->name('organizations.select');
    Route::post('/organizations/select', [OrganizationSelectController::class, 'store'])
        ->name('organizations.select.store');

    Route::get('/dashboard', [WorkspaceRedirectController::class, 'dashboard'])
        ->name('workspace.dashboard');
    Route::get('/team', [WorkspaceRedirectController::class, 'team']);
    Route::get('/settings/{path?}', [WorkspaceRedirectController::class, 'settings'])
        ->where('path', '.*');
    Route::get('/employees/{path?}', [WorkspaceRedirectController::class, 'employees'])
        ->where('path', '.*');

    Route::prefix(Workspace::ROUTE_PREFIX.'/{organization}')
        ->middleware(['org', 'org.active', 'employee.code_url', 'employee.default_tab', 'project.code_url', 'payroll.period_url', 'time.employee_url'])
        ->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::get('/team', [TeamInvitationController::class, 'index'])->name('team.index');
            Route::post('/team/invitations', [TeamInvitationController::class, 'store'])->name('team.invitations.store');
            Route::delete('/team/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])
                ->name('team.invitations.destroy');

            Route::get('/employees/{employee}/custom-fields/{fieldDefinition}/download', [EmployeeController::class, 'downloadCustomFieldFile'])
                ->name('employees.custom-fields.download');
            Route::get('/employees/export', [EmployeeImportController::class, 'export'])
                ->name('employees.export');
            Route::get('/employees/export/pdf', [EmployeeController::class, 'exportPdf'])
                ->name('employees.export.pdf');
            Route::get('/employees/import/template', [EmployeeImportController::class, 'template'])
                ->name('employees.import.template');
            Route::get('/employees/import', [EmployeeImportController::class, 'create'])
                ->name('employees.import');
            Route::post('/employees/import', [EmployeeImportController::class, 'store'])
                ->name('employees.import.store');

            Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])
                ->name('employees.documents.store');
            Route::get('/employees/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download'])
                ->name('employees.documents.download');
            Route::delete('/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])
                ->name('employees.documents.destroy');
            Route::post('/employees/{employee}/activate-login', [EmployeeController::class, 'activateLogin'])
                ->name('employees.activate-login');
            Route::post('/employees/{employee}/resend-login-invitation', [EmployeeController::class, 'resendLoginInvitation'])
                ->name('employees.resend-login-invitation');
            Route::resource('employees', EmployeeController::class);

            Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index')->middleware('plan.feature:documents');
            Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('plan.feature:documents');
            Route::get('/documents/files/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->middleware('plan.feature:documents');
            Route::delete('/documents/files/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware('plan.feature:documents');
            Route::post('/documents/folders', [DocumentFolderController::class, 'store'])->name('documents.folders.store')->middleware('plan.feature:documents');
            Route::delete('/documents/folders/{folder}', [DocumentFolderController::class, 'destroy'])->name('documents.folders.destroy')->middleware('plan.feature:documents');
            Route::get('/documents/templates/{template}/download', [ContractTemplateController::class, 'download'])
                ->name('documents.templates.download')
                ->middleware('plan.feature:documents');
            Route::post('/documents/templates/{template}/generate', [ContractTemplateController::class, 'generate'])
                ->name('documents.templates.generate')
                ->middleware('plan.feature:documents');

            Route::middleware('plan.feature:invoices')->group(function () {
                Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
                Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
                Route::post('/invoices/from-hours', [InvoiceController::class, 'storeFromHours'])->name('invoices.from-hours');
                Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
                Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
                Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
                Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
                Route::get('/invoices/{invoice}/export', [InvoiceController::class, 'export'])->name('invoices.export');
                Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
                Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
                Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'markSent'])->name('invoices.send');
                Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
                Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
            });

            Route::middleware('plan.feature:expenses')->group(function () {
                Route::get('/expenses', [ExpenseClaimController::class, 'index'])->name('expenses.index');
                Route::get('/expenses/create', [ExpenseClaimController::class, 'create'])->name('expenses.create');
                Route::post('/expenses/scan-receipt', [ExpenseClaimController::class, 'scanReceipt'])->name('expenses.scan-receipt');
                Route::post('/expenses', [ExpenseClaimController::class, 'store'])->name('expenses.store');
                Route::get('/expenses/{expenseClaim}', [ExpenseClaimController::class, 'show'])->name('expenses.show');
                Route::get('/expenses/{expenseClaim}/receipt', [ExpenseClaimController::class, 'downloadReceipt'])->name('expenses.receipt');
                Route::post('/expenses/{expenseClaim}/approve', [ExpenseClaimController::class, 'approve'])->name('expenses.approve');
                Route::post('/expenses/{expenseClaim}/reject', [ExpenseClaimController::class, 'reject'])->name('expenses.reject');
                Route::post('/expenses/{expenseClaim}/cancel', [ExpenseClaimController::class, 'cancel'])->name('expenses.cancel');
            });

            Route::middleware('plan.feature:payroll')->prefix('payroll-time')->name('payroll-time.')->group(function () {
                Route::get('/', [PayrollTimeController::class, 'index'])->name('index');
                Route::post('/hours', [PayrollTimeController::class, 'upsert'])->name('hours.upsert');
                Route::post('/hours/approve-all', [PayrollTimeController::class, 'approveAll'])->name('hours.approve-all');
                Route::post('/hours/{employee}/approve', [PayrollTimeController::class, 'approveEmployee'])->name('hours.approve');
                Route::post('/employees/{employee}/rate', [PayrollTimeController::class, 'updateRate'])->name('rate.update');
                Route::get('/export/pdf', [PayrollTimeController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/export/excel', [PayrollTimeController::class, 'exportExcel'])->name('export.excel');
                Route::post('/archive/past', [PayrollTimeController::class, 'archivePastMonths'])->name('archive.past');
                Route::get('/employees/{employee}/export/pdf', [PayrollTimeController::class, 'exportEmployeePdf'])->name('employee.export.pdf');
                Route::get('/employees/{employee}/export/excel', [PayrollTimeController::class, 'exportEmployeeExcel'])->name('employee.export.excel');
            });

            Route::get('/payroll', fn (Organization $organization) => redirect()->route('payroll-time.index', $organization))
                ->middleware('plan.feature:payroll');
            Route::get('/time', fn (Organization $organization) => redirect()->route('payroll-time.index', $organization))
                ->middleware('plan.feature:time_tracking');

            Route::middleware('plan.feature:payroll')->prefix('payroll')->name('payroll.')->group(function () {
                Route::get('/', [PayrollRunController::class, 'index'])->name('index');
                Route::get('/create', [PayrollRunController::class, 'create'])->name('create');
                Route::post('/', [PayrollRunController::class, 'store'])->name('store');
                Route::get('/{payrollRun}/export-csv', [PayrollRunController::class, 'exportCsv'])->name('export-csv');
                Route::get('/{payrollRun}/export-pdfs', [PayrollRunController::class, 'exportPdfs'])->name('export-pdfs');
                Route::post('/{payrollRun}/email-payslips', [PayrollRunController::class, 'emailPayslips'])->name('email-payslips');
                Route::get('/{payrollRun}/items/{item}/pdf', [PayslipController::class, 'pdf'])->name('payslip.pdf');
                Route::get('/{payrollRun}', [PayrollRunController::class, 'show'])->name('show');
                Route::put('/{payrollRun}', [PayrollRunController::class, 'update'])->name('update');
                Route::post('/{payrollRun}/lock', [PayrollRunController::class, 'lock'])->name('lock');
                Route::post('/{payrollRun}/items/{item}/email', [PayslipController::class, 'sendEmail'])->name('payslip.email');
                Route::get('/{payrollRun}/items/{item}', [PayslipController::class, 'show'])->name('payslip');
            });

            Route::post('/nav-items', [WorkspaceNavItemController::class, 'store'])->name('nav-items.store');
            Route::delete('/nav-items/{navItem}', [WorkspaceNavItemController::class, 'destroy'])->name('nav-items.destroy');

            Route::post('/employees/{employee}/hourly-rates', [EmployeeController::class, 'storeHourlyRate'])->name('employees.hourly-rates.store');
            Route::delete('/employees/{employee}/hourly-rates/{rate}', [EmployeeController::class, 'destroyHourlyRate'])->name('employees.hourly-rates.destroy');

            Route::middleware('plan.feature:documents')->group(function () {
                Route::get('/project-documents', [ProjectDocumentController::class, 'index'])->name('project-documents.index');
                Route::post('/project-documents', [ProjectDocumentController::class, 'store'])->name('project-documents.store');
                Route::get('/project-documents/export', [ProjectDocumentController::class, 'exportSummary'])->name('project-documents.export');
                Route::get('/project-documents/{projectDocument}/download', [ProjectDocumentController::class, 'download'])->name('project-documents.download');
                Route::delete('/project-documents/{projectDocument}', [ProjectDocumentController::class, 'destroy'])->name('project-documents.destroy');
            });

            Route::middleware('plan.feature:projects')->group(function () {
                Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
                Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
                Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
                Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
                Route::post('/projects/{project}/hours', [DailyHoursController::class, 'upsert'])->name('projects.hours.upsert');
                Route::post('/projects/{project}/hours/approve-all', [DailyHoursController::class, 'approveAll'])->name('projects.hours.approve-all');
                Route::post('/projects/{project}/hours/{employee}/approve', [DailyHoursController::class, 'approveRow'])->name('projects.hours.approve');
                Route::get('/projects/{project}/hours/export', [DailyHoursController::class, 'export'])->name('projects.hours.export');
                Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
                Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
                Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
                Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
                Route::put('/projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask'])->name('projects.tasks.update');
                Route::delete('/projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');
                Route::post('/projects/{project}/documents', [ProjectDocumentController::class, 'store'])->name('projects.documents.store');
                Route::get('/projects/{project}/documents/{projectDocument}/download', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');
                Route::delete('/projects/{project}/documents/{projectDocument}', [ProjectDocumentController::class, 'destroy'])->name('projects.documents.destroy');
                Route::get('/projects/{project}/hours/chart', [ProjectController::class, 'hoursChart'])->name('projects.hours.chart');
                Route::post('/projects/{project}/members', [ProjectController::class, 'storeMember'])->name('projects.members.store');
                Route::delete('/projects/{project}/members/{employee}', [ProjectController::class, 'destroyMember'])->name('projects.members.destroy');
            });

            Route::middleware('plan.feature:projects')->prefix('my-hours')->name('my-hours.')->group(function () {
                Route::get('/', [EmployeeDailyHoursController::class, 'index'])->name('index');
                Route::post('/hours', [EmployeeDailyHoursController::class, 'upsert'])->name('upsert');
            });

            Route::middleware('plan.feature:time_tracking')->group(function () {
                Route::get('/time/export', [TimeEntryController::class, 'export'])->name('time.export');
                Route::get('/time', [TimeEntryController::class, 'index'])->name('time.index');
                Route::get('/time/create', [TimeEntryController::class, 'create'])->name('time.create');
                Route::post('/time/entries', [TimeEntryController::class, 'store'])->name('time.entries.store');
                Route::get('/time/entries/{timeEntry}/edit', [TimeEntryController::class, 'edit'])->name('time.entries.edit');
                Route::put('/time/entries/{timeEntry}', [TimeEntryController::class, 'update'])->name('time.entries.update');
                Route::delete('/time/entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('time.entries.destroy');
                Route::post('/time/clock-in', [TimeEntryController::class, 'clockIn'])->name('time.clock-in');
                Route::post('/time/clock-out', [TimeEntryController::class, 'clockOut'])->name('time.clock-out');
            });

            Route::middleware('plan.feature:reports')->group(function () {
                Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
                Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            });

            Route::middleware('plan.feature:chat')->group(function () {
                Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
                Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
                Route::delete('/chat/{chatMessage}', [ChatController::class, 'destroy'])->name('chat.destroy');
            });

            Route::middleware('plan.feature:leave')->group(function () {
                Route::get('/leave/calendar', LeaveCalendarController::class)->name('leave.calendar');
                Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
                Route::get('/leave/create', [LeaveRequestController::class, 'create'])->name('leave.create');
                Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
                Route::get('/leave/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave.show');
                Route::post('/leave/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave.approve');
                Route::post('/leave/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave.reject');
                Route::post('/leave/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave.cancel');
            });

            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::get('/settings/billing', [BillingController::class, 'show'])->name('settings.billing');
            Route::post('/settings/billing/checkout', [BillingController::class, 'checkout'])->name('settings.billing.checkout');
            Route::get('/settings/billing/success', [BillingController::class, 'checkoutSuccess'])->name('settings.billing.checkout.success');
            Route::get('/settings/billing/paypal/success', [BillingController::class, 'paypalSuccess'])->name('settings.billing.paypal.success');
            Route::post('/settings/billing/portal', [BillingController::class, 'portal'])->name('settings.billing.portal');

            Route::get('/settings/company', [OrganizationSettingsController::class, 'edit'])->name('settings.company.edit');
            Route::put('/settings/company', [OrganizationSettingsController::class, 'update'])->name('settings.company.update');
            Route::get('/settings/company/logo', [OrganizationSettingsController::class, 'logo'])->name('settings.company.logo');

            Route::get('/settings/payroll', [PayrollSettingsController::class, 'edit'])->name('settings.payroll.edit');
            Route::put('/settings/payroll', [PayrollSettingsController::class, 'update'])->name('settings.payroll.update');

            Route::get('/settings/invoices', [InvoiceSettingsController::class, 'edit'])->name('settings.invoices.edit');
            Route::put('/settings/invoices', [InvoiceSettingsController::class, 'update'])->name('settings.invoices.update');

            Route::get('/settings/chat', [ChatSettingsController::class, 'edit'])->name('settings.chat.edit');
            Route::put('/settings/chat', [ChatSettingsController::class, 'update'])->name('settings.chat.update');

            Route::get('/settings/mail', [MailSettingsController::class, 'edit'])->name('settings.mail.edit');
            Route::put('/settings/mail', [MailSettingsController::class, 'update'])->name('settings.mail.update');
            Route::post('/settings/mail/test', [MailSettingsController::class, 'sendTest'])->name('settings.mail.test');

            Route::get('/settings/departments', [DepartmentController::class, 'index'])->name('settings.departments.index');
            Route::post('/settings/departments', [DepartmentController::class, 'store'])->name('settings.departments.store');
            Route::delete('/settings/departments/{department}', [DepartmentController::class, 'destroy'])
                ->name('settings.departments.destroy');

            Route::get('/settings/positions', [PositionController::class, 'index'])->name('settings.positions.index');
            Route::post('/settings/positions', [PositionController::class, 'store'])->name('settings.positions.store');
            Route::delete('/settings/positions/{position}', [PositionController::class, 'destroy'])
                ->name('settings.positions.destroy');

            Route::get('/settings/employee-fields', [EmployeeFieldDefinitionController::class, 'index'])
                ->name('settings.employee-fields.index');
            Route::post('/settings/employee-fields', [EmployeeFieldDefinitionController::class, 'store'])
                ->name('settings.employee-fields.store');
            Route::delete('/settings/employee-fields/{fieldDefinition}', [EmployeeFieldDefinitionController::class, 'destroy'])
                ->name('settings.employee-fields.destroy');

            Route::get('/settings/leave-types', [LeaveTypeController::class, 'index'])->name('settings.leave-types.index');
            Route::post('/settings/leave-types', [LeaveTypeController::class, 'store'])->name('settings.leave-types.store');
            Route::delete('/settings/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])
                ->name('settings.leave-types.destroy');

            Route::middleware('plan.feature:documents')->group(function () {
                Route::get('/settings/contract-templates', [OrganizationContractTemplateController::class, 'index'])
                    ->name('settings.contract-templates.index');
                Route::post('/settings/contract-templates', [OrganizationContractTemplateController::class, 'store'])
                    ->name('settings.contract-templates.store');
                Route::get('/settings/contract-templates/{template}/edit', [OrganizationContractTemplateController::class, 'edit'])
                    ->name('settings.contract-templates.edit');
                Route::put('/settings/contract-templates/{template}', [OrganizationContractTemplateController::class, 'update'])
                    ->name('settings.contract-templates.update');
                Route::delete('/settings/contract-templates/{template}', [OrganizationContractTemplateController::class, 'destroy'])
                    ->name('settings.contract-templates.destroy');
            });
        });
});
