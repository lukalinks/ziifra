<?php

namespace App\Notifications;

use App\Models\ExpenseClaim;
use App\Support\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExpenseClaimSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseClaim $expenseClaim,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $claim = $this->expenseClaim;
        $claim->loadMissing(['employee', 'organization']);

        return [
            'title' => __('notifications.expense_submitted_title'),
            'body' => __('notifications.expense_submitted_body', [
                'employee' => $claim->employee->fullName(),
                'title' => $claim->title,
                'amount' => $claim->formattedAmount(),
            ]),
            'url' => Workspace::route('expenses.show', $claim->organization, ['expenseClaim' => $claim]),
            'icon' => 'expense',
            'organization_id' => $claim->organization_id,
        ];
    }
}
