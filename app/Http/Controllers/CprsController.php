<?php

namespace App\Http\Controllers;

use App\Models\Attendance2026;
use App\Models\AttendanceSubmission;
use App\Models\ClinicalNote;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\LevelOfCare;
use App\Models\PeerGroup;
use App\Models\ServiceCode;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CprsController extends Controller
{
    public function index()
    {
        $query = AttendanceSubmission::with(['submittedBy', 'serviceCode', 'levelOfCare', 'clientGroup', 'peerGroup'])
            ->whereIn('type', ['batch_outing', 'prod_sheet', 'cprs_group'])
            ->orderByDesc('created_at');

        if (request('service_date')) {
            $query->whereDate('service_date', request('service_date'));
        }

        if (request('submission_date')) {
            $query->whereDate('submission_date', request('submission_date'));
        }

        if (request('submitted_by')) {
            $query->where('submitted_by', request('submitted_by'));
        }

        if (request('attendance_type')) {
            $query->where('type', request('attendance_type'));
        }

        $submissions = $query->get();
        $submitters = User::orderBy('name')->get();

        return view('cprs.index', compact('submissions', 'submitters'));
    }

    public function batchOuting(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $serviceCode = ServiceCode::where('service_code', 'H0038')->firstOrFail();
        $timeStart = $request->input('time_start');
        $timeEnd = $request->input('time_end');
        $remarks = $request->input('remarks');

        $clients = Client::with(['apartment.house'])
            ->active()
            ->orderBy('last_name')
            ->get();

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

        $attendanceMeta = Attendance2026::whereDate('service_date', $selectedDate)
            ->where('service_code_id', $serviceCode->id)
            ->first();
        $attendanceMetaTimeStart = $timeStart ?? ($attendanceMeta?->time_start ? substr($attendanceMeta->time_start, 0, 5) : '');
        $attendanceMetaTimeEnd = $timeEnd ?? ($attendanceMeta?->time_end ? substr($attendanceMeta->time_end, 0, 5) : '');
        $attendanceMetaRemarks = $remarks ?? $attendanceMeta?->remarks;

        $clientOptions = $clients->map(fn ($client) => [
            'id' => $client->id,
            'label' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
        ])->values();

        $existingUnitsByClient = Attendance2026::whereDate('service_date', $selectedDate)
            ->where('service_code_id', $serviceCode->id)
            ->select('client_id', DB::raw('SUM(units) as units'))
            ->groupBy('client_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->client_id => (int) $row->units]);

        return view('cprs.batch_outing', compact(
            'attendanceMeta',
            'attendanceMetaTimeStart',
            'attendanceMetaTimeEnd',
            'attendanceMetaRemarks',
            'selectedDate',
            'serviceCode',
            'clientOptions',
            'existingUnitsByClient'
        ));
    }

    public function batchOutingStore(Request $request)
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'service_code_id' => ['required', 'exists:service_codes,id'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i'],
            'units' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.client_id' => ['required', 'exists:clients,id'],
            'rows.*.remarks' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:2048'],
            'stored_attachments' => ['array'],
            'stored_attachments.*' => ['string'],
        ]);

        $attachments = $this->storeAttachments($request);
        $rows = $validated['rows'];
        $clientIds = collect($rows)->pluck('client_id')->values();

        if ($clientIds->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select at least one client before submitting.');
        }

        if (! $request->boolean('confirm')) {
            $clients = Client::whereIn('id', $clientIds->unique())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->keyBy('id');
            $reviewRows = collect($rows)->map(function ($row) use ($clients) {
                return [
                    'client' => $clients->get($row['client_id']),
                    'remarks' => $row['remarks'] ?? null,
                ];
            });
            $serviceCode = ServiceCode::find($validated['service_code_id']);

            return view('cprs.batch_outing_review', [
                'rows' => $reviewRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'attachments' => $attachments,
            ]);
        }

        $submission = AttendanceSubmission::create([
            'type' => 'batch_outing',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $validated['service_code_id'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'total_attendance' => count($rows),
            'submitted_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        foreach ($rows as $row) {
            Attendance2026::create([
                'client_id' => $row['client_id'],
                'service_date' => $validated['service_date'],
                'service_code_id' => $validated['service_code_id'],
                'time_start' => $validated['time_start'],
                'time_end' => $validated['time_end'],
                'units' => $validated['units'],
                'remarks' => $row['remarks'] ?? null,
                'marked_by' => auth()->id(),
                'attendance_submission_id' => $submission->id,
            ]);

        }

        return redirect()
            ->route('cprs.index')
            ->with('success', 'CPRS peer outing updated.');
    }

    public function productivitySheet()
    {
        $selectedDate = request('date', now()->toDateString());
        $serviceCode = ServiceCode::where('service_code', 'H0038')->firstOrFail();

        $clients = Client::with(['apartment.house'])
            ->active()
            ->orderBy('last_name')
            ->get();

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

        $clientOptions = $clients->map(fn ($client) => [
            'id' => $client->id,
            'label' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
        ])->values();

        $existingUnitsByClient = Attendance2026::whereDate('service_date', $selectedDate)
            ->where('service_code_id', $serviceCode->id)
            ->select('client_id', DB::raw('SUM(units) as units'))
            ->groupBy('client_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->client_id => (int) $row->units]);

        return view('cprs.prod_sheet', compact(
            'clients',
            'selectedDate',
            'serviceCode',
            'clientOptions',
            'existingUnitsByClient'
        ));
    }

    public function productivitySheetStore(Request $request)
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'service_code_id' => ['required', 'exists:service_codes,id'],
            'remarks' => ['nullable', 'string'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.client_id' => ['required', 'exists:clients,id'],
            'rows.*.time_start' => ['required', 'date_format:H:i'],
            'rows.*.time_end' => ['required', 'date_format:H:i'],
            'rows.*.units' => ['required', 'integer', 'min:0'],
            'rows.*.remarks' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:2048'],
            'stored_attachments' => ['array'],
            'stored_attachments.*' => ['string'],
        ]);

        $attachments = $this->storeAttachments($request);
        $rows = $validated['rows'];

        if (! $request->boolean('confirm')) {
            $clientIds = collect($rows)->pluck('client_id')->unique()->values();
            $clients = Client::whereIn('id', $clientIds)->get()->keyBy('id');

            $reviewRows = collect($rows)->map(function ($row) use ($clients) {
                $client = $clients->get($row['client_id']);
                return [
                    'client' => $client,
                    'time_start' => $row['time_start'],
                    'time_end' => $row['time_end'],
                    'units' => $row['units'],
                    'remarks' => $row['remarks'] ?? null,
                ];
            });

            $serviceCode = ServiceCode::find($validated['service_code_id']);

            return view('cprs.prod_sheet_review', [
                'rows' => $reviewRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'attachments' => $attachments,
            ]);
        }

        $startTimes = collect($rows)->pluck('time_start')->filter();
        $endTimes = collect($rows)->pluck('time_end')->filter();

        $submission = AttendanceSubmission::create([
            'type' => 'prod_sheet',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $validated['service_code_id'],
            'time_start' => $startTimes->min(),
            'time_end' => $endTimes->max(),
            'total_attendance' => count($rows),
            'submitted_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        foreach ($rows as $row) {
            Attendance2026::create([
                'client_id' => $row['client_id'],
                'service_date' => $validated['service_date'],
                'service_code_id' => $validated['service_code_id'],
                'time_start' => $row['time_start'],
                'time_end' => $row['time_end'],
                'units' => $row['units'],
                'remarks' => $row['remarks'] ?? null,
                'marked_by' => auth()->id(),
                'attendance_submission_id' => $submission->id,
            ]);

        }

        return redirect()
            ->route('cprs.index')
            ->with('success', 'CPRS Individual submitted.');
    }

    public function groupTherapy(Request $request)
    {
        $selectedDate = $request->input('date');
        $levels = LevelOfCare::orderBy('level_of_care')->get();
        $selectedLevel = $request->input('level_of_care');
        $selectedGroupId = $request->input('group_id');
        $selectedServiceCodeId = $request->input('service_code_id');

        $levelOptions = $levels->map(fn ($level) => [
            'id' => $level->id,
            'code' => $level->level_of_care,
        ])->values();
        $allGroups = ClientGroup::orderBy('name')
            ->get(['id', 'name', 'level_of_care_id']);
        $serviceCodes = ServiceCode::with('levelsOfCare:id')
            ->orderBy('service_code')
            ->get(['id', 'friendly_name', 'service_type'])
            ->filter(fn ($code) => strtolower((string) $code->service_type) === 'group')
            ->values();
        $serviceCodeOptions = $serviceCodes->map(fn ($code) => [
            'id' => $code->id,
            'friendly_name' => $code->friendly_name,
            'level_ids' => $code->levelsOfCare->pluck('id')->values(),
        ])->values();

        $filteredServiceCodes = collect();
        $groups = collect();
        $level = null;
        if ($selectedLevel) {
            $level = LevelOfCare::where('level_of_care', $selectedLevel)->first();
            $filteredServiceCodes = $level?->serviceCodes()
                ->orderBy('service_code')
                ->get(['service_codes.id', 'service_codes.friendly_name', 'service_codes.service_type'])
                ->filter(fn ($code) => strtolower((string) $code->service_type) === 'group')
                ->values() ?? collect();
            $groups = $level
                ? ClientGroup::where('level_of_care_id', $level->id)->orderBy('name')->get()
                : collect();
        }

        $clients = collect();
        if ($selectedDate && $selectedLevel && $selectedGroupId) {
            $clients = Client::with(['apartment.house'])
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
                })
                ->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $selectedGroupId) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->where('client_group_id', $selectedGroupId);
                })
                ->orderBy('last_name')
                ->get();
        }

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

        if ($selectedLevel) {
            $clients = $clients->filter(function ($client) use ($selectedLevel) {
                $levels = collect(explode(',', (string) $client->level_of_care))
                    ->map(fn ($level) => trim($level))
                    ->filter()
                    ->values();

                return $levels->containsStrict($selectedLevel);
            })->values();
        }

        $attendanceData = collect();
        $notesData = collect();
        if ($selectedDate && $selectedServiceCodeId) {
            $attendanceData = Attendance2026::whereDate('service_date', $selectedDate)
                ->where('service_code_id', $selectedServiceCodeId)
                ->select('client_id', DB::raw('COUNT(*) as total'))
                ->groupBy('client_id')
                ->get()
                ->keyBy('client_id');

            $notesData = ClinicalNote::whereDate('service_date', $selectedDate)
                ->where('service_code_id', $selectedServiceCodeId)
                ->select('client_id', DB::raw('COUNT(*) as total'))
                ->groupBy('client_id')
                ->get()
                ->keyBy('client_id');
        }

        return view('cprs.group_therapy', compact(
            'clients',
            'attendanceData',
            'notesData',
            'selectedDate',
            'levelOptions',
            'serviceCodeOptions',
            'allGroups',
            'selectedLevel',
            'selectedGroupId',
            'selectedServiceCodeId',
            'groups',
            'filteredServiceCodes'
        ));
    }

    public function groupTherapyServiceCodes(Request $request)
    {
        try {
            $level = $request->input('level_of_care');
            if (! $level) {
                return response()->json([]);
            }

            $levelOfCare = LevelOfCare::where('level_of_care', $level)->first();
            if (! $levelOfCare) {
                return response()->json([]);
            }

            $serviceCodes = $levelOfCare->serviceCodes()
                ->orderBy('service_code')
                ->get(['id', 'service_code', 'friendly_name']);

            return response()->json($serviceCodes);
        } catch (\Throwable $exception) {
            return response()->json([]);
        }
    }

    public function groupTherapyGroups(Request $request)
    {
        try {
            $level = $request->input('level_of_care');
            if (! $level) {
                return response()->json([]);
            }

            $levelOfCare = LevelOfCare::where('level_of_care', $level)->first();
            if (! $levelOfCare) {
                return response()->json([]);
            }

            $groups = ClientGroup::where('level_of_care_id', $levelOfCare->id)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json($groups);
        } catch (\Throwable $exception) {
            return response()->json([]);
        }
    }

    public function groupTherapyStore(Request $request)
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'level_of_care' => ['required', 'string'],
            'group_id' => ['required', 'exists:client_groups,id'],
            'service_code_id' => ['required', 'exists:service_codes,id'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i'],
            'remarks' => ['nullable', 'string'],
            'attendance' => ['array'],
            'attachments.*' => ['nullable', 'file', 'max:2048'],
            'stored_attachments' => ['array'],
            'stored_attachments.*' => ['string'],
        ]);

        $attachments = $this->storeAttachments($request);
        $attendanceRows = $request->input('attendance', []);
        $presentClientIds = collect($attendanceRows)
            ->filter(fn ($data) => !empty($data['present']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($presentClientIds->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select at least one client before submitting.');
        }

        if (! $request->boolean('confirm')) {
            $presentClients = Client::whereIn('id', $presentClientIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            $serviceCode = ServiceCode::find($validated['service_code_id']);

            return view('cprs.group_therapy_review', [
                'presentClients' => $presentClients,
                'attendanceRows' => $attendanceRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'attachments' => $attachments,
            ]);
        }

        $submission = AttendanceSubmission::create([
            'type' => 'group_therapy',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $validated['service_code_id'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'total_attendance' => $presentClientIds->count(),
            'submitted_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        foreach ($attendanceRows as $clientId => $data) {
            $present = !empty($data['present']);

            if ($present) {
                Attendance2026::create([
                    'client_id' => $clientId,
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $validated['service_code_id'],
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                    'units' => 1,
                    'remarks' => $validated['remarks'],
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }
        }

        return redirect()
            ->route('cprs.index')
            ->with('success', 'Group therapy submitted.');
    }

    public function cprsGroup(Request $request)
    {
        $selectedDate = $request->input('date');
        $serviceCode = ServiceCode::where('service_code', 'H0024')->firstOrFail();
        $timeStart = $request->input('time_start');
        $timeEnd = $request->input('time_end');
        $remarks = $request->input('remarks');
        $selectedPeerGroupId = $request->input('peer_group_id');

        $peerGroups = PeerGroup::orderBy('name')->get(['id', 'name']);

        if (! $selectedDate || ! $selectedPeerGroupId) {
            $clients = collect();
        } else {
            $clients = Client::with(['apartment.house'])
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
                })
                ->whereHas('peerGroupHistory', function ($query) use ($selectedDate, $selectedPeerGroupId) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->where('peer_group_id', $selectedPeerGroupId);
                })
                ->orderBy('last_name')
                ->get();
        }

        foreach ($clients as $client) {
            if ($selectedDate) {
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
            } else {
                $client->level_of_care = '';
            }
        }

        $attendanceData = collect();
        if ($selectedDate) {
            $attendanceData = Attendance2026::whereDate('service_date', $selectedDate)
                ->where('service_code_id', $serviceCode->id)
                ->select('client_id', DB::raw('SUM(units) as units'))
                ->groupBy('client_id')
                ->get()
                ->keyBy('client_id');
        }

        $attendanceMeta = null;
        if ($selectedDate) {
            $attendanceMeta = Attendance2026::whereDate('service_date', $selectedDate)
                ->where('service_code_id', $serviceCode->id)
                ->latest()
                ->first();
        }
        $attendanceMetaTimeStart = $timeStart ?? ($attendanceMeta?->time_start ? substr($attendanceMeta->time_start, 0, 5) : '');
        $attendanceMetaTimeEnd = $timeEnd ?? ($attendanceMeta?->time_end ? substr($attendanceMeta->time_end, 0, 5) : '');
        $attendanceMetaRemarks = $remarks ?? $attendanceMeta?->remarks;

        return view('cprs.cprs_group', compact(
            'clients',
            'attendanceData',
            'attendanceMeta',
            'attendanceMetaTimeStart',
            'attendanceMetaTimeEnd',
            'attendanceMetaRemarks',
            'selectedDate',
            'serviceCode',
            'peerGroups',
            'selectedPeerGroupId'
        ));
    }

    public function cprsGroupStore(Request $request)
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'service_code_id' => ['required', 'exists:service_codes,id'],
            'peer_group_id' => ['required', 'exists:peer_groups,id'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i'],
            'units' => ['required', 'integer', 'min:0'],
            'remarks' => ['required', 'string'],
            'attendance' => ['array'],
            'attachments.*' => ['nullable', 'file', 'max:2048'],
            'stored_attachments' => ['array'],
            'stored_attachments.*' => ['string'],
        ]);

        $attachments = $this->storeAttachments($request);
        $attendanceRows = $request->input('attendance', []);
        $presentClientIds = collect($attendanceRows)
            ->filter(fn ($data) => !empty($data['present']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($presentClientIds->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select at least one client before submitting.');
        }

        if (! $request->boolean('confirm')) {
            $presentClients = Client::whereIn('id', $presentClientIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            $serviceCode = ServiceCode::find($validated['service_code_id']);

            return view('cprs.cprs_group_review', [
                'presentClients' => $presentClients,
                'attendanceRows' => $attendanceRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'attachments' => $attachments,
            ]);
        }

        $submission = AttendanceSubmission::create([
            'type' => 'cprs_group',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $validated['service_code_id'],
            'peer_group_id' => $validated['peer_group_id'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'total_attendance' => $presentClientIds->count(),
            'submitted_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        foreach ($attendanceRows as $clientId => $data) {
            $present = !empty($data['present']);

            if ($present) {
                Attendance2026::create([
                    'client_id' => $clientId,
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $validated['service_code_id'],
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                    'units' => $validated['units'],
                    'remarks' => null,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }
        }

        return redirect()
            ->route('cprs.index')
            ->with('success', 'CPRS Group submitted.');
    }

    public function showSubmission(AttendanceSubmission $submission)
    {
        $attendanceRows = Attendance2026::with(['client', 'serviceCode'])
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? '']);

        return view($this->cprsSubmissionView($submission, 'show'), compact('submission', 'attendanceRows'));
    }

    public function editSubmission(AttendanceSubmission $submission)
    {
        if ($submission->type !== 'cprs_group') {
            $attendanceRows = Attendance2026::with('client')
                ->where('attendance_submission_id', $submission->id)
                ->get()
                ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? ''])
                ->values();

            $clientsList = Client::active()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'last_name', 'first_name']);

            return view($this->cprsSubmissionView($submission, 'edit'), compact('submission', 'attendanceRows', 'clientsList'));
        }

        $submission->load(['peerGroup', 'serviceCode']);
        $selectedDate = optional($submission->service_date)->toDateString();
        $peerGroups = PeerGroup::orderBy('name')->get(['id', 'name']);
        $selectedPeerGroupId = request('peer_group_id') ?? $submission->peer_group_id;

        $clients = collect();
        if ($selectedDate && $selectedPeerGroupId) {
            $clients = Client::with(['apartment.house'])
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
                })
                ->whereHas('peerGroupHistory', function ($query) use ($selectedDate, $selectedPeerGroupId) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->where('peer_group_id', $selectedPeerGroupId);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

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

        $attendanceRows = Attendance2026::with('client')
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->keyBy('client_id');

        $groupClientIds = $clients->pluck('id')->values();
        $outOfGroupRows = Attendance2026::with('client')
            ->where('attendance_submission_id', $submission->id)
            ->whereNotIn('client_id', $groupClientIds)
            ->get()
            ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? ''])
            ->values();

        foreach ($outOfGroupRows as $row) {
            if (! $row->client) {
                continue;
            }
            $levelsForDate = $row->client->levelOfCareHistory()
                ->with('levelOfCare')
                ->where('start_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                })
                ->get();

            $row->client->level_of_care = $levelsForDate
                ->map(fn ($level) => $level->levelOfCare?->level_of_care)
                ->filter()
                ->unique()
                ->implode(', ');
        }

        return view($this->cprsSubmissionView($submission, 'edit'), compact(
            'submission',
            'peerGroups',
            'selectedPeerGroupId',
            'clients',
            'attendanceRows',
            'outOfGroupRows'
        ));
    }

    public function updateSubmission(Request $request, AttendanceSubmission $submission)
    {
        $rules = [
            'remarks' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:2048'],
            'stored_attachments' => ['array'],
            'stored_attachments.*' => ['string'],
        ];

        if ($submission->type === 'cprs_group') {
            $rules['peer_group_id'] = ['required', 'exists:peer_groups,id'];
            $rules['attendance'] = ['array'];
        } elseif ($submission->type === 'batch_outing') {
            $rules['service_date'] = ['required', 'date'];
            $rules['time_start'] = ['required', 'date_format:H:i'];
            $rules['time_end'] = ['required', 'date_format:H:i'];
            $rules['units'] = ['required', 'integer', 'min:0'];
            $rules['rows'] = ['required', 'array', 'min:1'];
            $rules['rows.*.client_id'] = ['required', 'integer', 'exists:clients,id'];
            $rules['rows.*.remarks'] = ['nullable', 'string'];
        } else {
            $rules['service_date'] = ['required', 'date'];
            $rules['rows'] = ['required', 'array', 'min:1'];
            $rules['rows.*.client_id'] = ['required', 'integer', 'exists:clients,id'];
            $rules['rows.*.time_start'] = ['required', 'date_format:H:i'];
            $rules['rows.*.time_end'] = ['required', 'date_format:H:i'];
            $rules['rows.*.units'] = ['required', 'integer', 'min:0'];
            $rules['rows.*.remarks'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        $attachments = $this->storeAttachments($request, $submission->attachments ?? []);

        $submission->update([
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        if ($submission->type === 'cprs_group') {
            $attendanceRows = $request->input('attendance', []);
            $presentClientIds = collect($attendanceRows)
                ->filter(fn ($data) => !empty($data['present']))
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($presentClientIds->isEmpty()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Please select at least one client before submitting.');
            }

            $submission->update([
                'peer_group_id' => $validated['peer_group_id'],
                'total_attendance' => $presentClientIds->count(),
            ]);

            $existingRows = Attendance2026::where('attendance_submission_id', $submission->id)->get()->keyBy('client_id');
            $defaultUnits = $existingRows->first()?->units ?? 1;

            foreach ($presentClientIds as $clientId) {
                if ($existingRows->has($clientId)) {
                    continue;
                }

                Attendance2026::create([
                    'client_id' => $clientId,
                    'service_date' => $submission->service_date,
                    'service_code_id' => $submission->service_code_id,
                    'time_start' => $submission->time_start,
                    'time_end' => $submission->time_end,
                    'units' => $defaultUnits,
                    'remarks' => $validated['remarks'] ?? $submission->remarks,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }

            $existingRows->keys()
                ->diff($presentClientIds)
                ->each(function ($clientId) use ($submission) {
                    Attendance2026::where('attendance_submission_id', $submission->id)
                        ->where('client_id', $clientId)
                        ->delete();
                });
        } elseif ($submission->type === 'batch_outing') {
            $rows = collect($validated['rows']);
            $submission->update([
                'service_date' => $validated['service_date'],
                'time_start' => $validated['time_start'],
                'time_end' => $validated['time_end'],
                'total_attendance' => $rows->count(),
            ]);

            Attendance2026::where('attendance_submission_id', $submission->id)->delete();

            foreach ($rows as $row) {
                Attendance2026::create([
                    'client_id' => $row['client_id'],
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $submission->service_code_id,
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                    'units' => $validated['units'],
                    'remarks' => $row['remarks'] ?? null,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }
        } else {
            $rows = collect($validated['rows']);
            $startTimes = $rows->pluck('time_start')->filter();
            $endTimes = $rows->pluck('time_end')->filter();

            $submission->update([
                'service_date' => $validated['service_date'],
                'time_start' => $startTimes->min(),
                'time_end' => $endTimes->max(),
                'total_attendance' => $rows->count(),
            ]);

            Attendance2026::where('attendance_submission_id', $submission->id)->delete();

            foreach ($rows as $row) {
                Attendance2026::create([
                    'client_id' => $row['client_id'],
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $submission->service_code_id,
                    'time_start' => $row['time_start'],
                    'time_end' => $row['time_end'],
                    'units' => $row['units'],
                    'remarks' => $row['remarks'] ?? null,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }
        }

        Attendance2026::where('attendance_submission_id', $submission->id)
            ->update(['remarks' => $validated['remarks'] ?? null]);

        return redirect()
            ->route('cprs.index')
            ->with('success', 'CPRS submission updated.');
    }

    public function destroySubmission(AttendanceSubmission $submission)
    {
        $submission->delete();

        return redirect()
            ->route('cprs.index')
            ->with('success', 'CPRS submission deleted.');
    }

    public function downloadSubmission(AttendanceSubmission $submission)
    {
        $attendanceRows = Attendance2026::with(['client', 'serviceCode'])
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? '']);

        $pdf = Pdf::loadView($this->cprsSubmissionView($submission, 'pdf'), [
            'submission' => $submission,
            'attendanceRows' => $attendanceRows,
        ])->setPaper('letter');

        return $pdf->download('attendance_submission_' . $submission->id . '.pdf');
    }

    public function downloadAttachment(AttendanceSubmission $submission, string $filename)
    {
        $attachments = $submission->attachments ?? [];
        if (! in_array($filename, $attachments, true)) {
            abort(404, 'File not found.');
        }

        $path = 'attachments/attendance2026/' . $filename;
        if (! Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path, $filename);
    }

    protected function cprsSubmissionView(AttendanceSubmission $submission, string $view): string
    {
        return match ($submission->type) {
            'batch_outing' => "cprs.peer-outing.{$view}",
            'prod_sheet' => "cprs.individual.{$view}",
            'cprs_group' => "cprs.group.{$view}",
            default => "cprs.individual.{$view}",
        };
    }

    protected function storeAttachments(Request $request, array $existing = []): array
    {
        $attachments = is_array($existing) ? $existing : [];
        $stored = $request->input('stored_attachments', []);
        if (is_array($stored)) {
            $attachments = array_merge($attachments, $stored);
        }

        if (! $request->hasFile('attachments')) {
            return array_values(array_unique($attachments));
        }

        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $timestamp = now()->timestamp;
            $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $file->storeAs('attachments/attendance2026', $uniqueFileName);
            $attachments[] = $uniqueFileName;
        }

        return array_values(array_unique($attachments));
    }
}
