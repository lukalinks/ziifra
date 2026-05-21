<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeaveRequestReviewedMail extends ZiifraMailable
{

    public function __construct(
        public LeaveRequest $leaveRequest,
        public User $reviewer,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->leaveRequest->status->label();

        return new Envelope(
            subject: "Your leave request was {$status} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leave-request-reviewed',
            with: [
                'status' => $this->leaveRequest->status->label(),
                'leaveType' => $this->leaveRequest->leaveType->name,
                'startDate' => $this->leaveRequest->start_date->format('M j, Y'),
                'endDate' => $this->leaveRequest->end_date->format('M j, Y'),
                'days' => number_format((float) $this->leaveRequest->days, 1),
                'reviewerName' => $this->reviewer->name,
                'rejectionReason' => $this->leaveRequest->rejection_reason,
                'viewUrl' => Workspace::route('leave.show', $this->leaveRequest->organization, [
                    'leaveRequest' => $this->leaveRequest,
                ]),
            ],
        );
    }
}
