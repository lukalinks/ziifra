<?php

namespace App\Notifications;

use App\Models\ExpenseClaim;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExpenseClaimReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExpenseClaim $expenseClaim,
        public User $reviewer,
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
        $claim->loadMissing(['organization']);

        return [
            'title' => __('notifications.expense_reviewed_title'),
            'body' => __('notifications.expense_reviewed_body', [
                'status' => $claim->status->label(),
                'title' => $claim->title,
            ]),
            'url' => Workspace::route('expenses.show', $claim->organization, ['expenseClaim' => $claim]),
            'icon' => 'expense',
            'organization_id' => $claim->organization_id,
        ];
    }
}
