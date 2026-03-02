<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditLogRequest
{
    private static bool $logging = false;
    private const MODULES = [
        'houses' => 'Houses',
        'clients' => 'Clients',
        'client-groups' => 'Client Groups',
        'peer-groups' => 'Peer Groups',
        'medical-contacts' => 'Medical Contacts',
        'auth-to-release-info' => 'Auth to Release Info',
        'authorizations' => 'Authorizations',
        'consents' => 'Consents',
        'verification-letters' => 'Verification Letters',
        'group_notes' => 'Group Notes',
        'attendances' => 'Attendance',
        'reports' => 'Reports',
        'dropboxes' => 'Dropbox',
        'users' => 'Users',
        'pin' => 'PIN',
        'level-of-cares' => 'Levels of Care',
        'claims' => 'Claims',
        'checks' => 'Checks',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        $routeName = $route?->getName();
        $moduleKey = $routeName ? $this->resolveModuleKey($routeName) : null;
        $queries = [];

        if ($user && $routeName && $moduleKey) {
            DB::listen(function ($query) use (&$queries) {
                if (self::$logging) {
                    return;
                }

                if (str_contains($query->sql, 'audit_logs')) {
                    return;
                }

                $queries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ];
            });
        }

        $response = $next($request);

        if (! $user || ! $routeName || $routeName === 'audit-logs.index') {
            return $response;
        }

        if (! $moduleKey) {
            return $response;
        }

        if ($moduleKey === 'reports') {
            return $response;
        }

        if (! $this->shouldLog($request, $routeName)) {
            return $response;
        }

        $moduleLabel = self::MODULES[$moduleKey];
        $action = $this->resolveAction($request, $routeName);
        $actorName = $user->name;

        $client = $this->resolveClient($route);
        $target = $client
            ? sprintf('client %s %s', $client->first_name, $client->last_name)
            : $moduleLabel;

        $description = sprintf('%s %s %s.', $actorName, $action, $target);

        AuditLogger::log(
            sprintf('%s_%s', $moduleKey, str_replace(' ', '_', $action)),
            $description,
            $client,
            [
                'route' => $routeName,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'payload_keys' => array_keys($request->except(['password', 'password_confirmation', 'pin'])),
            ]
        );

        foreach ($queries as $queryData) {
            self::$logging = true;
            AuditLogger::log(
                sprintf('%s_db_query', $moduleKey),
                sprintf('%s ran a database query in %s.', $actorName, $moduleLabel),
                $client,
                $queryData
            );
            self::$logging = false;
        }

        return $response;
    }

    private function resolveModuleKey(string $routeName): ?string
    {
        $prefix = explode('.', $routeName)[0];

        return array_key_exists($prefix, self::MODULES) ? $prefix : null;
    }

    private function resolveAction(Request $request, string $routeName): string
    {
        $method = $request->method();
        $action = match (true) {
            str_contains($routeName, 'clients.discharge') => 'discharged',
            str_contains($routeName, 'clients.reactivate') => 'reactivated',
            default => null,
        };

        if (! $action && str_contains($routeName, 'download')) {
            $action = 'downloaded';
        }

        if (! $action) {
            $action = match ($method) {
                'POST' => 'created',
                'PUT', 'PATCH' => 'updated',
                'DELETE' => 'deleted',
                default => 'viewed',
            };
        }

        return $action;
    }

    private function shouldLog(Request $request, string $routeName): bool
    {
        $method = $request->method();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        return str_contains($routeName, 'download')
            || str_contains($routeName, 'export')
            || str_contains($routeName, 'report');
    }

    private function resolveClient($route): ?Client
    {
        $clientParam = $route?->parameter('client') ?? $route?->parameter('id') ?? null;

        if ($clientParam instanceof Client) {
            return $clientParam;
        }

        if (is_numeric($clientParam)) {
            return Client::find($clientParam);
        }

        return null;
    }
}
