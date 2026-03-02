<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionDuplicateController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'service_date' => ['required', 'date'],
            'client_group_id' => ['nullable'],
            'peer_group_id' => ['nullable', 'integer'],
            'submitted_by' => ['nullable', 'integer'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i'],
            'view_route' => ['required', 'string'],
        ]);

        $query = AttendanceSubmission::query()
            ->with('submittedBy:id,name')
            ->where('type', $data['type'])
            ->whereDate('service_date', $data['service_date']);

        if (array_key_exists('client_group_id', $data)) {
            $clientGroupId = $data['client_group_id'];
            if ($clientGroupId === null || $clientGroupId === '' || $clientGroupId === 'all') {
                $query->whereNull('client_group_id');
            } else {
                $query->where('client_group_id', (int) $clientGroupId);
            }
        }

        if (!empty($data['peer_group_id'])) {
            $query->where('peer_group_id', $data['peer_group_id']);
        }

        if (!empty($data['submitted_by'])) {
            $query->where('submitted_by', $data['submitted_by']);
        }

        if (!empty($data['time_start'])) {
            $query->where('time_start', $data['time_start'] . ':00');
        }

        if (!empty($data['time_end'])) {
            $query->where('time_end', $data['time_end'] . ':00');
        }

        $duplicates = $query
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (AttendanceSubmission $submission) use ($data) {
                return [
                    'id' => $submission->id,
                    'submitted_by' => $submission->submittedBy?->name,
                    'created_at' => optional($submission->created_at)->format('m/d/Y h:i A'),
                    'view_url' => route($data['view_route'], $submission),
                ];
            })
            ->values();

        return response()->json([
            'duplicates' => $duplicates,
        ]);
    }
}
