<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\AuthLineOfService;
use App\Models\AuditLog;
use App\Models\Authorization;
use App\Models\Check;
use App\Models\Claim;
use App\Models\Client;
use App\Models\LevelOfCare;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    private const DISPLAY_LABELS = [
        'ClientGroup' => 'Client Group',
        'PeerGroup' => 'Peer Group',
        'MedicalContact' => 'Medical Contact',
        'AuthLineOfService' => 'Authorization Line of Service',
        'LevelOfCare' => 'Level of Care',
        'Dropbox' => 'Dropbox Submission',
    ];

    private const DETAILED_LABELS = [
        'House',
        'Apartment',
        'Client',
        'ClientGroup',
        'PeerGroup',
        'MedicalContact',
        'Authorization',
        'AuthLineOfService',
        'User',
        'Claim',
        'Check',
        'Attendance',
        'LevelOfCare',
        'Dropbox',
    ];

    public function created(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $attributes = $this->sanitizeAttributes($model->getAttributes());
        $this->logChange('created', $model, $attributes);
    }

    public function updated(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $changes = $this->filterAttributes($model->getChanges());
        if (empty($changes)) {
            return;
        }

        $original = $this->filterAttributes($model->getOriginal());
        $before = array_intersect_key($original, $changes);

        $this->logChange('updated', $model, $changes, $before);
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $attributes = $this->sanitizeAttributes($model->getAttributes());
        $this->logChange('deleted', $model, [], $attributes);
    }

    private function logChange(string $action, Model $model, array $after = [], array $before = []): void
    {
        $actorName = Auth::user()?->name ?? 'System';
        $label = class_basename($model);
        $identifier = $this->describeTarget($model);
        $fieldList = $action === 'updated' ? ' (fields: ' . implode(', ', array_keys($after)) . ')' : '';
        $changeSummary = $action === 'updated' ? $this->formatChangeSummary($before, $after) : '';
        $client = $this->resolveClient($model);
        $actionLabel = $this->formatActionLabel($label, $action);
        $description = $this->formatDescription(
            $label,
            $action,
            $actionLabel,
            $actorName,
            $identifier,
            $fieldList,
            $changeSummary,
            $before,
            $after
        );

        AuditLogger::log(
            sprintf('%s_%s', strtolower($label), $action),
            $description,
            $client,
            [
                'model' => $label,
                'model_id' => $model->getKey(),
                'before' => $before ?: null,
                'after' => $after ?: null,
            ]
        );
    }

    private function describeTarget(Model $model): string
    {
        $key = $model->getKey();

        if ($model instanceof Client) {
            $fullName = trim(sprintf('%s %s', $model->getAttribute('first_name'), $model->getAttribute('last_name')));
            return $fullName !== '' ? sprintf('client %s', $fullName) : sprintf('client #%s', $key);
        }

        if ($model instanceof Authorization) {
            $authNumber = $model->getAttribute('auth_number');
            return $authNumber ? sprintf('authorization %s', $authNumber) : sprintf('authorization #%s', $key);
        }

        if ($model instanceof User) {
            $name = $model->getAttribute('name');
            $email = $model->getAttribute('email');

            if ($name && $email) {
                return sprintf('%s (%s)', $name, $email);
            }

            return $name ?: ($email ? sprintf('user %s', $email) : sprintf('user #%s', $key));
        }

        if ($model instanceof Claim) {
            $claimNumber = $model->getAttribute('claim_number');

            return $claimNumber ? sprintf('claim %s', $claimNumber) : sprintf('claim #%s', $key);
        }

        if ($model instanceof Check) {
            $checkNumber = $model->getAttribute('check_number');

            return $checkNumber ? sprintf('check %s', $checkNumber) : sprintf('check #%s', $key);
        }

        if ($model instanceof Attendance) {
            $serviceDate = $model->getAttribute('service_date');
            $dateLabel = $serviceDate ? date('m/d/Y', strtotime($serviceDate)) : null;
            $client = $this->resolveClient($model);

            if ($client) {
                $clientName = trim($client->first_name . ' ' . $client->last_name);
                $clientLabel = $clientName !== '' ? $clientName : sprintf('client #%s', $client->getKey());

                return $dateLabel
                    ? sprintf('attendance for %s on %s', $clientLabel, $dateLabel)
                    : sprintf('attendance for %s', $clientLabel);
            }

            return $dateLabel ? sprintf('attendance on %s', $dateLabel) : sprintf('attendance #%s', $key);
        }

        if ($model instanceof LevelOfCare) {
            $displayName = $model->getAttribute('display_name');
            $level = $model->getAttribute('level_of_care');
            $label = $displayName ?: $level;

            return $label ? sprintf('level of care %s', $label) : sprintf('level of care #%s', $key);
        }

        if ($model instanceof AuthLineOfService) {
            $serviceType = $model->getAttribute('type');
            $serviceLabel = $serviceType ? sprintf('%s line of service', ucfirst($serviceType)) : 'Line of service';
            $authorization = $model->relationLoaded('authorization') ? $model->authorization : null;

            if (! $authorization && $model->getAttribute('auth_id')) {
                $authorization = Authorization::find($model->getAttribute('auth_id'));
            }

            if ($authorization) {
                $authNumber = $authorization->getAttribute('auth_number');
                $authLabel = $authNumber ? sprintf('authorization %s', $authNumber) : sprintf('authorization #%s', $authorization->getKey());

                return sprintf('%s for %s', $serviceLabel, $authLabel);
            }

            return sprintf('%s #%s', $serviceLabel, $key);
        }

        if ($model->getAttribute('house_name')) {
            return sprintf('House #%s', $key);
        }

        if ($model->getAttribute('apartment_number')) {
            return sprintf('Apartment #%s', $key);
        }

        if ($model->getAttribute('first_name') || $model->getAttribute('last_name')) {
            return trim($model->getAttribute('first_name') . ' ' . $model->getAttribute('last_name')) ?: class_basename($model) . ' #' . $key;
        }

        foreach (['name', 'display_name', 'title', 'house_name', 'apartment_number'] as $field) {
            if ($model->getAttribute($field)) {
                return sprintf('%s #%s', $model->getAttribute($field), $key);
            }
        }

        return class_basename($model) . ' #' . $key;
    }

    private function resolveClient(Model $model): ?Client
    {
        if ($model instanceof Client) {
            return $model;
        }

        if ($model->relationLoaded('client') && $model->client instanceof Client) {
            return $model->client;
        }

        if ($model->relationLoaded('authorization') && $model->authorization?->client instanceof Client) {
            return $model->authorization->client;
        }

        if ($model->getAttribute('client_id')) {
            return Client::find($model->getAttribute('client_id'));
        }

        if ($model->getAttribute('auth_id')) {
            $authorization = Authorization::find($model->getAttribute('auth_id'));

            return $authorization?->client;
        }

        return null;
    }

    private function filterAttributes(array $attributes): array
    {
        $filtered = $this->sanitizeAttributes($attributes);
        unset($filtered['created_at'], $filtered['updated_at'], $filtered['deleted_at']);

        return $filtered;
    }

    private function sanitizeAttributes(array $attributes): array
    {
        foreach (['password', 'remember_token', 'pin'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = '[redacted]';
            }
        }

        return $attributes;
    }

    private function formatChangeSummary(array $before, array $after): string
    {
        if (empty($after)) {
            return '';
        }

        $beforeParts = $this->formatFieldPairs($before);
        $afterParts = $this->formatFieldPairs($after);

        if ($beforeParts === '' && $afterParts === '') {
            return '';
        }

        return sprintf(' (%s) to (%s)', $beforeParts ?: 'no previous values', $afterParts ?: 'no new values');
    }

    private function formatFieldPairs(array $values): string
    {
        if (empty($values)) {
            return '';
        }

        $pairs = [];

        foreach ($values as $field => $value) {
            $pairs[] = sprintf('%s: %s', $field, $this->stringifyValue($value));
        }

        return implode(', ', $pairs);
    }

    private function stringifyValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    private function formatActionLabel(string $label, string $action): string
    {
        $displayLabel = self::DISPLAY_LABELS[$label] ?? preg_replace('/(?<!^)[A-Z]/', ' $0', $label);

        return sprintf('%s %s', $displayLabel, ucfirst($action));
    }

    private function formatDescription(
        string $label,
        string $action,
        string $actionLabel,
        string $actorName,
        string $identifier,
        string $fieldList,
        string $changeSummary,
        array $before,
        array $after
    ): string {
        if (in_array($label, self::DETAILED_LABELS, true)) {
            return match ($action) {
                'created' => sprintf(
                    '%s: %s created %s (%s).',
                    $actionLabel,
                    $actorName,
                    $identifier,
                    $this->formatFieldPairs($after)
                ),
                'deleted' => sprintf(
                    '%s: %s deleted %s (%s).',
                    $actionLabel,
                    $actorName,
                    $identifier,
                    $this->formatFieldPairs($before)
                ),
                'updated' => sprintf(
                    '%s: %s updated %s%s%s.',
                    $actionLabel,
                    $actorName,
                    $identifier,
                    $fieldList,
                    $changeSummary
                ),
                default => sprintf('%s %s %s.', $actorName, $action, $identifier),
            };
        }

        return sprintf('%s %s %s%s%s.', $actorName, $action, $identifier, $fieldList, $changeSummary);
    }
}
