<?php

namespace App\Mail;

use App\Models\EmployeeDocument;
use App\Support\Workspace;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DocumentExpiringMail extends ZiifraMailable
{

    public function __construct(public EmployeeDocument $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('documents.mail.expiring_subject', [
                'employee' => $this->document->employee->fullName(),
                'title' => $this->document->title,
            ]),
        );
    }

    public function content(): Content
    {
        $organization = $this->document->organization;

        return new Content(
            markdown: 'emails.document-expiring',
            with: [
                'employeeName' => $this->document->employee->fullName(),
                'documentTitle' => $this->document->title,
                'expiresAt' => $this->document->expires_at?->format('M j, Y'),
                'isExpired' => $this->document->isExpired(),
                'profileUrl' => Workspace::route('employees.show', $organization, [
                    'employee' => $this->document->employee,
                ], true),
            ],
        );
    }
}
