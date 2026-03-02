<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $allowedEmails = [
            'abdullah@snbllc.org',
            'fawzan@snbllc.org',
        ];

        $user = $request->user();
        if (! $user || ! in_array($user->email, $allowedEmails, true)) {
            abort(403);
        }

        $query = AuditLog::query();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $applyDefaultRange = ! $dateFrom
            && ! $dateTo
            && ! $request->filled('time_from')
            && ! $request->filled('time_to')
            && ! $request->filled('actor_name')
            && ! $request->filled('action_type')
            && ! $request->filled('search');

        if ($applyDefaultRange) {
            $dateFrom = now()->subDays(30)->toDateString();
            $dateTo = now()->toDateString();
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($request->filled('time_from')) {
            $query->whereTime('created_at', '>=', $request->input('time_from'));
        }

        if ($request->filled('time_to')) {
            $query->whereTime('created_at', '<=', $request->input('time_to'));
        }

        if ($request->filled('actor_name')) {
            $query->where('actor_name', $request->input('actor_name'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('description', 'like', '%' . $search . '%')
                    ->orWhere('actor_name', 'like', '%' . $search . '%')
                    ->orWhere('client_name', 'like', '%' . $search . '%')
                    ->orWhere('action_type', 'like', '%' . $search . '%');
            });
        }

        $auditLogs = (clone $query)
            ->orderByDesc('created_at')
            ->simplePaginate(50)
            ->withQueryString();
        $actors = (clone $query)
            ->whereNotNull('actor_name')
            ->distinct()
            ->orderBy('actor_name')
            ->pluck('actor_name');
        $actionTypes = (clone $query)
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type');

        return view('audit_logs.index', [
            'auditLogs' => $auditLogs,
            'actors' => $actors,
            'actionTypes' => $actionTypes,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'applyDefaultRange' => $applyDefaultRange,
        ]);
    }
}
