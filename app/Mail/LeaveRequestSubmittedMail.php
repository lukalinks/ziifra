<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Support\Workspace;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeaveRequestSubmittedMail extends ZiifraMailable
{

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function envelope(): Envelope
    {
        $employee = $this->leaveRequest->employee->fullName();

        return new Envelope(
            subject: "Leave request from {$employee} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leave-request-submitted',
            with: [
                'employeeName' => $this->leaveRequest->employee->fullName(),
                'leaveType' => $this->leaveRequest->leaveType->name,
                'startDate' => $this->leaveRequest->start_date->format('M j, Y'),
                'endDate' => $this->leaveRequest->end_date->format('M j, Y'),
                'days' => number_format((float) $this->leaveRequest->days, 1),
                'reviewUrl' => Workspace::route('leave.show', $this->leaveRequest->organization, [
                    'leaveRequest' => $this->leaveRequest,
                ]),
                'submittedBy' => $this->leaveRequest->submittedBy?->name,
            ],
        );
    }
}
