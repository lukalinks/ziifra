<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org' => \App\Http\Middleware\EnsureOrganizationSelected::class,
            'org.active' => \App\Http\Middleware\EnsureOrganizationIsActive::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'payroll' => \App\Http\Middleware\EnsurePayrollEnabled::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanFeature::class,
            'employee.code_url' => \App\Http\Middleware\RedirectEmployeeNumericUrlToCode::class,
            'employee.default_tab' => \App\Http\Middleware\RedirectEmployeeDefaultTabQuery::class,
            'project.code_url' => \App\Http\Middleware\RedirectProjectNumericUrlToCode::class,
            'payroll.period_url' => \App\Http\Middleware\RedirectPayrollNumericUrlToPeriod::class,
            'time.employee_url' => \App\Http\Middleware\RedirectTimeNumericEmployeeIdToCode::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetCurrentOrganization::class,
            \App\Http\Middleware\SetApplicationLocale::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'paypal/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('billing:send-reminders')->dailyAt('08:00')->onOneServer();
        $schedule->command('documents:send-expiry-reminders')->dailyAt('08:30')->onOneServer();
    })
    ->create();
