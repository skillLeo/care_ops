<?php

namespace App\Mail;

use App\Models\Dropbox;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DropboxRoiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Dropbox $dropbox, public string $roiPath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Signed Authorization to Release Information',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dropboxes.roi',
            with: [
                'dropbox' => $this->dropbox,
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->roiPath) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->roiPath)
                ->as(basename($this->roiPath))
                ->withMime('application/pdf'),
        ];
    }
}
