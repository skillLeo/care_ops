<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Schema;

class ClinicalNoteTrackerMail extends Mailable
{
    protected string $emailBody;
    protected string $emailSubject;
    protected array $emailCc = [];
    protected array $emailBcc = [];

    public function __construct(
        public string $counselorName,
        public $assignedRows,
        public $unassignedRows,
        public $noteTypeOptions,
        public string $asOfDate,
        public ?string $counselorEmail = null
    ) {
        $template = Schema::hasTable('email_templates')
            ? EmailTemplate::where('key', 'clinical_note_tracker')->first()
            : null;
        $replacements = [
            '[counselor_name]' => $this->counselorName,
            '[counselor_email]' => $this->counselorEmail ?? '',
            '[as_of_date]' => $this->asOfDate,
        ];

        $subject = $template?->subject ?? "Daily Chart Compliance Summary - {$this->asOfDate}";
        $body = $template?->body ?? "Hi {$this->counselorName},\n\nPlease see the attached daily chart compliance summary for {$this->asOfDate}.\n";

        $this->emailSubject = strtr($subject, $replacements);
        $this->emailBody = strtr($body, $replacements);
        $this->emailCc = $this->parseEmailList(strtr($template?->cc ?? '', $replacements));
        $this->emailBcc = $this->parseEmailList(strtr($template?->bcc ?? '', $replacements));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            cc: $this->emailCc,
            bcc: $this->emailBcc,
        );
    }

    private function parseEmailList(string $emails): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[;,\n]+/', $emails) ?: [])));
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.clinical_notes_tracker_text',
            with: [
                'body' => $this->emailBody,
            ],
        );
    }

    public function attachments(): array
    {
        $pdfOutput = Pdf::loadView('clinical-notes.tracker_pdf', [
            'assignedRows' => collect($this->assignedRows),
            'unassignedRows' => collect($this->unassignedRows),
            'noteTypeOptions' => collect($this->noteTypeOptions),
            'counselorName' => $this->counselorName,
            'asOfDate' => $this->asOfDate,
        ])->setPaper('letter', 'landscape')->output();

        return [
            Attachment::fromData(
                fn () => $pdfOutput,
                'daily_chart_compliance_summary.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
