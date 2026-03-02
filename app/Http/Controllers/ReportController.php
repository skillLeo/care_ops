<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\House;
use App\Models\Apartment;
use App\Models\User;
use App\Models\ClientLevelOfCares;
use App\Models\PeerGroup;
use App\Models\LevelOfCare;
use App\Models\ClientHospitalization;
use App\Models\ServiceCode;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Services\AuditLogger;

class ReportController extends Controller
{
    private function logReport(string $actionType, string $reportName, array $metadata = []): void
    {
        $actorName = request()->user()?->name ?? 'System';
        $verb = $actionType === 'report_downloaded' ? 'downloaded' : 'generated';

        AuditLogger::log(
            $actionType,
            sprintf('%s %s report: %s.', $actorName, $verb, $reportName),
            null,
            $metadata
        );
    }

    public function index()
    {
        return view('reports.index');
    }

    public function attendanceBulkExportForm()
    {
        $clientGroups = ClientGroup::orderBy('name')->get();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        return view('reports.attendance_export', compact('clientGroups', 'levels'));
    }

    public function attendanceBulkExport(Request $request)
    {
        if ($request->input('client_group_id') === 'all') {
            $request->merge(['client_group_id' => null]);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'session_type' => ['required', 'in:group,peer_individual,peer_group'],
            'client_group_id' => ['nullable', 'exists:client_groups,id'],
            'levels' => ['required', 'array'],
            'levels.*' => [Rule::exists('level_of_cares', 'level_of_care')],
            'present_only' => ['nullable', 'boolean'],
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $sessionType = $validated['session_type'];
        $clientGroupId = $validated['client_group_id'] ?? null;
        $levels = collect($validated['levels'])->filter()->values();
        $presentOnly = (bool) ($validated['present_only'] ?? false);

        if ($levels->isEmpty()) {
            return back()->withInput()->withErrors(['levels' => 'Select at least one level of care.']);
        }

        $clientGroup = $clientGroupId ? ClientGroup::find($clientGroupId) : null;

        $period = CarbonPeriod::create($startDate, $endDate);

        $uuid = Str::uuid()->toString();
        $relativeDir = 'attendance_exports/' . $uuid;
        Storage::makeDirectory($relativeDir);

        $generatedFiles = [];

        foreach ($period as $date) {
            foreach ($levels as $level) {
                $records = $this->gatherAttendanceForDate($date, $sessionType, $clientGroupId, $level, $presentOnly);

                $hasPresentClients = $records->contains(function (array $row) {
                    $attendance = $row['attendance'] ?? null;

                    return $attendance && $attendance->attended;
                });

                if (! $hasPresentClients) {
                    continue;
                }

                $pdf = Pdf::loadView('reports.attendance_export_pdf', [
                    'records' => $records,
                    'date' => $date,
                    'level' => $level,
                    'sessionType' => $sessionType,
                    'presentOnly' => $presentOnly,
                    'clientGroup' => $clientGroup,
                ]);

                $fileName = sprintf('%s-%s-%s.pdf', $date->format('Y-m-d'), Str::slug($level), $sessionType);
                $relativePath = $relativeDir . '/' . $fileName;
                Storage::put($relativePath, $pdf->output());
                $generatedFiles[] = Storage::path($relativePath);
            }
        }

        if (empty($generatedFiles)) {
            Storage::deleteDirectory($relativeDir);

            return back()->withInput()->with('error', 'No attendance records were found for the selected filters.');
        }

        $zipRelativePath = 'attendance_exports/attendance-' . $uuid . '.zip';
        $zipPath = Storage::path($zipRelativePath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Storage::deleteDirectory($relativeDir);

            return back()->withInput()->with('error', 'Failed to create export archive.');
        }

        foreach ($generatedFiles as $filePath) {
            $zip->addFile($filePath, basename($filePath));
        }

        $zip->close();

        foreach (Storage::files($relativeDir) as $file) {
            Storage::delete($file);
        }
        Storage::deleteDirectory($relativeDir);

        $this->logReport('report_downloaded', 'Attendance Bulk Export', [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'session_type' => $validated['session_type'],
            'client_group_id' => $clientGroupId,
            'levels' => $levels->all(),
            'present_only' => $presentOnly,
        ]);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function attendance(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $sessionType = $request->input('session_type', 'group');
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');
        $presentOnly = $request->boolean('present_only');

        $counselors = User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $houses = House::all();
        $apartments = $houseId !== 'all' ? Apartment::where('house_id', $houseId)->get() : collect();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $clients = collect();
        $attendanceData = [];

        $query = Client::with(['apartment.house', 'counselor', 'peer'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
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
        if ($houseId !== 'all') {
            $query->whereHas('apartment', function ($q) use ($houseId) {
                $q->where('house_id', $houseId);
            });
        }
        if ($apartmentId !== 'all') {
            $query->whereHas('apartmentHistory', function ($q) use ($apartmentId, $selectedDate) { $q->where('apartment_id', $apartmentId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->orderBy('last_name')->get();

        foreach ($clients as $client) {
            $client->level_of_care = $client->levelOfCareHistory()
                ->with('levelOfCare')
                ->where('start_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                })
                ->get()
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

        if ($presentOnly) {
            $clients = $clients->filter(function ($client) use ($attendanceData) {
                return isset($attendanceData[$client->id]);
            })->values();
        }

        $this->logReport('report_generated', 'Attendance', [
            'date' => $selectedDate,
            'session_type' => $sessionType,
            'counselor_id' => $counselorId,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
            'present_only' => $presentOnly,
        ]);

        return view('reports.attendance', compact(
            'clients',
            'attendanceData',
            'selectedDate',
            'sessionType',
            'counselorId',
            'peerId',
            'houseId',
            'apartmentId',
            'levelOfCareFilter',
            'presentOnly',
            'counselors',
            'peers',
            'houses',
            'apartments',
            'levels'
        ));
    }

    public function attendanceTsv(Request $request)
    {
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'level_of_care' => ['nullable', Rule::exists('level_of_cares', 'level_of_care')],
            'house_type' => ['nullable', 'in:grove,non-grove,housed,non-housed,all'],
            'present_only' => ['nullable', 'boolean'],
        ]);

        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $levelOfCare = $validated['level_of_care'] ?? null;
        $houseType = $validated['house_type'] ?? 'all';
        $presentOnly = (bool) ($validated['present_only'] ?? false);

        $groupedRows = collect();
        $tableRows = collect();

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $groupedRows = $this->getAttendanceTsvRows($start, $end, $levelOfCare, $houseType, $presentOnly);

            $tableRows = $groupedRows
                ->flatten(1)
                ->unique('mrn')
                ->sortBy('name')
                ->values();
        }

        $this->logReport('report_generated', 'Attendance TSV', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'level_of_care' => $levelOfCare,
            'house_type' => $houseType,
            'present_only' => $presentOnly,
        ]);

        return view('reports.attendance_tsv', compact(
            'levels',
            'startDate',
            'endDate',
            'levelOfCare',
            'houseType',
            'presentOnly',
            'tableRows',
            'groupedRows'
        ));
    }

    public function attendanceTsvDownload(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'level_of_care' => ['nullable', Rule::exists('level_of_cares', 'level_of_care')],
            'house_type' => ['required', 'in:grove,non-grove,housed,non-housed,all'],
            'present_only' => ['nullable', 'boolean'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $levelOfCare = $validated['level_of_care'] ?? null;
        $houseType = $validated['house_type'];
        $presentOnly = (bool) ($validated['present_only'] ?? false);

        $groupedRows = $this->getAttendanceTsvRows($start, $end, $levelOfCare, $houseType, $presentOnly);

        if ($groupedRows->isEmpty()) {
            return back()->withInput()->with('error', 'No attendance records were found for the selected filters.');
        }

        $uuid = Str::uuid()->toString();
        $relativeDir = 'attendance_tsv';
        Storage::makeDirectory($relativeDir);
        $zipRelativePath = $relativeDir . '/attendance-tsv-' . $uuid . '.zip';
        $zipPath = Storage::path($zipRelativePath);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withInput()->with('error', 'Failed to create TSV archive.');
        }

        foreach ($groupedRows as $date => $rows) {
            $lines = collect();

            foreach ($rows as $row) {
                $lines->push($row['name'] . "\t" . $row['mrn']);
            }

            $formattedDate = Carbon::parse($date)->format('m-d-Y');

            $zip->addFromString($formattedDate . '.tsv', $lines->implode("\n"));
        }

        $zip->close();

        $this->logReport('report_downloaded', 'Attendance TSV', [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'level_of_care' => $levelOfCare,
            'house_type' => $houseType,
            'present_only' => $presentOnly,
        ]);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    protected function gatherAttendanceForDate(Carbon $date, string $sessionType, ?int $clientGroupId, string $level, bool $presentOnly)
    {
        $clientsQuery = Client::with(['counselor', 'peer', 'apartment.house', 'levelOfCareHistory'])
            ->whereDate('starting_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $date->toDateString());
            });

        if ($clientGroupId) {
            $clientsQuery->whereHas('clientGroupHistory', function ($query) use ($date, $clientGroupId) {
                $query->where('client_group_id', $clientGroupId)
                    ->whereDate('start_date', '<=', $date->toDateString())
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $date->toDateString());
                    });
            });
        }

        $clients = $clientsQuery->get();

        $attendanceRecords = Attendance::whereDate('service_date', $date->toDateString())
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy('client_id');

        return $clients->map(function (Client $client) use ($attendanceRecords, $date, $level, $presentOnly) {
            $clientLevel = $client->getLevelOfCareOnDateWithoutHospitalization($date->toDateString());

            if ($clientLevel !== $level) {
                return null;
            }

            $attendance = $attendanceRecords->get($client->id);

            if ($presentOnly && (! $attendance || ! $attendance->attended)) {
                return null;
            }

            return [
                'client' => $client,
                'level' => $clientLevel,
                'attendance' => $attendance,
            ];
        })->filter()->sortBy(function (array $item) {
            $client = $item['client'];

            return strtoupper($client->last_name . ' ' . $client->first_name);
        })->values();
    }

    public function clientsByHouse(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');

        $counselors = User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $houses = House::all();
        $apartments = ($houseId !== 'all' && $houseId !== 'unhoused') ? Apartment::where('house_id', $houseId)->get() : collect();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $query = Client::with(['apartment.house', 'counselor', 'peer', 'levelOfCareHistory.levelOfCare'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                $q->whereDate('start_date', '<', $selectedDate)
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

        $clients = $query->get();

        $clients = $clients->sortBy(function ($client) {
            $house = optional(optional($client->apartment)->house)->house_name ?? 'ZZZ';
            $apartment = $client->apartment->apartment_number ?? 'ZZZ';
            return $house . '|' . $apartment . '|' . $client->last_name;
        })->values();

        foreach ($clients as $client) {
            $client->level_of_care_history = $client->levelOfCareHistory->map(function ($level) {
                $label = $level->levelOfCare?->display_name ?? '-';

                return $label . ' (' . Carbon::parse($level->start_date)->format('m/d/Y') . ' - ' . ($level->end_date ? Carbon::parse($level->end_date)->format('m/d/Y') : 'Ongoing') . ')';
            })->implode('<br>');
            $client->current_level_of_care = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
        }

        if ($levelOfCareFilter !== 'all') {
            $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                return $client->current_level_of_care === $levelOfCareFilter;
            })->values();
        }

        $this->logReport('report_generated', 'Clients by House', [
            'date' => $selectedDate,
            'counselor_id' => $counselorId,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
        ]);

        return view('reports.clients_by_house', compact(
            'clients',
            'selectedDate',
            'counselorId',
            'peerId',
            'houseId',
            'apartmentId',
            'levelOfCareFilter',
            'counselors',
            'peers',
            'houses',
            'apartments',
            'levels'
        ));
    }

    public function clientsByCounselor(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');

        $counselors = User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $houses = House::all();
        $apartments = $houseId !== 'all' ? Apartment::where('house_id', $houseId)->get() : collect();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $query = Client::with(['apartment.house', 'counselor', 'peer', 'levelOfCareHistory.levelOfCare'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                $q->whereDate('start_date', '<', $selectedDate)
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
        if ($houseId !== 'all') {
            $query->whereHas('apartment', function ($q) use ($houseId) {
                $q->where('house_id', $houseId);
            });
        }
        if ($apartmentId !== 'all') {
            $query->whereHas('apartmentHistory', function ($q) use ($apartmentId, $selectedDate) { $q->where('apartment_id', $apartmentId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->get();

        $clients = $clients->sortBy(function ($client) {
            $counselor = optional($client->counselor)->name ?? 'ZZZ';
            return $counselor . '|' . $client->last_name;
        })->values();

        foreach ($clients as $client) {
            $client->level_of_care_history = $client->levelOfCareHistory->map(function ($level) {
                $label = $level->levelOfCare?->display_name ?? '-';

                return $label . ' (' . Carbon::parse($level->start_date)->format('m/d/Y') . ' - ' . ($level->end_date ? Carbon::parse($level->end_date)->format('m/d/Y') : 'Ongoing') . ')';
            })->implode('<br>');
            $client->current_level_of_care = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
        }

        if ($levelOfCareFilter !== 'all') {
            $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                return $client->current_level_of_care === $levelOfCareFilter;
            })->values();
        }

        $this->logReport('report_generated', 'Clients by Counselor', [
            'date' => $selectedDate,
            'counselor_id' => $counselorId,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
        ]);

        return view('reports.clients_by_counselor', compact(
            'clients',
            'selectedDate',
            'counselorId',
            'peerId',
            'houseId',
            'apartmentId',
            'levelOfCareFilter',
            'counselors',
            'peers',
            'houses',
            'apartments',
            'levels'
        ));
    }

    public function clientsByPeer(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');

        $peers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['peer', 'Peer_Support']);
        })->get();
        $houses = House::all();
        $apartments = $houseId !== 'all' ? Apartment::where('house_id', $houseId)->get() : collect();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $query = Client::with(['apartment.house', 'peer', 'counselor', 'levelOfCareHistory.levelOfCare'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                $q->whereDate('start_date', '<', $selectedDate)
                    ->where(function ($query) use ($selectedDate) {
                        $query->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate);
                    });
            });

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }
        if ($houseId !== 'all') {
            $query->whereHas('apartment', function ($q) use ($houseId) {
                $q->where('house_id', $houseId);
            });
        }
        if ($apartmentId !== 'all') {
            $query->whereHas('apartmentHistory', function ($q) use ($apartmentId, $selectedDate) { $q->where('apartment_id', $apartmentId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->get();

        $clients = $clients->sortBy(function ($client) {
            $peer = optional($client->peer)->name ?? 'ZZZ';
            return $peer . '|' . $client->last_name;
        })->values();

        foreach ($clients as $client) {
            $client->level_of_care_history = $client->levelOfCareHistory->map(function ($level) {
                $label = $level->levelOfCare?->display_name ?? '-';

                return $label . ' (' . Carbon::parse($level->start_date)->format('m/d/Y') . ' - ' . ($level->end_date ? Carbon::parse($level->end_date)->format('m/d/Y') : 'Ongoing') . ')';
            })->implode('<br>');
            $client->current_level_of_care = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
        }

        if ($levelOfCareFilter !== 'all') {
            $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                return $client->current_level_of_care === $levelOfCareFilter;
            })->values();
        }

        $this->logReport('report_generated', 'Clients by Peer', [
            'date' => $selectedDate,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
        ]);

        return view('reports.clients_by_peer', compact(
            'clients',
            'selectedDate',
            'peerId',
            'houseId',
            'apartmentId',
            'levelOfCareFilter',
            'peers',
            'houses',
            'apartments',
            'levels'
        ));
    }

    public function clientsByGroup(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $groupId = $request->input('group_id', 'all');
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');

        $groups = ClientGroup::orderBy('name')->get();
        $counselors = User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $query = Client::with(['counselor', 'peer', 'levelOfCareHistory.levelOfCare', 'levelOfCareHistory'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                $q->whereDate('start_date', '<', $selectedDate)
                    ->where(function ($query) use ($selectedDate) {
                        $query->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate);
                    });
            });

        if ($groupId !== 'all') {
            if ($groupId === 'ungrouped') {
                $query->whereDoesntHave('clientGroupHistory', function ($query) use ($selectedDate) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($dates) use ($selectedDate) {
                            $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate);
                        });
                });
            } else {
                $query->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $groupId) {
                    $query->where('client_group_id', $groupId)
                        ->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        });
                });
            }
        }

        if ($counselorId !== 'all') {
            $query->whereHas('counselorHistory', function ($q) use ($counselorId, $selectedDate) { $q->where('counselor_id', $counselorId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->get();

        $clients = $clients->sortBy(function ($client) use ($selectedDate) {
            $groupName = $client->getClientGroupOnDate($selectedDate)?->name ?? 'ZZZ';
            return $groupName . '|' . $client->last_name;
        })->values();

        foreach ($clients as $client) {
            $client->current_level_of_care = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
        }

        if ($levelOfCareFilter !== 'all') {
            $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                return $client->current_level_of_care === $levelOfCareFilter;
            })->values();
        }

        $this->logReport('report_generated', 'Clients by Group', [
            'date' => $selectedDate,
            'counselor_id' => $counselorId,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
        ]);

        return view('reports.clients_by_group', compact(
            'clients',
            'selectedDate',
            'groupId',
            'counselorId',
            'peerId',
            'levelOfCareFilter',
            'groups',
            'counselors',
            'peers',
            'levels'
        ));
    }

    public function clientsByPeerGroup(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $peerGroupId = $request->input('peer_group_id', 'all');
        $counselorId = $request->input('counselor_id', 'all');
        $peerId = $request->input('peer_id', 'all');
        $houseId = $request->input('house_id', 'all');
        $apartmentId = $request->input('apartment_id', 'all');
        $levelOfCareFilter = $request->input('level_of_care', 'all');

        $peerGroups = PeerGroup::orderBy('name')->get();
        $counselors = User::whereHas('roles', function ($q) {
            $q->where('name', 'counselor');
        })->get();
        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        $query = Client::with(['peerGroup', 'counselor', 'peer', 'levelOfCareHistory'])
            ->whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->whereDoesntHave('hospitalizations', function ($q) use ($selectedDate) {
                $q->whereDate('start_date', '<', $selectedDate)
                    ->where(function ($query) use ($selectedDate) {
                        $query->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate);
                    });
            });

        if ($peerGroupId !== 'all') {
            if ($peerGroupId === 'ungrouped') {
                $query->whereNull('peer_group_id');
            } else {
                $query->where('peer_group_id', $peerGroupId);
            }
        }

        if ($counselorId !== 'all') {
            $query->whereHas('counselorHistory', function ($q) use ($counselorId, $selectedDate) { $q->where('counselor_id', $counselorId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->get();

        $clients = $clients->sortBy(function ($client) {
            $groupName = optional($client->peerGroup)->name ?? 'ZZZ';
            return $groupName . '|' . $client->last_name;
        })->values();

        foreach ($clients as $client) {
            $client->current_level_of_care = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
        }

        if ($levelOfCareFilter !== 'all') {
            $clients = $clients->filter(function ($client) use ($levelOfCareFilter) {
                return $client->current_level_of_care === $levelOfCareFilter;
            })->values();
        }

        $this->logReport('report_generated', 'Clients by Peer Group', [
            'date' => $selectedDate,
            'counselor_id' => $counselorId,
            'peer_id' => $peerId,
            'house_id' => $houseId,
            'apartment_id' => $apartmentId,
            'level_of_care' => $levelOfCareFilter,
        ]);

        return view('reports.clients_by_peer_group', compact(
            'clients',
            'selectedDate',
            'peerGroupId',
            'counselorId',
            'peerId',
            'levelOfCareFilter',
            'peerGroups',
            'counselors',
            'peers',
            'levels'
        ));
    }

    public function houseReport(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());

        $houses = House::with(['apartments.clients' => function ($q) use ($selectedDate) {
            $q->whereDate('starting_date', '<=', $selectedDate)
                ->where(function ($q2) use ($selectedDate) {
                    $q2->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
                });
        }])->get();

        $reportData = $houses->map(function ($house) use ($selectedDate) {
            $totalCapacity = $house->apartments->sum('capacity');
            $totalOccupied = 0;

            $maleCapacity = $femaleCapacity = $coupleCapacity = 0;
            $maleOccupied = $femaleOccupied = $couplePeople = 0;

            $phpCount = $iopCount = $nonLevelCount = 0;

            foreach ($house->apartments as $apartment) {
                $clients = $apartment->clients;
                $occupied = $clients->count();
                $totalOccupied += $occupied;

                switch ($apartment->type) {
                    case 'single_male':
                        $maleCapacity += $apartment->capacity;
                        $maleOccupied += $occupied;
                        break;
                    case 'single_female':
                        $femaleCapacity += $apartment->capacity;
                        $femaleOccupied += $occupied;
                        break;
                    case 'couples':
                        $coupleCapacity += $apartment->capacity;
                        $couplePeople += $occupied;
                        break;
                    default:
                        $maleCapacity += $apartment->capacity;
                        $maleOccupied += $occupied;
                }

                foreach ($clients as $client) {
                    $level = $client->getLevelOfCareOnDateWithoutHospitalization($selectedDate);
                    if ($level === 'PHP') {
                        $phpCount++;
                    } elseif ($level === 'IOP') {
                        $iopCount++;
                    } else {
                        $nonLevelCount++;
                    }
                }
            }

            $vacantMale = $maleCapacity - $maleOccupied;
            $vacantFemale = $femaleCapacity - $femaleOccupied;
            $vacantCouple = $coupleCapacity - $couplePeople;

            return [
                'house_name' => $house->house_name,
                'total_capacity' => $totalCapacity,
                'capacity_male' => $maleCapacity,
                'capacity_female' => $femaleCapacity,
                'capacity_couple' => $coupleCapacity,
                'total_occupied' => $totalOccupied,
                'occupied_male' => $maleOccupied,
                'occupied_female' => $femaleOccupied,
                'occupied_couple' => $couplePeople,
                'vacant_total' => $totalCapacity - $totalOccupied,
                'vacant_male' => $vacantMale,
                'vacant_female' => $vacantFemale,
                'vacant_couple' => $vacantCouple,
                'php_count' => $phpCount,
                'iop_count' => $iopCount,
                'non_level_count' => $nonLevelCount,
            ];
        });

        $this->logReport('report_generated', 'House Report', [
            'date' => $selectedDate,
        ]);

        return view('reports.house', compact('reportData', 'selectedDate'));
    }

    public function clientList()
    {
        $clients = Client::orderBy('last_name')->get();

        $this->logReport('report_generated', 'Client List');

        return view('reports.client_list', compact('clients'));
    }

    public function totalClientList(Request $request)
    {
        $groupOptions = LevelOfCare::orderBy('level_of_care')->get();

        $validated = $request->validate([
            'group' => ['nullable', Rule::exists('level_of_cares', 'level_of_care')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'house_type' => ['nullable', 'in:grove,non-grove'],
        ]);

        $selectedGroup = $validated['group'] ?? null;
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $houseType = $validated['house_type'] ?? null;

        $clients = collect();

        if ($selectedGroup && $from && $to) {
            $startDate = Carbon::parse($from)->startOfDay();
            $endDate = Carbon::parse($to)->endOfDay();

            $clientGroupId = optional(ClientGroup::where('name', $selectedGroup)->first())->id;

            $clientsById = collect();

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $records = $this->gatherAttendanceForDate($date, 'group', $clientGroupId, $selectedGroup, true);

                foreach ($records as $record) {
                    $client = $record['client'];

                    if ($houseType) {
                        $houseName = optional(optional($client->apartment)->house)->house_name;
                        $isGroveHouse = $houseName ? Str::contains(Str::lower($houseName), 'grove') : false;

                        if ($houseType === 'grove' && ! $isGroveHouse) {
                            continue;
                        }

                        if ($houseType === 'non-grove' && $isGroveHouse) {
                            continue;
                        }
                    }

                    $clientsById->put($client->id, [
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                    ]);
                }
            }

            $clients = $clientsById
                ->map(fn (array $client) => (object) $client)
                ->sortBy(fn ($client) => strtoupper($client->last_name . ' ' . $client->first_name))
                ->values();
        }

        $this->logReport('report_generated', 'Total Client List', [
            'group' => $selectedGroup,
            'from' => $from,
            'to' => $to,
            'house_type' => $houseType,
        ]);

        return view('reports.total_client_list', compact('groupOptions', 'clients', 'selectedGroup', 'from', 'to', 'houseType'));
    }

    public function intakes(Request $request)
    {
        $from = $request->input('from', Carbon::now()->subDays(7)->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());
        $peerId = $request->input('peer_id', 'all');
        $includeDischarged = $request->boolean('include_discharged');

        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();

        $query = Client::with(['counselor', 'peer'])
            ->whereDate('starting_date', '>=', $from)
            ->whereDate('starting_date', '<=', $to);

        if (! $includeDischarged) {
            $query->whereNull('discharge_date');
        }

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->orderBy('starting_date')->get();

        foreach ($clients as $client) {
            $client->current_level_of_care = $client->getCurrentLevelOfCare();
        }

        $this->logReport('report_generated', 'Intakes', [
            'from' => $from,
            'to' => $to,
            'peer_id' => $peerId,
            'include_discharged' => $includeDischarged,
        ]);

        return view('reports.intakes', compact('clients', 'from', 'to', 'peers', 'peerId', 'includeDischarged'));
    }

    public function reactivations(Request $request)
    {
        $from = $request->input('from', Carbon::now()->subDays(7)->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());
        $peerId = $request->input('peer_id', 'all');

        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();

        $query = Client::with(['counselor', 'peer'])
            ->whereDate('reactivation_date', '>=', $from)
            ->whereDate('reactivation_date', '<=', $to);

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->orderBy('reactivation_date')->get();

        foreach ($clients as $client) {
            $client->current_level_of_care = $client->getCurrentLevelOfCare();
        }

        $this->logReport('report_generated', 'Reactivations', [
            'from' => $from,
            'to' => $to,
            'peer_id' => $peerId,
        ]);

        return view('reports.reactivations', compact('clients', 'from', 'to', 'peers', 'peerId'));
    }

    public function discharges(Request $request)
    {
        $from = $request->input('from', Carbon::now()->subDays(7)->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());
        $peerId = $request->input('peer_id', 'all');

        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();

        $query = Client::with(['counselor', 'peer'])
            ->whereNotNull('discharge_date')
            ->whereDate('discharge_date', '>=', $from)
            ->whereDate('discharge_date', '<=', $to);

        if ($peerId !== 'all') {
            $query->whereHas('peerHistory', function ($q) use ($peerId, $selectedDate) { $q->where('peer_id', $peerId)->whereDate('start_date', '<=', $selectedDate)->where(function ($z) use ($selectedDate) { $z->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate); }); });
        }

        $clients = $query->orderBy('discharge_date')->get();

        foreach ($clients as $client) {
            $client->current_level_of_care = $client->getCurrentLevelOfCare();
        }

        $this->logReport('report_generated', 'Discharges', [
            'from' => $from,
            'to' => $to,
            'peer_id' => $peerId,
        ]);

        return view('reports.discharges', compact('clients', 'from', 'to', 'peers', 'peerId'));
    }

    public function hospitalizations(Request $request)
    {
        $from = $request->input('from', Carbon::now()->subDays(7)->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $hospitalizations = ClientHospitalization::with(['client.counselor', 'client.peer', 'facility'])
            ->whereDate('start_date', '>=', $from)
            ->whereDate('start_date', '<=', $to)
            ->orderBy('start_date')
            ->get();

        $this->logReport('report_generated', 'Hospitalizations', [
            'from' => $from,
            'to' => $to,
        ]);

        return view('reports.hospitalizations', compact('hospitalizations', 'from', 'to'));
    }

    public function transitions(Request $request)
    {
        $from = $request->input('from', Carbon::now()->subDays(7)->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());
        $peerId = $request->input('peer_id', 'all');

        $peers = User::whereHas('roles', function ($q) {
            $q->where('name', 'peer');
        })->get();

        $query = ClientLevelOfCares::with(['client.counselor', 'client.peer', 'levelOfCare'])
            ->whereBetween('start_date', [$from, $to])
            ->orderBy('start_date');

        if ($peerId !== 'all') {
            $query->whereHas('client', function ($q) use ($peerId) {
                $q->where('peer_id', $peerId);
            });
        }

        $transitions = $query->get()
            ->filter(function ($transition) {
                $previous = $transition->client->levelOfCareHistory()
                    ->where('start_date', '<', $transition->start_date)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($previous) {
                    $transition->previous_level = $previous->levelOfCare?->display_name ?? '-';
                    return true;
                }

                return false; // exclude intakes with no previous level
            })->values();

        $this->logReport('report_generated', 'Transitions', [
            'from' => $from,
            'to' => $to,
            'peer_id' => $peerId,
        ]);

        return view('reports.transitions', compact('transitions', 'from', 'to', 'peers', 'peerId'));
    }

    public function medicaidByDate(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());

        $clients = Client::whereDate('starting_date', '<=', $selectedDate)
            ->where(function ($q) use ($selectedDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $selectedDate);
            })
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'date_of_birth', 'medicaid_id']);

        $this->logReport('report_generated', 'Medicaid by Date', [
            'date' => $selectedDate,
        ]);

        return view('reports.medicaid_by_date', compact('clients', 'selectedDate'));
    }

    public function groupAttendanceSummary(Request $request)
    {
        $filters = $request->validate([
            'service_date_start' => ['nullable', 'date'],
            'service_date_end' => ['nullable', 'date', 'after_or_equal:service_date_start'],
            'service_code_id' => ['nullable', Rule::exists('service_codes', 'id')],
            'client_group_id' => ['nullable', Rule::exists('client_groups', 'id')],
            'level_of_care_id' => ['nullable', Rule::exists('level_of_cares', 'id')],
            'submitted_by' => ['nullable', Rule::exists('users', 'id')],
        ]);

        $rows = $this->buildAttendanceSummaryRows('group_therapy', 'client_group_id', $filters);

        $serviceCodes = ServiceCode::orderBy('friendly_name')->get(['id', 'service_code', 'friendly_name']);
        $groups = ClientGroup::orderBy('name')->get(['id', 'name']);
        $levels = LevelOfCare::orderBy('level_of_care')->get(['id', 'level_of_care', 'display_name']);
        $submitters = User::orderBy('name')->get(['id', 'name']);

        $this->logReport('report_generated', 'Group Attendance Summary', $filters);

        return view('reports.group_attendance_summary', compact('rows', 'serviceCodes', 'groups', 'levels', 'submitters', 'filters'));
    }

    public function peerGroupAttendanceSummary(Request $request)
    {
        $filters = $request->validate([
            'service_date_start' => ['nullable', 'date'],
            'service_date_end' => ['nullable', 'date', 'after_or_equal:service_date_start'],
            'service_code_id' => ['nullable', Rule::exists('service_codes', 'id')],
            'peer_group_id' => ['nullable', Rule::exists('peer_groups', 'id')],
            'level_of_care_id' => ['nullable', Rule::exists('level_of_cares', 'id')],
            'submitted_by' => ['nullable', Rule::exists('users', 'id')],
        ]);

        $rows = $this->buildAttendanceSummaryRows('cprs_group', 'peer_group_id', $filters);

        $serviceCodes = ServiceCode::orderBy('friendly_name')->get(['id', 'service_code', 'friendly_name']);
        $peerGroups = PeerGroup::orderBy('name')->get(['id', 'name']);
        $levels = LevelOfCare::orderBy('level_of_care')->get(['id', 'level_of_care', 'display_name']);
        $submitters = User::orderBy('name')->get(['id', 'name']);

        $this->logReport('report_generated', 'Peer Group Attendance Summary', $filters);

        return view('reports.peer_group_attendance_summary', compact('rows', 'serviceCodes', 'peerGroups', 'levels', 'submitters', 'filters'));
    }

    private function buildAttendanceSummaryRows(string $submissionType, string $groupColumn, array $filters)
    {
        $query = AttendanceSubmission::with(['serviceCode:id,friendly_name,service_code', 'levelOfCare:id,display_name,level_of_care', 'clientGroup:id,name', 'peerGroup:id,name'])
            ->withSum('attendanceRows as total_units', 'units')
            ->where('type', $submissionType)
            ->orderByDesc('service_date')
            ->orderByDesc('id');

        if (! empty($filters['service_date_start'])) {
            $query->whereDate('service_date', '>=', $filters['service_date_start']);
        }

        if (! empty($filters['service_date_end'])) {
            $query->whereDate('service_date', '<=', $filters['service_date_end']);
        }

        foreach (['service_code_id', $groupColumn, 'level_of_care_id', 'submitted_by'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        return $query->get()->map(function (AttendanceSubmission $submission) use ($groupColumn, $submissionType) {
            $serviceDate = optional($submission->service_date)->toDateString();

            if ($groupColumn === 'client_group_id') {
                $groupName = optional($submission->clientGroup)->name ?? 'All';
                $totalPeople = $this->countClientsInGroupForDate((int) $submission->client_group_id, $serviceDate, $submission->level_of_care_id);
            } else {
                $groupName = optional($submission->peerGroup)->name ?? 'All';
                $totalPeople = $this->countClientsInPeerGroupForDate((int) $submission->peer_group_id, $serviceDate);
            }

            if (! $submission->{$groupColumn}) {
                $totalPeople = $submissionType === 'group_therapy'
                    ? $this->countAllClientsForDate($serviceDate, $submission->level_of_care_id)
                    : $this->countAllClientsForDate($serviceDate, null);
            }

            return [
                'service_date' => $submission->service_date,
                'level_of_care' => $submission->levelOfCare?->display_name ?? $submission->levelOfCare?->level_of_care ?? '-',
                'group' => $groupName,
                'attendance' => (int) $submission->total_attendance,
                'total_people' => $totalPeople,
                'total_units' => (int) ($submission->total_units ?? 0),
            ];
        });
    }

    private function countClientsInGroupForDate(?int $groupId, ?string $serviceDate, ?int $levelOfCareId): int
    {
        if (! $groupId || ! $serviceDate) {
            return 0;
        }

        $query = Client::query()
            ->whereDate('starting_date', '<=', $serviceDate)
            ->where(function ($q) use ($serviceDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $serviceDate);
            })
            ->whereHas('clientGroupHistory', function ($q) use ($groupId, $serviceDate) {
                $q->where('client_group_id', $groupId)
                    ->whereDate('start_date', '<=', $serviceDate)
                    ->where(function ($levelDates) use ($serviceDate) {
                        $levelDates->whereNull('end_date')->orWhereDate('end_date', '>=', $serviceDate);
                    });
            });

        if ($levelOfCareId) {
            $query->whereHas('levelOfCareHistory', function ($q) use ($serviceDate, $levelOfCareId) {
                $q->where('level_of_care', $levelOfCareId)
                    ->whereDate('start_date', '<=', $serviceDate)
                    ->where(function ($levelDates) use ($serviceDate) {
                        $levelDates->whereNull('end_date')->orWhereDate('end_date', '>=', $serviceDate);
                    });
            });
        }

        return $query->count();
    }

    private function countClientsInPeerGroupForDate(?int $peerGroupId, ?string $serviceDate): int
    {
        if (! $peerGroupId || ! $serviceDate) {
            return 0;
        }

        return Client::whereDate('starting_date', '<=', $serviceDate)
            ->where(function ($q) use ($serviceDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $serviceDate);
            })
            ->whereHas('peerGroupHistory', function ($q) use ($peerGroupId, $serviceDate) {
                $q->where('peer_group_id', $peerGroupId)
                    ->whereDate('start_date', '<=', $serviceDate)
                    ->where(function ($dates) use ($serviceDate) {
                        $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $serviceDate);
                    });
            })
            ->count();
    }

    private function countAllClientsForDate(?string $serviceDate, ?int $levelOfCareId): int
    {
        if (! $serviceDate) {
            return 0;
        }

        $query = Client::whereDate('starting_date', '<=', $serviceDate)
            ->where(function ($q) use ($serviceDate) {
                $q->whereNull('discharge_date')->orWhereDate('discharge_date', '>=', $serviceDate);
            });

        if ($levelOfCareId) {
            $query->whereHas('levelOfCareHistory', function ($q) use ($serviceDate, $levelOfCareId) {
                $q->where('level_of_care', $levelOfCareId)
                    ->whereDate('start_date', '<=', $serviceDate)
                    ->where(function ($levelDates) use ($serviceDate) {
                        $levelDates->whereNull('end_date')->orWhereDate('end_date', '>=', $serviceDate);
                    });
            });
        }

        return $query->count();
    }

    protected function getAttendanceTsvRows(Carbon $startDate, Carbon $endDate, ?string $levelOfCare, string $houseType, bool $presentOnly)
    {
        $attendanceRecords = Attendance::with(['client.apartment.house', 'client.levelOfCareHistory'])
            ->whereBetween('service_date', [$startDate, $endDate])
            ->where('session_type', 'group')
            ->when($presentOnly, function ($query) {
                $query->where('attended', true)->where('units', '>', 0);
            })
            ->get();

        $filtered = $attendanceRecords->filter(function ($attendance) use ($levelOfCare, $houseType, $presentOnly) {
            $client = $attendance->client;

            if (! $client) {
                return false;
            }

            if ($presentOnly && (! $attendance->attended || $attendance->units <= 0)) {
                return false;
            }

            $level = $client->getLevelOfCareOnDateWithoutHospitalization($attendance->service_date->toDateString());

            if ($levelOfCare && $level !== $levelOfCare) {
                return false;
            }

            $houseName = optional(optional($client->apartment)->house)->house_name;
            $isGroveHouse = $houseName ? Str::contains(Str::lower($houseName), 'grove') : false;
            $isHoused = ! is_null($client->apartment);

            if ($houseType === 'grove' && ! $isGroveHouse) {
                return false;
            }

            if ($houseType === 'non-grove' && $isGroveHouse) {
                return false;
            }

            if ($houseType === 'housed' && ! $isHoused) {
                return false;
            }

            if ($houseType === 'non-housed' && $isHoused) {
                return false;
            }

            return true;
        });

        return $filtered
            ->groupBy(fn ($attendance) => $attendance->service_date->toDateString())
            ->sortKeys()
            ->map(function ($records, $date) {
                return $records
                    ->unique('client_id')
                    ->sortBy(function ($attendance) {
                        $client = $attendance->client;

                        return strtoupper($client->last_name . ' ' . $client->first_name);
                    })
                    ->values()
                    ->map(function ($attendance) use ($date) {
                        $client = $attendance->client;

                        return [
                            'date' => $date,
                            'name' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
                            'mrn' => $client->mrn,
                            'present' => (bool) $attendance->attended,
                            'units' => $attendance->units,
                        ];
                    });
            })
            ->filter(fn ($records) => $records->isNotEmpty());
    }
}
