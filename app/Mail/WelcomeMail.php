<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeMail extends ZiifraMailable
{

    public function __construct(
        public User $user,
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' — '.$this->organization->name.' is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'userName' => $this->user->name,
                'organizationName' => $this->organization->name,
                'workspaceUrl' => Workspace::route('dashboard', $this->organization),
                'settingsUrl' => Workspace::route('settings.index', $this->organization),
            ],
        );
    }
}
