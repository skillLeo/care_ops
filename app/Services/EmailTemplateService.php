<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Dropbox;
use App\Models\EmailTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class EmailTemplateService
{
    public const PLACEHOLDERS = [
        '[first_name]' => 'Client first name',
        '[last_name]' => 'Client last name',
        '[full_name]' => 'Client full name',
        '[mrn]' => 'Client MRN',
        '[dob]' => 'Client date of birth',
        '[medicaid_id]' => 'Client Medicaid ID',
        '[carelon_id]' => 'Client Carelon ID',
        '[starting_date]' => 'Client starting date',
        '[discharge_date]' => 'Client discharge date',
        '[reactivation_date]' => 'Client reactivation date',
        '[current_level_of_care]' => 'Current level of care',
        '[first_level_of_care]' => 'First level of care',
        '[last_level_of_care]' => 'Last level of care',
        '[previous_level_of_care]' => 'Previous level of care',
        '[previous_level_of_care_start]' => 'Previous level of care start date',
        '[previous_level_of_care_end]' => 'Previous level of care end date',
        '[new_level_of_care]' => 'New level of care',
        '[new_level_of_care_start]' => 'New level of care start date',
        '[authorization_numbers]' => 'Authorization numbers list',
        '[counselor_name]' => 'Counselor name',
        '[peer_name]' => 'Peer name',
        '[counselor_email]' => 'Counselor email',
        '[peer_email]' => 'Peer email',
        '[client_email]' => 'Client email',
        '[hospitalization_date]' => 'Hospitalization date',
        '[hospitalization_type]' => 'Hospitalization type',
        '[hospitalization_facility]' => 'Hospitalization facility',
        '[medical_contact_name]' => 'Medical contact name',
        '[medical_contact_email]' => 'Medical contact email',
        '[as_of_date]' => 'As of date',
    ];

    public const DROPBOX_PLACEHOLDERS = [
        '[dropbox_first_name]' => 'Dropbox first name',
        '[dropbox_last_name]' => 'Dropbox last name',
        '[dropbox_full_name]' => 'Dropbox full name',
        '[dropbox_email]' => 'Dropbox email',
        '[dropbox_phone]' => 'Dropbox phone',
        '[dropbox_dob]' => 'Dropbox date of birth',
        '[dropbox_gender]' => 'Dropbox gender',
        '[dropbox_type]' => 'Dropbox type',
        '[dropbox_returning_client]' => 'Dropbox returning client',
        '[dropbox_currently_in_program]' => 'Dropbox currently in program',
        '[dropbox_program_name]' => 'Dropbox program name',
        '[dropbox_program_contact_details]' => 'Dropbox program contact details',
        '[dropbox_program_level_of_care]' => 'Dropbox program level of care',
        '[dropbox_document_delivery_method]' => 'Dropbox document delivery method',
        '[dropbox_submission_date]' => 'Dropbox submission date',
        '[dropbox_status]' => 'Dropbox status',
    ];

    public function fetchTemplate(string $key): ?EmailTemplate
    {
        if (! Schema::hasTable('email_templates')) {
            return null;
        }

        return EmailTemplate::where('key', $key)->first();
    }

    public function buildMessage(string $key, Client $client, array $context = []): ?array
    {
        $template = $this->fetchTemplate($key);
        if (! $template) {
            return null;
        }

        $replacements = $this->buildReplacements($client, $context);

        return [
            'template' => $template,
            'subject' => $this->replacePlaceholders($template->subject, $replacements),
            'body' => $this->replacePlaceholders($template->body, $replacements),
            'to' => $this->renderEmailList($template->to, $replacements),
            'cc' => $this->renderEmailList($template->cc, $replacements),
            'bcc' => $this->renderEmailList($template->bcc, $replacements),
        ];
    }

    public function buildDropboxMessage(string $key, Dropbox $dropbox, array $context = []): ?array
    {
        $template = $this->fetchTemplate($key);
        if (! $template) {
            return null;
        }

        $replacements = $this->buildDropboxReplacements($dropbox, $context);

        return [
            'template' => $template,
            'subject' => $this->replacePlaceholders($template->subject, $replacements),
            'body' => $this->replacePlaceholders($template->body, $replacements),
            'to' => $this->renderEmailList($template->to, $replacements),
            'cc' => $this->renderEmailList($template->cc, $replacements),
            'bcc' => $this->renderEmailList($template->bcc, $replacements),
        ];
    }

    public function parseEmailList(?string $emails): array
    {
        if (! $emails) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[;,\n]+/', $emails) ?: [])));
    }

    private function renderEmailList(?string $value, array $replacements): array
    {
        $rendered = $this->replacePlaceholders($value, $replacements);

        return $this->parseEmailList($rendered);
    }

    private function replacePlaceholders(?string $text, array $replacements): string
    {
        if ($text === null) {
            return '';
        }

        return strtr($text, $replacements);
    }

    private function buildReplacements(Client $client, array $context): array
    {
        $client->loadMissing(['levelOfCareHistory.levelOfCare', 'counselor', 'peer']);

        $history = $client->levelOfCareHistory
            ->sortBy('start_date')
            ->values();

        $firstLoc = $history->first()?->levelOfCare?->display_name ?? '';
        $lastLoc = $history->last()?->levelOfCare?->display_name ?? '';
        $currentLoc = $client->levelOfCareHistory
            ->firstWhere('end_date', null)?->levelOfCare?->display_name
            ?? $lastLoc;

        $counselorName = $context['counselor_name'] ?? $client->counselor?->name ?? '';
        $counselorEmail = $context['counselor_email'] ?? $client->counselor?->email ?? '';
        $peerName = $context['peer_name'] ?? $client->peer?->name ?? '';
        $peerEmail = $context['peer_email'] ?? $client->peer?->email ?? '';

        return [
            '[first_name]' => $client->first_name ?? '',
            '[last_name]' => $client->last_name ?? '',
            '[full_name]' => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            '[mrn]' => $client->mrn ?? '',
            '[dob]' => $this->formatDate($client->date_of_birth),
            '[medicaid_id]' => $client->medicaid_id ?? '',
            '[carelon_id]' => $client->carelon_id ?? '',
            '[starting_date]' => $this->formatDate($context['starting_date'] ?? $client->starting_date),
            '[discharge_date]' => $this->formatDate($context['discharge_date'] ?? $client->discharge_date),
            '[reactivation_date]' => $this->formatDate($context['reactivation_date'] ?? $client->reactivation_date),
            '[current_level_of_care]' => $context['current_level_of_care'] ?? $currentLoc,
            '[first_level_of_care]' => $context['first_level_of_care'] ?? $firstLoc,
            '[last_level_of_care]' => $context['last_level_of_care'] ?? $lastLoc,
            '[previous_level_of_care]' => $context['previous_level_of_care'] ?? '',
            '[previous_level_of_care_start]' => $this->formatDate($context['previous_level_of_care_start'] ?? null),
            '[previous_level_of_care_end]' => $this->formatDate($context['previous_level_of_care_end'] ?? null),
            '[new_level_of_care]' => $context['new_level_of_care'] ?? '',
            '[new_level_of_care_start]' => $this->formatDate($context['new_level_of_care_start'] ?? null),
            '[authorization_numbers]' => $context['authorization_numbers'] ?? '',
            '[counselor_name]' => $counselorName,
            '[peer_name]' => $peerName,
            '[counselor_email]' => $counselorEmail,
            '[peer_email]' => $peerEmail,
            '[client_email]' => $client->email ?? '',
            '[hospitalization_date]' => $this->formatDate($context['hospitalization_date'] ?? null),
            '[hospitalization_type]' => $context['hospitalization_type'] ?? '',
            '[hospitalization_facility]' => $context['hospitalization_facility'] ?? '',
            '[medical_contact_name]' => $context['medical_contact_name'] ?? '',
            '[medical_contact_email]' => $context['medical_contact_email'] ?? '',
        ];
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return '';
        }

        return Carbon::parse($value)->format('m/d/Y');
    }

    private function buildDropboxReplacements(Dropbox $dropbox, array $context): array
    {
        $returningClient = $dropbox->returning_client ? 'Yes' : 'No';
        $currentlyInProgram = $dropbox->currently_in_program ? 'Yes' : 'No';

        return [
            '[dropbox_first_name]' => $dropbox->first_name ?? '',
            '[dropbox_last_name]' => $dropbox->last_name ?? '',
            '[dropbox_full_name]' => $dropbox->full_name,
            '[dropbox_email]' => $dropbox->email ?? '',
            '[dropbox_phone]' => $dropbox->phone ?? '',
            '[dropbox_dob]' => $this->formatDate($dropbox->date_of_birth),
            '[dropbox_gender]' => $dropbox->gender ?? '',
            '[dropbox_type]' => $dropbox->type ?? '',
            '[dropbox_returning_client]' => $context['dropbox_returning_client'] ?? $returningClient,
            '[dropbox_currently_in_program]' => $context['dropbox_currently_in_program'] ?? $currentlyInProgram,
            '[dropbox_program_name]' => $dropbox->program_name ?? '',
            '[dropbox_program_contact_details]' => $dropbox->program_contact_details ?? '',
            '[dropbox_program_level_of_care]' => $dropbox->program_level_of_care ?? '',
            '[dropbox_document_delivery_method]' => $dropbox->document_delivery_method ?? '',
            '[dropbox_submission_date]' => $this->formatDate($dropbox->created_at),
            '[dropbox_status]' => $dropbox->status ?? '',
        ];
    }
}
