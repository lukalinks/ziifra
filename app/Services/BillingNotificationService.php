<?php

namespace App\Services;

use App\Mail\EmployeeLimitApproachingMail;
use App\Mail\SubscriptionPaymentFailedMail;
use App\Mail\TrialEndingSoonMail;
use App\Mail\TrialExpiredMail;
use App\Models\Organization;
use App\Support\Workspace;
use Illuminate\Support\Collection;

class BillingNotificationService
{
    public function __construct(
        protected OrganizationBillingService $billing,
        protected OrganizationMailService $mail,
    ) {}

    public function sendTrialReminders(): int
    {
        $sent = 0;
        $thresholds = config('billing.trial_reminder_days', [7, 3, 1, 0]);

        Organization::query()
            ->whereNotNull('trial_ends_at')
            ->whereNull('suspended_at')
            ->whereNull('stripe_subscription_id')
            ->whereNull('paypal_subscription_id')
            ->chunkById(50, function ($organizations) use ($thresholds, &$sent): void {
                foreach ($organizations as $organization) {
                    if (! $this->billing->isOnTrial($organization)) {
                        continue;
                    }

                    $daysRemaining = $this->billing->trialDaysRemaining($organization);

                    if ($daysRemaining === null) {
                        continue;
                    }

                    foreach ($thresholds as $threshold) {
                        if ($daysRemaining !== $threshold) {
                            continue;
                        }

                        $key = 'trial_'.$threshold;

                        if ($this->wasReminderSent($organization, $key)) {
                            continue;
                        }

                        $this->notifyTrialThreshold($organization, $threshold);
                        $this->markReminderSent($organization, $key);
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function notifyEmployeeLimitApproaching(Organization $organization): void
    {
        $limit = $this->billing->employeeLimit($organization);

        if ($limit === null || $limit < 2) {
            return;
        }

        $count = $this->billing->activeEmployeeCount($organization);
        $remaining = $limit - $count;

        if ($remaining > 1 || $remaining < 0) {
            return;
        }

        if ($this->wasReminderSent($organization, 'limit_near')) {
            return;
        }

        foreach ($this->billingManagerEmails($organization) as $email) {
            $this->mail->queue($organization, $email, new EmployeeLimitApproachingMail($organization, $count, $limit));
        }

        $this->markReminderSent($organization, 'limit_near');
    }

    public function notifyPaymentFailed(Organization $organization): void
    {
        if ($this->wasReminderSent($organization, 'payment_failed')) {
            return;
        }

        foreach ($this->billingManagerEmails($organization) as $email) {
            $this->mail->queue($organization, $email, new SubscriptionPaymentFailedMail($organization));
        }

        $this->markReminderSent($organization, 'payment_failed');
    }

    public function clearPaymentFailedReminder(Organization $organization): void
    {
        $sent = $organization->billing_reminders_sent ?? [];

        if (! isset($sent['payment_failed'])) {
            return;
        }

        unset($sent['payment_failed']);
        $organization->update(['billing_reminders_sent' => $sent === [] ? null : $sent]);
    }

    protected function notifyTrialThreshold(Organization $organization, int $daysRemaining): void
    {
        $billingUrl = Workspace::route('settings.billing', $organization, [], true);

        foreach ($this->billingManagerEmails($organization) as $email) {
            if ($daysRemaining === 0) {
                $this->mail->queue($organization, $email, new TrialExpiredMail($organization, $billingUrl));
            } else {
                $this->mail->queue($organization, $email, new TrialEndingSoonMail($organization, $daysRemaining, $billingUrl));
            }
        }
    }

    /**
     * @return Collection<int, string>
     */
    protected function billingManagerEmails(Organization $organization): Collection
    {
        return $this->billing->billingManagerEmails($organization);
    }

    protected function wasReminderSent(Organization $organization, string $key): bool
    {
        $sent = $organization->billing_reminders_sent ?? [];

        return isset($sent[$key]);
    }

    protected function markReminderSent(Organization $organization, string $key): void
    {
        $sent = $organization->billing_reminders_sent ?? [];
        $sent[$key] = now()->toIso8601String();
        $organization->update(['billing_reminders_sent' => $sent]);
        $organization->refresh();
    }
}
