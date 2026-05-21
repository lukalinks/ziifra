<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TrialExpiredMail extends ZiifraMailable
{

    public function __construct(
        public Organization $organization,
        public string $billingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('billing.mail.trial_expired_subject', ['name' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial-expired',
            with: [
                'organizationName' => $this->organization->name,
                'billingUrl' => $this->billingUrl,
            ],
        );
    }
}
