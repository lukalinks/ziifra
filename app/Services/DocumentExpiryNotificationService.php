<?php

namespace App\Services;

use App\Mail\DocumentExpiringMail;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;

class DocumentExpiryNotificationService
{
    public function sendReminders(): int
    {
        $sent = 0;

        EmployeeDocument::query()
            ->with(['employee', 'organization'])
            ->whereNotNull('expires_at')
            ->whereNull('expiry_reminder_sent_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->chunkById(50, function ($documents) use (&$sent): void {
                foreach ($documents as $document) {
                    $organization = $document->organization;

                    if ($organization === null || $organization->suspended_at !== null) {
                        continue;
                    }

                    $recipients = app(OrganizationBillingService::class)
                        ->billingManagerEmails($organization);

                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    foreach ($recipients as $email) {
                        Mail::to($email)->queue(new DocumentExpiringMail($document));
                    }

                    $document->update(['expiry_reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        return $sent;
    }
}
