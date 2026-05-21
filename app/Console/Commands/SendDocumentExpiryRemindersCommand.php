<?php

namespace App\Console\Commands;

use App\Services\DocumentExpiryNotificationService;
use Illuminate\Console\Command;

class SendDocumentExpiryRemindersCommand extends Command
{
    protected $signature = 'documents:send-expiry-reminders';

    protected $description = 'Email HR when employee documents expire within 30 days';

    public function handle(DocumentExpiryNotificationService $notifications): int
    {
        $count = $notifications->sendReminders();

        $this->info("Sent {$count} document expiry reminder(s).");

        return self::SUCCESS;
    }
}
