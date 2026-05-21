<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Support\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest,
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
        $request = $this->leaveRequest;
        $request->loadMissing(['employee', 'leaveType', 'organization']);

        return [
            'title' => __('notifications.leave_submitted_title'),
            'body' => __('notifications.leave_submitted_body', [
                'employee' => $request->employee->fullName(),
                'type' => $request->leaveType->name,
            ]),
            'url' => Workspace::route('leave.show', $request->organization, ['leaveRequest' => $request]),
            'icon' => 'leave',
            'organization_id' => $request->organization_id,
        ];
    }
}
