<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\LevelOfCare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function show(Request $request, Client $client)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $sessionType = $request->input('session_type', 'group');

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendances = Attendance::where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy(fn($a) => $a->service_date->format('Y-m-d'));

        return view('attendance.calendar', compact('client', 'month', 'year', 'sessionType', 'attendances'));
    }

    public function store(Request $request, Client $client)
    {
        $request->validate([
            'attachments.*' => ['nullable', 'file', 'max:2048'],
        ]);

        $sessionType = $request->input('session_type', 'group');
        $attachments = $this->storeAttachments($request);

        foreach ($request->attendance as $date => $data) {
            $units = intval($data['units']);
            $attended = $units > 0;

            if (!empty($data['id'])) {
                $attendance = Attendance::find($data['id']);
                if (! $attendance) {
                    continue;
                }

                if (! $attended) {
                    $attendance->delete();
                    continue;
                }

                $payload = [
                    'units' => $units,
                    'attended' => true,
                    'session_type' => $sessionType,
                    'marked_by' => auth()->id(),
                ];
                if (! empty($attachments)) {
                    $payload['attachments'] = array_values(array_merge($attendance->attachments ?? [], $attachments));
                }

                $attendance->update($payload);
            } elseif ($attended) {
                $payload = [
                    'client_id' => $client->id,
                    'service_date' => $date,
                    'session_type' => $sessionType,
                    'units' => $units,
                    'attended' => true,
                    'marked_by' => auth()->id(),
                ];
                if (! empty($attachments)) {
                    $payload['attachments'] = $attachments;
                }
                Attendance::create($payload);
            }
        }

        return redirect()->back()->with('success', 'Attendance saved successfully.');
    }

    public function batch(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $sessionType = $request->input('session_type', 'group');
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');
        $guestFilter = $request->input('guest', 'all');

        $counselors = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $houses = \App\Models\House::all();
        $apartments = ($houseId !== 'all' && $houseId !== 'unhoused')
            ? \App\Models\Apartment::where('house_id', $houseId)->get()
            : collect();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $clients = collect();
        $attendanceData = [];

        if ($sessionType) {
            $query = Client::with(['apartment.house', 'counselor', 'peer'])
                ->whereDate('starting_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('discharge_date')
                        ->orWhereDate('discharge_date', '>=', $selectedDate);
                })
                ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                    $q->where('start_date', '<', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate);
                        });
                });

            if ($counselorId !== 'all') {
                $query->whereHas('counselorHistory', function ($q) use ($counselorId, $selectedDate) { $q->where('counselor_id', $counselorId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
            }
            if ($peerId !== 'all') {
                $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
            }
            if ($houseId === 'unhoused') {
                $query->whereDoesntHave('apartmentHistory', function ($q) use ($selectedDate) { $q->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
            } elseif ($houseId !== 'all') {
                $query->whereHas('apartment', function ($q) use ($houseId) {
                    $q->where('house_id', $houseId);
                });
            }
            if ($apartmentId !== 'all') {
                $query->whereHas('apartmentHistory', function ($q) use ($apartmentId, $selectedDate) { $q->where('apartment_id', $apartmentId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
            }
            if ($guestFilter !== 'all') {
                $query->where('guest', $guestFilter);
            }

            $clients = $query->orderBy('last_name')->get();

            foreach ($clients as $client) {
                $levelsForDate = $client->levelOfCareHistory()
                    ->with('levelOfCare')
                    ->where('start_date', '<=', $selectedDate)
                    ->where(function ($q) use ($selectedDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                    })
                    ->get();

                $client->level_of_care = $levelsForDate
                    ->map(fn ($level) => $level->levelOfCare?->level_of_care)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            }

            if ($levelOfCareFilter !== 'all') {
                $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                    $levels = collect(explode(',', (string) $client->level_of_care))
                        ->map(fn ($level) => trim($level))
                        ->filter()
                        ->values();

                    return $levels->containsStrict($levelOfCareFilter);
                })->values();
            }

            $attendanceData = Attendance::whereDate('service_date', $selectedDate)
                ->where('session_type', $sessionType)
                ->get()
                ->keyBy('client_id');
        }

        return view('attendance.batch', compact(
            'clients',
            'attendanceData',
            'selectedDate',
            'sessionType',
            'counselorId',
            'peerId',
            'houseId',
            'apartmentId',
            'levelOfCareFilter',
            'guestFilter',
            'counselors',
            'peers',
            'houses',
            'apartments',
            'levels'
        ));
    }

    public function batchStore(Request $request)
    {
        $request->validate([
            'attachments.*' => ['nullable', 'file', 'max:2048'],
        ]);

        $date = $request->input('service_date');
        $sessionType = $request->input('session_type', 'group');
        $attachments = $this->storeAttachments($request);

        foreach ($request->attendance as $clientId => $data) {
            $present = !empty($data['present']);

            if ($present && isset($data['units'])) {
                $existing = Attendance::where('client_id', $clientId)
                    ->whereDate('service_date', $date)
                    ->where('session_type', $sessionType)
                    ->first();

                $payload = [
                    'client_id' => $clientId,
                    'service_date' => $date,
                    'session_type' => $sessionType,
                    'attended' => true,
                    'units' => $data['units'],
                    'marked_by' => auth()->id(),
                ];

                if (! empty($attachments)) {
                    $existingAttachments = $existing?->attachments ?? [];
                    $payload['attachments'] = array_values(array_merge($existingAttachments, $attachments));
                }

                if ($existing) {
                    $existing->update($payload);
                } else {
                    Attendance::create($payload);
                }
            } else {
                Attendance::where('client_id', $clientId)
                    ->whereDate('service_date', $date)
                    ->where('session_type', $sessionType)
                    ->get()
                    ->each->delete();
            }
        }

        return redirect()->route('attendances.batch', [
            'date' => $date,
            'session_type' => $sessionType
        ])->with('success', 'Batch attendance updated.');
    }

    public function downloadAttachment(Attendance $attendance, string $filename)
    {
        $attachments = $attendance->attachments ?? [];
        if (! in_array($filename, $attachments, true)) {
            abort(404, 'File not found.');
        }

        $path = 'attachments/attendance/' . $filename;
        if (! Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path, $filename);
    }

    protected function storeAttachments(Request $request): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $stored = [];
        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $timestamp = now()->timestamp;
            $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $file->storeAs('attachments/attendance', $uniqueFileName);
            $stored[] = $uniqueFileName;
        }

        return $stored;
    }



}
