<?php

namespace App\Mail;

use App\Models\Dropbox;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DropboxApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Dropbox $dropbox)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dropbox Approved: ' . $this->dropbox->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dropboxes.approval',
            with: [
                'dropbox' => $this->dropbox,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
