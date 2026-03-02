<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSubmission;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\ClinicalNote;
use App\Models\LevelOfCare;
use App\Models\ServiceCode;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClinicalNotesController extends Controller
{
    public function index()
    {
        $query = AttendanceSubmission::with(['submittedBy', 'serviceCode', 'levelOfCare', 'clientGroup', 'peerGroup'])
            ->whereIn('type', ['clinical_group_therapy', 'clinical_op_individual', 'clinical_prod_sheet'])
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

        $submissions = $query->get();
        $submitters = User::orderBy('name')->get();

        return view('clinical-notes.index', compact('submissions', 'submitters'));
    }

    public function productivitySheet()
    {
        $selectedDate = request('date', now()->toDateString());

        $clients = Client::with(['apartment.house'])
            ->active()
            ->orderBy('last_name')
            ->get();

        $clientOptions = $clients->map(fn ($client) => [
            'id' => $client->id,
            'label' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
        ])->values();

        $serviceCode = ServiceCode::where('service_code', 'H0001')->firstOrFail();

        $noteTypeOptions = $this->productivityNoteTypeOptions();

        $counselors = $clients
            ->map(fn ($client) => $client->counselor?->name ?? 'Unassigned')
            ->unique()
            ->sort()
            ->values();

        return view('clinical-notes.productivity_sheet', compact(
            'selectedDate',
            'clientOptions',
            'noteTypeOptions',
            'serviceCode'
        ));
    }

    public function productivitySheetStore(Request $request)
    {
        $validated = $request->validate([
            'service_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.client_id' => ['required', 'exists:clients,id'],
            'rows.*.note_type' => ['required', 'string'],
            'rows.*.interaction_date' => ['required', 'date'],
            'rows.*.time_start' => ['required', 'date_format:H:i'],
            'rows.*.time_end' => ['required', 'date_format:H:i'],
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
            $serviceCode = ServiceCode::where('service_code', 'H0001')->firstOrFail();

            $reviewRows = collect($rows)->map(function ($row) use ($clients) {
                return [
                    'client' => $clients->get($row['client_id']),
                    'note_type' => $row['note_type'],
                    'interaction_date' => $row['interaction_date'],
                    'time_start' => $row['time_start'],
                    'time_end' => $row['time_end'],
                    'remarks' => $row['remarks'] ?? null,
                ];
            });

            return view('clinical-notes.productivity_sheet_review', [
                'rows' => $reviewRows,
                'meta' => $validated,
                'attachments' => $attachments,
                'serviceCode' => $serviceCode,
            ]);
        }

        $startTimes = collect($rows)->pluck('time_start')->filter();
        $endTimes = collect($rows)->pluck('time_end')->filter();
        $serviceCode = ServiceCode::where('service_code', 'H0001')->firstOrFail();

        $submission = AttendanceSubmission::create([
            'type' => 'clinical_prod_sheet',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $serviceCode->id,
            'time_start' => $startTimes->min(),
            'time_end' => $endTimes->max(),
            'total_attendance' => count($rows),
            'submitted_by' => auth()->id(),
            'attachments' => $attachments ?: null,
        ]);

        foreach ($rows as $row) {
            ClinicalNote::create([
                'client_id' => $row['client_id'],
                'service_date' => $validated['service_date'],
                'service_code_id' => $serviceCode->id,
                'note_type' => $row['note_type'],
                'time_start' => $row['time_start'],
                'time_end' => $row['time_end'],
                'units' => 1,
                'remarks' => $row['remarks'] ?? null,
                'marked_by' => auth()->id(),
                'attendance_submission_id' => $submission->id,
            ]);
        }

        return redirect()
            ->route('clinical-notes.index')
            ->with('success', 'Clinical productivity sheet submitted.');
    }

    public function tracker()
    {
        $clients = Client::with(['counselor', 'levelOfCareHistory.levelOfCare', 'hospitalizations', 'authorizations.lineOfServices'])
            ->active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $noteTypeOptions = collect([
            'Auth Initial Package',
            'Assessments',
            'Biopsycosocial',
            'Discharge Plan',
            'Discharge Summary',
            'Auth Update Package',
            'Transition Note',
        ])->sort()->values();

        $counselors = $clients
            ->map(fn ($client) => $client->counselor?->name ?? 'Unassigned')
            ->unique()
            ->sort()
            ->values();

        $clientIds = $clients->pluck('id')->values();
        $latestNotes = ClinicalNote::query()
            ->whereIn('client_id', $clientIds)
            ->whereIn('note_type', $noteTypeOptions->all())
            ->select('client_id', 'note_type', DB::raw('MAX(service_date) as last_date'))
            ->groupBy('client_id', 'note_type')
            ->get()
            ->groupBy('client_id');
        $noteDates = ClinicalNote::query()
            ->whereIn('client_id', $clientIds)
            ->whereIn('note_type', $noteTypeOptions->all())
            ->select('client_id', 'note_type', 'service_date')
            ->get()
            ->groupBy('client_id')
            ->map(fn ($rows) => $rows->groupBy('note_type')->map(fn ($items) => $items->pluck('service_date')));

        $today = now()->toDateString();
        $trackerRows = $clients->map(function ($client) use ($noteTypeOptions, $today, $latestNotes, $noteDates) {
            $clientNotes = $latestNotes->get($client->id, collect())->keyBy('note_type');
            $clientNoteDates = $noteDates->get($client->id, collect());
            $startingDate = $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->toDateString() : null;
            $dischargeDate = $client->discharge_date ? \Carbon\Carbon::parse($client->discharge_date)->toDateString() : null;

            $levelStarts = $client->levelOfCareHistory
                ->filter(fn ($record) => ! empty($record->start_date))
                ->map(fn ($record) => \Carbon\Carbon::parse($record->start_date)->toDateString())
                ->unique()
                ->sort()
                ->values();
            if ($startingDate && ! $levelStarts->contains($startingDate)) {
                $levelStarts->prepend($startingDate);
            }
            $transitionDates = $levelStarts->filter(fn ($date) => $startingDate && $date !== $startingDate)->values();

            $latestHospitalization = $client->hospitalizations
                ->sortByDesc('start_date')
                ->first();
            $readmissionDate = $latestHospitalization?->start_date
                ? \Carbon\Carbon::parse($latestHospitalization->start_date)->toDateString()
                : null;
            $reactivationDate = $client->reactivation_date
                ? \Carbon\Carbon::parse($client->reactivation_date)->toDateString()
                : null;

            $currentLevel = $client->levelOfCareHistory
                ->filter(function ($record) use ($today) {
                    if (! $record->start_date) {
                        return false;
                    }
                    $start = \Carbon\Carbon::parse($record->start_date)->toDateString();
                    $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->toDateString() : null;

                    return $start <= $today && (! $end || $end >= $today);
                })
                ->sortByDesc('start_date')
                ->first();
            $updateDays = $currentLevel?->levelOfCare?->update_note_days;

            $authLines = $client->authorizations
                ->flatMap(fn ($auth) => $auth->lineOfServices ?? collect())
                ->filter(fn ($line) => ! empty($line->ending_date))
                ->sortBy(fn ($line) => $line->ending_date)
                ->values();

            $dueDates = $noteTypeOptions->mapWithKeys(function ($noteType) use (
                $clientNotes,
                $clientNoteDates,
                $startingDate,
                $dischargeDate,
                $today,
                $transitionDates,
                $readmissionDate,
                $reactivationDate,
                $updateDays,
                $authLines
            ) {
                $lastDate = $clientNotes->get($noteType)?->last_date;
                $lastDateString = $lastDate ? \Carbon\Carbon::parse($lastDate)->toDateString() : null;
                $noteDatesForType = $clientNoteDates->get($noteType, collect())
                    ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                    ->values();
                $isCovered = function (?string $dueDate) use ($noteDatesForType) {
                    if (! $dueDate) {
                        return false;
                    }
                    return $noteDatesForType->contains(function ($date) use ($dueDate) {
                        $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                        return $diff <= 5;
                    });
                };

                if ($noteType === 'Auth Initial Package') {
                    $authUpdateDates = $clientNoteDates->get('Auth Update Package', collect())
                        ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                        ->values();
                    $coverageDates = $noteDatesForType->merge($authUpdateDates)->unique();
                    $isCoveredForInitial = function (?string $dueDate) use ($coverageDates) {
                        if (! $dueDate) {
                            return false;
                        }
                        return $coverageDates->contains(function ($date) use ($dueDate) {
                            $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                            return $diff <= 5;
                        });
                    };

                    $dueDates = collect([$startingDate])
                        ->merge($transitionDates)
                        ->merge([$readmissionDate, $reactivationDate])
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();

                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCoveredForInitial) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCoveredForInitial($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                $onceOnStart = [
                    'Assessments',
                    'Biopsycosocial',
                    'Discharge Plan',
                ];

                if (in_array($noteType, $onceOnStart, true)) {
                    $dueDates = collect([$startingDate]);
                    $dueDates = $dueDates->filter();
                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCovered) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCovered($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($noteType === 'Transition Note') {
                    $dueDates = $transitionDates;
                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCovered) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCovered($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($noteType === 'Discharge Summary') {
                    $dueDate = $dischargeDate;
                    if (! $dueDate) {
                        return [$noteType => null];
                    }
                    if ($isCovered($dueDate)) {
                        return [$noteType => 'Complete'];
                    }
                    return [$noteType => $dueDate];
                }

                if ($noteType === 'Auth Update Package') {
                    $authInitialDates = $clientNoteDates->get('Auth Initial Package', collect())
                        ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                        ->values();
                    $coverageDates = $noteDatesForType->merge($authInitialDates)->unique();
                    $closestCoverageDate = function (?string $dueDate) use ($coverageDates) {
                        if (! $dueDate) {
                            return null;
                        }
                        $matched = $coverageDates->filter(function ($date) use ($dueDate) {
                            $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                            return $diff <= 5;
                        });
                        if ($matched->isEmpty()) {
                            return null;
                        }
                        return $matched->max();
                    };

                    $lastInitialDate = $clientNotes->get('Auth Initial Package')?->last_date;
                    $lastInitialDateString = $lastInitialDate
                        ? \Carbon\Carbon::parse($lastInitialDate)->toDateString()
                        : null;
                    $lastUpdateOrInitial = collect([$lastDateString, $lastInitialDateString])->filter()->sort()->last();

                    $dueDates = collect();
                    $rollingBaseDate = $lastUpdateOrInitial ?? $startingDate;
                    foreach ($authLines as $line) {
                        $lineStart = $line->starting_date ? \Carbon\Carbon::parse($line->starting_date)->toDateString() : null;
                        $lineEnd = $line->ending_date ? \Carbon\Carbon::parse($line->ending_date)->toDateString() : null;
                        if (! $lineEnd) {
                            continue;
                        }
                        $lineUpdateDays = $line->authorization?->levelOfCare?->update_note_days ?? $updateDays;
                        $authDue = \Carbon\Carbon::parse($lineEnd)->toDateString();
                        $baseDate = $rollingBaseDate ?? $lineStart ?? $startingDate;
                        if ($lineStart && $baseDate && $baseDate < $lineStart) {
                            $dayGap = \Carbon\Carbon::parse($baseDate)->diffInDays(\Carbon\Carbon::parse($lineStart));
                            if ($dayGap > 5) {
                                $baseDate = $lineStart;
                            }
                        }

                        if ($lineStart && $authDue <= $lineStart) {
                            $dueDates->push($lineStart);
                            continue;
                        }

                        while ($baseDate && $lineUpdateDays && $lineUpdateDays > 0) {
                            $intervalDue = \Carbon\Carbon::parse($baseDate)->addDays($lineUpdateDays)->toDateString();
                            $candidate = collect([$intervalDue, $authDue])->filter()->sort()->first();
                            if (! $candidate) {
                                break;
                            }
                            if ($lineStart && $candidate < $lineStart) {
                                $baseDate = $lineStart;
                                continue;
                            }
                            $lastDue = $dueDates->last();
                            if ($lastDue && \Carbon\Carbon::parse($lastDue)->diffInDays(\Carbon\Carbon::parse($candidate)) <= 5) {
                                $baseDate = $candidate;
                                if ($candidate === $authDue) {
                                    break;
                                }
                                continue;
                            }
                            $dueDates->push($candidate);
                            $baseDate = $candidate;
                            if ($candidate === $authDue) {
                                break;
                            }
                        }

                        if ((! $lineUpdateDays || $lineUpdateDays <= 0 || ! $baseDate) && $authDue) {
                            $dueDates->push($authDue);
                        }

                        if ($baseDate) {
                            $rollingBaseDate = $baseDate;
                        }
                    }

                    foreach ([$readmissionDate, $reactivationDate] as $triggerDate) {
                        if ($triggerDate) {
                            $dueDates->push($triggerDate);
                        }
                    }
                    $dueDates = $dueDates->filter()->unique()->sort()->values();

                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $closestCoverageDate) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $closestCoverageDate($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($lastDateString) {
                    return [$noteType => 'Complete'];
                }

                return [$noteType => null];
            });

            return [
                'client' => $client,
                'due_dates' => $dueDates,
            ];
        });

        return view('clinical-notes.tracker', compact('trackerRows', 'noteTypeOptions', 'counselors'));
    }

    public function trackerDownload()
    {
        $clients = Client::with(['counselor', 'levelOfCareHistory.levelOfCare', 'hospitalizations', 'authorizations.lineOfServices'])
            ->active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $noteTypeOptions = collect([
            'Auth Initial Package',
            'Assessments',
            'Biopsycosocial',
            'Discharge Plan',
            'Discharge Summary',
            'Auth Update Package',
            'Transition Note',
        ])->sort()->values();

        $clientIds = $clients->pluck('id')->values();
        $latestNotes = ClinicalNote::query()
            ->whereIn('client_id', $clientIds)
            ->whereIn('note_type', $noteTypeOptions->all())
            ->select('client_id', 'note_type', DB::raw('MAX(service_date) as last_date'))
            ->groupBy('client_id', 'note_type')
            ->get()
            ->groupBy('client_id');
        $noteDates = ClinicalNote::query()
            ->whereIn('client_id', $clientIds)
            ->whereIn('note_type', $noteTypeOptions->all())
            ->select('client_id', 'note_type', 'service_date')
            ->get()
            ->groupBy('client_id')
            ->map(fn ($rows) => $rows->groupBy('note_type')->map(fn ($items) => $items->pluck('service_date')));

        $today = now()->toDateString();
        $trackerRows = $clients->map(function ($client) use ($noteTypeOptions, $today, $latestNotes, $noteDates) {
            $clientNotes = $latestNotes->get($client->id, collect())->keyBy('note_type');
            $clientNoteDates = $noteDates->get($client->id, collect());
            $startingDate = $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->toDateString() : null;
            $dischargeDate = $client->discharge_date ? \Carbon\Carbon::parse($client->discharge_date)->toDateString() : null;

            $levelStarts = $client->levelOfCareHistory
                ->filter(fn ($record) => ! empty($record->start_date))
                ->map(fn ($record) => \Carbon\Carbon::parse($record->start_date)->toDateString())
                ->unique()
                ->sort()
                ->values();
            if ($startingDate && ! $levelStarts->contains($startingDate)) {
                $levelStarts->prepend($startingDate);
            }
            $transitionDates = $levelStarts->filter(fn ($date) => $startingDate && $date !== $startingDate)->values();

            $latestHospitalization = $client->hospitalizations
                ->sortByDesc('start_date')
                ->first();
            $readmissionDate = $latestHospitalization?->start_date
                ? \Carbon\Carbon::parse($latestHospitalization->start_date)->toDateString()
                : null;
            $reactivationDate = $client->reactivation_date
                ? \Carbon\Carbon::parse($client->reactivation_date)->toDateString()
                : null;

            $currentLevel = $client->levelOfCareHistory
                ->filter(function ($record) use ($today) {
                    if (! $record->start_date) {
                        return false;
                    }
                    $start = \Carbon\Carbon::parse($record->start_date)->toDateString();
                    $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->toDateString() : null;

                    return $start <= $today && (! $end || $end >= $today);
                })
                ->sortByDesc('start_date')
                ->first();
            $updateDays = $currentLevel?->levelOfCare?->update_note_days;

            $authLines = $client->authorizations
                ->flatMap(fn ($auth) => $auth->lineOfServices ?? collect())
                ->filter(fn ($line) => ! empty($line->ending_date))
                ->sortBy(fn ($line) => $line->ending_date)
                ->values();

            $dueDates = $noteTypeOptions->mapWithKeys(function ($noteType) use (
                $clientNotes,
                $clientNoteDates,
                $startingDate,
                $dischargeDate,
                $today,
                $transitionDates,
                $readmissionDate,
                $reactivationDate,
                $updateDays,
                $authLines
            ) {
                $lastDate = $clientNotes->get($noteType)?->last_date;
                $lastDateString = $lastDate ? \Carbon\Carbon::parse($lastDate)->toDateString() : null;
                $noteDatesForType = $clientNoteDates->get($noteType, collect())
                    ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                    ->values();
                $isCovered = function (?string $dueDate) use ($noteDatesForType) {
                    if (! $dueDate) {
                        return false;
                    }
                    return $noteDatesForType->contains(function ($date) use ($dueDate) {
                        $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                        return $diff <= 5;
                    });
                };

                if ($noteType === 'Auth Initial Package') {
                    $authUpdateDates = $clientNoteDates->get('Auth Update Package', collect())
                        ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                        ->values();
                    $coverageDates = $noteDatesForType->merge($authUpdateDates)->unique();
                    $isCoveredForInitial = function (?string $dueDate) use ($coverageDates) {
                        if (! $dueDate) {
                            return false;
                        }
                        return $coverageDates->contains(function ($date) use ($dueDate) {
                            $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                            return $diff <= 5;
                        });
                    };

                    $dueDates = collect([$startingDate])
                        ->merge($transitionDates)
                        ->merge([$readmissionDate, $reactivationDate])
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();

                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCoveredForInitial) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCoveredForInitial($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                $onceOnStart = [
                    'Assessments',
                    'Biopsycosocial',
                    'Discharge Plan',
                ];

                if (in_array($noteType, $onceOnStart, true)) {
                    $dueDates = collect([$startingDate]);
                    $dueDates = $dueDates->filter();
                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCovered) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCovered($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($noteType === 'Transition Note') {
                    $dueDates = $transitionDates;
                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $isCovered) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $isCovered($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($noteType === 'Discharge Summary') {
                    $dueDate = $dischargeDate;
                    if (! $dueDate) {
                        return [$noteType => null];
                    }
                    if ($isCovered($dueDate)) {
                        return [$noteType => 'Complete'];
                    }
                    return [$noteType => $dueDate];
                }

                if ($noteType === 'Auth Update Package') {
                    $authInitialDates = $clientNoteDates->get('Auth Initial Package', collect())
                        ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
                        ->values();
                    $coverageDates = $noteDatesForType->merge($authInitialDates)->unique();
                    $closestCoverageDate = function (?string $dueDate) use ($coverageDates) {
                        if (! $dueDate) {
                            return null;
                        }
                        $matched = $coverageDates->filter(function ($date) use ($dueDate) {
                            $diff = \Carbon\Carbon::parse($date)->diffInDays(\Carbon\Carbon::parse($dueDate));
                            return $diff <= 5;
                        });
                        if ($matched->isEmpty()) {
                            return null;
                        }
                        return $matched->max();
                    };

                    $lastInitialDate = $clientNotes->get('Auth Initial Package')?->last_date;
                    $lastInitialDateString = $lastInitialDate
                        ? \Carbon\Carbon::parse($lastInitialDate)->toDateString()
                        : null;
                    $lastUpdateOrInitial = collect([$lastDateString, $lastInitialDateString])->filter()->sort()->last();

                    $dueDates = collect();
                    $rollingBaseDate = $lastUpdateOrInitial ?? $startingDate;
                    foreach ($authLines as $line) {
                        $lineStart = $line->starting_date ? \Carbon\Carbon::parse($line->starting_date)->toDateString() : null;
                        $lineEnd = $line->ending_date ? \Carbon\Carbon::parse($line->ending_date)->toDateString() : null;
                        if (! $lineEnd) {
                            continue;
                        }
                        $lineUpdateDays = $line->authorization?->levelOfCare?->update_note_days ?? $updateDays;
                        $authDue = \Carbon\Carbon::parse($lineEnd)->toDateString();
                        $baseDate = $rollingBaseDate ?? $lineStart ?? $startingDate;
                        if ($lineStart && $baseDate && $baseDate < $lineStart) {
                            $dayGap = \Carbon\Carbon::parse($baseDate)->diffInDays(\Carbon\Carbon::parse($lineStart));
                            if ($dayGap > 5) {
                                $baseDate = $lineStart;
                            }
                        }

                        if ($lineStart && $authDue <= $lineStart) {
                            $dueDates->push($lineStart);
                            continue;
                        }

                        while ($baseDate && $lineUpdateDays && $lineUpdateDays > 0) {
                            $intervalDue = \Carbon\Carbon::parse($baseDate)->addDays($lineUpdateDays)->toDateString();
                            $candidate = collect([$intervalDue, $authDue])->filter()->sort()->first();
                            if (! $candidate) {
                                break;
                            }
                            if ($lineStart && $candidate < $lineStart) {
                                $baseDate = $lineStart;
                                continue;
                            }
                            $lastDue = $dueDates->last();
                            if ($lastDue && \Carbon\Carbon::parse($lastDue)->diffInDays(\Carbon\Carbon::parse($candidate)) <= 5) {
                                $baseDate = $candidate;
                                if ($candidate === $authDue) {
                                    break;
                                }
                                continue;
                            }
                            $dueDates->push($candidate);
                            $baseDate = $candidate;
                            if ($candidate === $authDue) {
                                break;
                            }
                        }

                        if ((! $lineUpdateDays || $lineUpdateDays <= 0 || ! $baseDate) && $authDue) {
                            $dueDates->push($authDue);
                        }

                        if ($baseDate) {
                            $rollingBaseDate = $baseDate;
                        }
                    }

                    foreach ([$readmissionDate, $reactivationDate] as $triggerDate) {
                        if ($triggerDate) {
                            $dueDates->push($triggerDate);
                        }
                    }
                    $dueDates = $dueDates->filter()->unique()->sort()->values();

                    $pending = $dueDates->filter(function ($dueDate) use ($dischargeDate, $closestCoverageDate) {
                        if ($dueDate && $dischargeDate && $dueDate > $dischargeDate) {
                            return false;
                        }
                        return ! $closestCoverageDate($dueDate);
                    })->values();

                    if ($pending->isEmpty() && $dueDates->isNotEmpty()) {
                        return [$noteType => 'Complete'];
                    }

                    return [$noteType => $pending->all()];
                }

                if ($lastDateString) {
                    return [$noteType => 'Complete'];
                }

                return [$noteType => null];
            });

            return [
                'client' => $client,
                'due_dates' => $dueDates,
            ];
        });

        $pdf = Pdf::loadView('clinical-notes.tracker_pdf', [
            'trackerRows' => $trackerRows,
            'noteTypeOptions' => $noteTypeOptions,
            'asOfDate' => now()->format('m/d/Y'),
        ])->setPaper('letter', 'landscape');

        return $pdf->download('clinical_note_tracker.pdf');
    }

    public function opIndividual()
    {
        $selectedDate = request('date', now()->toDateString());
        $serviceCode = ServiceCode::where('service_code', 'H0004')->firstOrFail();

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

        return view('clinical-notes.op_individual', compact(
            'clients',
            'selectedDate',
            'serviceCode',
            'clientOptions'
        ));
    }

    public function opIndividualStore(Request $request)
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

            return view('clinical-notes.op_individual_review', [
                'rows' => $reviewRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'attachments' => $attachments,
            ]);
        }

        $startTimes = collect($rows)->pluck('time_start')->filter();
        $endTimes = collect($rows)->pluck('time_end')->filter();


        $submission = AttendanceSubmission::create([
            'type' => 'clinical_op_individual',
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
            ClinicalNote::create([
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
            ->route('clinical-notes.index')
            ->with('success', 'Clinical OP Individual submitted.');
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
            $clientQuery = Client::with(['apartment.house'])
                ->whereDate('starting_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('discharge_date')
                        ->orWhereDate('discharge_date', '>=', $selectedDate);
                });

            $groupIds = collect();
            if ($selectedGroupId === 'all') {
                $groupIds = ClientGroup::where('level_of_care_id', optional($level)->id)
                    ->pluck('id');
            }

            $clientQuery->whereHas('levelOfCareHistory', function ($query) use ($selectedDate) {
                $query->where('start_date', '<=', $selectedDate)
                    ->where(function ($q) use ($selectedDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                    });
            });

            if ($selectedGroupId === 'all') {
                $clientQuery->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $groupIds) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->whereIn('client_group_id', $groupIds);
                });
            } else {
                $clientQuery->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $selectedGroupId) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->where('client_group_id', $selectedGroupId);
                });
            }

            $clients = $clientQuery
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

        return view('clinical-notes.group_therapy', compact(
            'clients',
            'selectedDate',
            'levels',
            'selectedLevel',
            'groups',
            'selectedGroupId',
            'filteredServiceCodes',
            'selectedServiceCodeId',
            'levelOptions',
            'allGroups',
            'serviceCodeOptions'
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
            'group_id' => ['required', 'string', function ($attribute, $value, $fail) {
                if ($value === 'all') {
                    return;
                }

                if (! ClientGroup::whereKey($value)->exists()) {
                    $fail('The selected group is invalid.');
                }
            }],
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
        $completeClientIds = collect($attendanceRows)
            ->filter(fn ($data) => ! empty($data['complete']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($completeClientIds->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please select at least one client before submitting.');
        }

        $levelOfCare = LevelOfCare::where('level_of_care', $validated['level_of_care'])->first();

        if (! $request->boolean('confirm')) {
            $completeClients = Client::whereIn('id', $completeClientIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            $serviceCode = ServiceCode::find($validated['service_code_id']);
            $groupName = $validated['group_id'] === 'all'
                ? 'All Groups'
                : ClientGroup::whereKey($validated['group_id'])->value('name');

            return view('clinical-notes.group_therapy_review', [
                'completeClients' => $completeClients,
                'attendanceRows' => $attendanceRows,
                'meta' => $validated,
                'serviceCode' => $serviceCode,
                'levelOfCareName' => $levelOfCare?->display_name ?? $validated['level_of_care'],
                'groupName' => $groupName ?? $validated['group_id'],
                'attachments' => $attachments,
            ]);
        }

        $submission = AttendanceSubmission::create([
            'type' => 'clinical_group_therapy',
            'submission_date' => now()->toDateString(),
            'service_date' => $validated['service_date'],
            'service_code_id' => $validated['service_code_id'],
            'level_of_care_id' => $levelOfCare?->id,
            'client_group_id' => $validated['group_id'] === 'all' ? null : $validated['group_id'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'total_attendance' => $completeClientIds->count(),
            'submitted_by' => auth()->id(),
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        foreach ($attendanceRows as $clientId => $data) {
            $complete = ! empty($data['complete']);

            if ($complete) {
                ClinicalNote::create([
                    'client_id' => $clientId,
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $validated['service_code_id'],
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                    'units' => 1,
                    'remarks' => null,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }
        }

        return redirect()
            ->route('clinical-notes.index')
            ->with('success', 'Clinical notes submitted.');
    }

    public function showSubmission(AttendanceSubmission $submission)
    {
        $noteRows = ClinicalNote::with(['client', 'serviceCode'])
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? '']);

        return view($this->clinicalSubmissionView($submission, 'show'), compact('submission', 'noteRows'));
    }

    public function editSubmission(AttendanceSubmission $submission)
    {
        if ($submission->type !== 'clinical_group_therapy') {
            $noteRows = ClinicalNote::with(['client', 'serviceCode'])
                ->where('attendance_submission_id', $submission->id)
                ->get()
                ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? ''])
                ->values();

            $clientsList = Client::active()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'last_name', 'first_name']);

            $viewData = compact('submission', 'noteRows', 'clientsList');
            if ($submission->type === 'clinical_prod_sheet') {
                $viewData['noteTypeOptions'] = $this->productivityNoteTypeOptions();
            }

            return view($this->clinicalSubmissionView($submission, 'edit'), $viewData);
        }

        $submission->load(['levelOfCare', 'clientGroup', 'serviceCode']);
        $selectedDate = request('date', optional($submission->service_date)->toDateString());
        $levels = LevelOfCare::orderBy('level_of_care')->get();
        $selectedLevel = request('level_of_care') ?? $submission->levelOfCare?->level_of_care;
        $selectedGroupId = request('group_id') ?? ($submission->client_group_id ? (string) $submission->client_group_id : 'all');
        $selectedServiceCodeId = request('service_code_id', (string) $submission->service_code_id);
        $selectedTimeStart = request('time_start', $submission->time_start ? substr((string) $submission->time_start, 0, 5) : '');
        $selectedTimeEnd = request('time_end', $submission->time_end ? substr((string) $submission->time_end, 0, 5) : '');
        $selectedRemarks = request('remarks', $submission->remarks);
        $levelId = $selectedLevel ? LevelOfCare::where('level_of_care', $selectedLevel)->value('id') : null;

        $levelOptions = $levels->map(fn ($level) => [
            'id' => $level->id,
            'code' => $level->level_of_care,
        ])->values();
        $allGroups = ClientGroup::orderBy('name')->get(['id', 'name', 'level_of_care_id']);
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
        $groups = $levelId
            ? ClientGroup::where('level_of_care_id', $levelId)->orderBy('name')->get()
            : collect();

        if ($selectedLevel) {
            $level = LevelOfCare::where('level_of_care', $selectedLevel)->first();
            $filteredServiceCodes = $level?->serviceCodes()
                ->orderBy('service_code')
                ->get(['service_codes.id', 'service_codes.friendly_name', 'service_codes.service_type'])
                ->filter(fn ($code) => strtolower((string) $code->service_type) === 'group')
                ->values() ?? collect();
        }

        $clients = collect();
        if ($selectedDate && $selectedLevel && $selectedGroupId) {
            $clientQuery = Client::with(['apartment.house'])
                ->whereDate('starting_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('discharge_date')
                        ->orWhereDate('discharge_date', '>=', $selectedDate);
                });

            $groupIds = collect();
            if ($selectedGroupId === 'all' && $levelId) {
                $groupIds = ClientGroup::where('level_of_care_id', $levelId)->pluck('id');
            }

            $clientQuery->whereHas('levelOfCareHistory', function ($query) use ($selectedDate) {
                $query->where('start_date', '<=', $selectedDate)
                    ->where(function ($q) use ($selectedDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                    });
            });

            if ($selectedGroupId === 'all') {
                if ($groupIds->isNotEmpty()) {
                    $clientQuery->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $groupIds) {
                        $query->whereDate('start_date', '<=', $selectedDate)
                            ->where(function ($query) use ($selectedDate) {
                                $query->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $selectedDate);
                            })
                            ->whereIn('client_group_id', $groupIds);
                    });
                }
            } else {
                $clientQuery->whereHas('clientGroupHistory', function ($query) use ($selectedDate, $selectedGroupId) {
                    $query->whereDate('start_date', '<=', $selectedDate)
                        ->where(function ($query) use ($selectedDate) {
                            $query->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $selectedDate);
                        })
                        ->where('client_group_id', $selectedGroupId);
                });
            }

            $clients = $clientQuery
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

        $noteRows = ClinicalNote::with('client')
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->keyBy('client_id');

        $groupClientIds = $clients->pluck('id')->values();
        $outOfGroupRows = ClinicalNote::with('client')
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

        return view($this->clinicalSubmissionView($submission, 'edit'), compact(
            'submission',
            'levels',
            'selectedDate',
            'selectedLevel',
            'selectedGroupId',
            'selectedServiceCodeId',
            'selectedTimeStart',
            'selectedTimeEnd',
            'selectedRemarks',
            'groups',
            'filteredServiceCodes',
            'levelOptions',
            'serviceCodeOptions',
            'allGroups',
            'clients',
            'noteRows',
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

        if ($submission->type === 'clinical_group_therapy') {
            $rules['level_of_care'] = ['required', 'string'];
            $rules['group_id'] = ['required', 'string', function ($attribute, $value, $fail) {
                if ($value === 'all') {
                    return;
                }

                if (! ClientGroup::whereKey($value)->exists()) {
                    $fail('The selected group is invalid.');
                }
            }];
            $rules['service_date'] = ['required', 'date'];
            $rules['service_code_id'] = ['required', 'exists:service_codes,id'];
            $rules['time_start'] = ['required', 'date_format:H:i'];
            $rules['time_end'] = ['required', 'date_format:H:i'];
            $rules['attendance'] = ['array'];
        } elseif ($submission->type === 'clinical_prod_sheet') {
            $rules['rows'] = ['required', 'array', 'min:1'];
            $rules['rows.*.id'] = ['required', 'integer', 'exists:clinical_notes,id'];
            $rules['rows.*.client_id'] = ['required', 'integer', 'exists:clients,id'];
            $rules['rows.*.service_date'] = ['required', 'date'];
            $rules['rows.*.time_start'] = ['required', 'date_format:H:i'];
            $rules['rows.*.time_end'] = ['required', 'date_format:H:i'];
            $rules['rows.*.units'] = ['required', 'integer', 'min:0'];
            $rules['rows.*.note_type'] = ['required', 'string'];
            $rules['rows.*.remarks'] = ['nullable', 'string'];
        } else {
            $rules['rows'] = ['required', 'array', 'min:1'];
            $rules['rows.*.id'] = ['required', 'integer', 'exists:clinical_notes,id'];
            $rules['rows.*.client_id'] = ['required', 'integer', 'exists:clients,id'];
            $rules['rows.*.service_date'] = ['required', 'date'];
            $rules['rows.*.time_start'] = ['required', 'date_format:H:i'];
            $rules['rows.*.time_end'] = ['required', 'date_format:H:i'];
            $rules['rows.*.units'] = ['required', 'integer', 'min:0'];
            $rules['rows.*.remarks'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        $attachments = $this->storeAttachments($request, $submission->attachments ?? []);

        if (! $request->boolean('confirm')) {
            if ($submission->type === 'clinical_group_therapy') {
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

                $presentClients = Client::whereIn('id', $presentClientIds)
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();
                $levelOfCare = LevelOfCare::where('level_of_care', $validated['level_of_care'])->first();
                $groupName = $validated['group_id'] === 'all'
                    ? 'All Groups'
                    : ClientGroup::whereKey($validated['group_id'])->value('name');

                return view($this->clinicalSubmissionView($submission, 'review'), [
                    'submission' => $submission,
                    'remarks' => $validated['remarks'] ?? null,
                    'attachments' => $attachments,
                    'isGroupTherapy' => true,
                    'attendanceRows' => $attendanceRows,
                    'presentClients' => $presentClients,
                    'levelOfCareName' => $levelOfCare?->display_name ?? $validated['level_of_care'],
                    'groupName' => $groupName ?? $validated['group_id'],
                    'level_of_care' => $validated['level_of_care'],
                    'group_id' => $validated['group_id'],
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $validated['service_code_id'],
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                ]);
            }

            $rows = collect($validated['rows'])->map(function ($row) {
                $client = Client::find($row['client_id']);
                $note = ClinicalNote::with('serviceCode')->find($row['id']);

                return (object) [
                    'id' => $row['id'],
                    'client' => $client,
                    'serviceCode' => $note?->serviceCode,
                    'service_date' => $row['service_date'],
                    'time_start' => $row['time_start'],
                    'time_end' => $row['time_end'],
                    'units' => $row['units'],
                    'remarks' => $row['remarks'] ?? null,
                    'note_type' => $row['note_type'] ?? null,
                ];
            });

            return view($this->clinicalSubmissionView($submission, 'review'), [
                'submission' => $submission,
                'noteRows' => $rows,
                'editRows' => $validated['rows'],
                'remarks' => $validated['remarks'] ?? null,
                'attachments' => $attachments,
            ]);
        }

        $submission->update([
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachments ?: null,
        ]);

        if ($submission->type === 'clinical_group_therapy') {
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

            $levelOfCare = LevelOfCare::where('level_of_care', $validated['level_of_care'])->first();
            $clientGroupId = $validated['group_id'] === 'all' ? null : $validated['group_id'];

            $submission->update([
                'service_date' => $validated['service_date'],
                'service_code_id' => $validated['service_code_id'],
                'time_start' => $validated['time_start'],
                'time_end' => $validated['time_end'],
                'level_of_care_id' => $levelOfCare?->id,
                'client_group_id' => $clientGroupId,
                'total_attendance' => $presentClientIds->count(),
            ]);

            $existingRows = ClinicalNote::where('attendance_submission_id', $submission->id)->get()->keyBy('client_id');

            foreach ($presentClientIds as $clientId) {
                if ($existingRows->has($clientId)) {
                    $existingRows->get($clientId)->update([
                        'service_date' => $validated['service_date'],
                        'service_code_id' => $validated['service_code_id'],
                        'time_start' => $validated['time_start'],
                        'time_end' => $validated['time_end'],
                        'marked_by' => auth()->id(),
                    ]);
                    continue;
                }

                ClinicalNote::create([
                    'client_id' => $clientId,
                    'service_date' => $validated['service_date'],
                    'service_code_id' => $validated['service_code_id'],
                    'time_start' => $validated['time_start'],
                    'time_end' => $validated['time_end'],
                    'units' => 1,
                    'remarks' => null,
                    'marked_by' => auth()->id(),
                    'attendance_submission_id' => $submission->id,
                ]);
            }

            $existingRows->keys()
                ->diff($presentClientIds)
                ->each(function ($clientId) use ($submission) {
                    ClinicalNote::where('attendance_submission_id', $submission->id)
                        ->where('client_id', $clientId)
                        ->delete();
                });
        } else {
            $rows = collect($validated['rows']);
            $rowIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->values();

            $notes = ClinicalNote::where('attendance_submission_id', $submission->id)
                ->whereIn('id', $rowIds)
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $note = $notes->get((int) $row['id']);
                if (! $note) {
                    continue;
                }

                $note->update([
                    'client_id' => $row['client_id'],
                    'service_date' => $row['service_date'],
                    'time_start' => $row['time_start'],
                    'time_end' => $row['time_end'],
                    'units' => $row['units'],
                    'note_type' => $row['note_type'] ?? $note->note_type,
                    'remarks' => $row['remarks'] ?? null,
                    'marked_by' => auth()->id(),
                ]);
            }

            $submission->update([
                'service_date' => $rows->min('service_date'),
                'time_start' => $rows->min('time_start'),
                'time_end' => $rows->max('time_end'),
                'total_attendance' => $rows->count(),
            ]);
        }

        return redirect()
            ->route('clinical-notes.index')
            ->with('success', 'Clinical notes submission updated.');
    }

    public function destroySubmission(AttendanceSubmission $submission)
    {
        ClinicalNote::where('attendance_submission_id', $submission->id)->delete();
        $submission->delete();

        return redirect()
            ->route('clinical-notes.index')
            ->with('success', 'Clinical notes submission deleted.');
    }

    public function downloadSubmission(AttendanceSubmission $submission)
    {
        $noteRows = ClinicalNote::with(['client', 'serviceCode'])
            ->where('attendance_submission_id', $submission->id)
            ->get()
            ->sortBy(fn ($row) => [$row->client->last_name ?? '', $row->client->first_name ?? '']);

        $pdf = Pdf::loadView($this->clinicalSubmissionView($submission, 'pdf'), [
            'submission' => $submission,
            'noteRows' => $noteRows,
        ])->setPaper('letter');

        return $pdf->download('clinical_notes_submission_' . $submission->id . '.pdf');
    }

    public function downloadAttachment(AttendanceSubmission $submission, string $filename)
    {
        $attachments = $submission->attachments ?? [];
        if (! in_array($filename, $attachments, true)) {
            abort(404, 'File not found.');
        }

        $path = 'attachments/clinical-notes/' . $filename;
        if (! Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path, $filename);
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
            $file->storeAs('attachments/clinical-notes', $uniqueFileName);
            $attachments[] = $uniqueFileName;
        }

        return array_values(array_unique($attachments));
    }

    protected function clinicalSubmissionView(AttendanceSubmission $submission, string $view): string
    {
        return match ($submission->type) {
            'clinical_group_therapy' => "clinical-notes.group-therapy.{$view}",
            'clinical_op_individual' => "clinical-notes.op-individual.{$view}",
            'clinical_prod_sheet' => "clinical-notes.productivity-sheet.{$view}",
            default => "clinical-notes.productivity-sheet.{$view}",
        };
    }

    protected function productivityNoteTypeOptions(): \Illuminate\Support\Collection
    {
        return collect([
            'Auth Initial Package',
            'Assessments',
            'Biopsycosocial',
            'Discharge Plan',
            'Discharge Summary',
            'Auth Update Package',
            'Individual Treatment Plan (ITP) Review/Update',
            'Progress Note',
            'Quick Note',
            'Toxicology Review',
            'Transition Note',
            'Other',
        ])->sort()->values();
    }
}
