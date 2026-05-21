<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TrialEndingSoonMail extends ZiifraMailable
{

    public function __construct(
        public Organization $organization,
        public int $daysRemaining,
        public string $billingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('billing.mail.trial_ending_subject', [
                'days' => $this->daysRemaining,
                'name' => $this->organization->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial-ending-soon',
            with: [
                'organizationName' => $this->organization->name,
                'daysRemaining' => $this->daysRemaining,
                'billingUrl' => $this->billingUrl,
            ],
        );
    }
}
