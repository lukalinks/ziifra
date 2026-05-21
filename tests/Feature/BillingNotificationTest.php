<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Mail\EmployeeLimitApproachingMail;
use App\Mail\TrialEndingSoonMail;
use App\Mail\TrialExpiredMail;
use App\Models\Employee;
use App\Services\BillingNotificationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_reminder_command_sends_email_at_threshold(): void
    {
        Mail::fake();

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['trial_ends_at' => now()->startOfDay()->addDays(3)]);

        Artisan::call('billing:send-reminders');

        Mail::assertQueued(TrialEndingSoonMail::class, function (TrialEndingSoonMail $mail) {
            return $mail->daysRemaining === 3;
        });
    }

    public function test_trial_reminder_is_not_sent_twice(): void
    {
        Mail::fake();

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['trial_ends_at' => now()->startOfDay()->addDays(3)]);

        Artisan::call('billing:send-reminders');
        Artisan::call('billing:send-reminders');

        Mail::assertQueued(TrialEndingSoonMail::class, 1);
    }

    public function test_trial_expired_day_sends_expired_mail(): void
    {
        Mail::fake();

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $result['organization']->update(['trial_ends_at' => now()->startOfDay()]);

        Artisan::call('billing:send-reminders');

        Mail::assertQueued(TrialExpiredMail::class);
        Mail::assertNotSent(TrialEndingSoonMail::class);
    }

    public function test_employee_limit_approaching_notification(): void
    {
        Mail::fake();

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $result['organization'];
        $organization->update(['plan' => SubscriptionPlan::Starter->value]);

        Employee::factory()->count(49)->create([
            'organization_id' => $organization->id,
        ]);

        app(BillingNotificationService::class)->notifyEmployeeLimitApproaching($organization->fresh());

        Mail::assertQueued(EmployeeLimitApproachingMail::class);
    }
}
