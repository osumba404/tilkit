<?php

namespace App\Mail;

use App\Models\DcProject;
use App\Models\ToolInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ToolCollectorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ToolInvitation $invitation,
        public DcProject $tool,
        public User $inviter,
        public ?string $acceptUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to collect data on Tilkit: '.$this->tool->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tool-collector-invitation',
            with: [
                'acceptUrl' => $this->acceptUrl ?? route('invitations.accept', $this->invitation->token),
                'toolName' => $this->tool->name,
                'inviterName' => $this->inviter->name,
            ],
        );
    }
}
