<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmployeeLimitApproachingMail extends ZiifraMailable
{

    public function __construct(
        public Organization $organization,
        public int $employeeCount,
        public int $employeeLimit,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('billing.mail.limit_approaching_subject', ['name' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.employee-limit-approaching',
            with: [
                'organizationName' => $this->organization->name,
                'employeeCount' => $this->employeeCount,
                'employeeLimit' => $this->employeeLimit,
                'billingUrl' => \App\Support\Workspace::route('settings.billing', $this->organization, [], true),
            ],
        );
    }
}
