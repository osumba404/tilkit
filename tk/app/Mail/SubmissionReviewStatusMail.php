<?php

namespace App\Mail;

use App\Models\DcSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionReviewStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DcSubmission $submission,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->status === 'approved' ? 'approved' : 'needs revision';

        return new Envelope(
            subject: 'Submission '.$label.' - '.$this->submission->project->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.submission-review-status',
        );
    }
}
