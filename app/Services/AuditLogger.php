<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $actionType, string $description, ?Client $client = null, array $metadata = []): void
    {
        $actorName = Auth::user()?->name;
        $clientName = $client
            ? trim(sprintf('%s %s', $client->first_name, $client->last_name))
            : null;

        AuditLog::create([
            'actor_name' => $actorName,
            'client_name' => $clientName,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}
