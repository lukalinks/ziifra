<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionPaymentFailedMail extends ZiifraMailable
{

    public function __construct(public Organization $organization) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('billing.mail.payment_failed_subject', ['name' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-payment-failed',
            with: [
                'organizationName' => $this->organization->name,
                'billingUrl' => \App\Support\Workspace::route('settings.billing', $this->organization, [], true),
            ],
        );
    }
}
