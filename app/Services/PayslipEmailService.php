<?php

namespace App\Services;

use App\Mail\PayslipMail;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Throwable;

class PayslipEmailService
{
    public function __construct(
        protected OrganizationMailService $mail,
    ) {}
    /**
     * @return array{sent: int, skipped: int, failed: int, failed_names: list<string>}
     */
    public function sendForRun(PayrollRun $payrollRun, ?User $sentBy = null): array
    {
        $payrollRun->loadMissing('items');

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $failedNames = [];

        foreach ($payrollRun->items as $item) {
            $result = $this->sendForItem($item, $sentBy);

            if ($result === 'sent') {
                $sent++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $failed++;
                $failedNames[] = $item->employeeName();
            }
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'failed_names' => $failedNames,
        ];
    }

    public function sendForItem(PayrollItem $item, ?User $sentBy = null): string
    {
        $email = $item->payslipRecipientEmail();

        if ($email === null) {
            return 'skipped';
        }

        try {
            $item->loadMissing('organization');
            $this->mail->sendNow($item->organization, $email, new PayslipMail($item, $sentBy));

            return 'sent';
        } catch (Throwable $exception) {
            report($exception);

            return 'failed';
        }
    }
}
