<?php

namespace App\Console\Commands;

use App\Services\BillingNotificationService;
use Illuminate\Console\Command;

class SendBillingRemindersCommand extends Command
{
    protected $signature = 'billing:send-reminders';

    protected $description = 'Send trial-ending emails to organization owners and admins';

    public function handle(BillingNotificationService $notifications): int
    {
        $count = $notifications->sendTrialReminders();

        $this->info("Sent {$count} billing reminder(s).");

        return self::SUCCESS;
    }
}
