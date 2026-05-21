<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeamInvitationMail extends ZiifraMailable
{

    public function __construct(public Invitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to join '.$this->invitation->organization->name.' on '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'acceptUrl' => route('invitations.accept', $this->invitation->token),
                'organizationName' => $this->invitation->organization->name,
                'role' => $this->invitation->role->label(),
            ],
        );
    }
}
