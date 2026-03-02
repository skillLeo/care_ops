<?php

namespace App\Services;

use App\Models\ClinicalNote;
use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClinicalNoteTrackerService
{
    public const CUTOFF_DATE = '2026-02-09';

    public function buildTrackerRows(): array
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

        return [
            'trackerRows' => $trackerRows,
            'noteTypeOptions' => $noteTypeOptions,
        ];
    }

    public function filterRowsWithPending(Collection $trackerRows): Collection
    {
        $cutoff = \Carbon\Carbon::parse(self::CUTOFF_DATE);

        return $trackerRows->filter(function ($row) use ($cutoff) {
            return $row['due_dates']->contains(function ($value) use ($cutoff) {
                if ($value === null || $value === 'Complete') {
                    return false;
                }
                if (is_array($value)) {
                    return collect($value)->filter(function ($date) use ($cutoff) {
                        return \Carbon\Carbon::parse($date)->gte($cutoff);
                    })->isNotEmpty();
                }
                if (is_string($value)) {
                    return \Carbon\Carbon::parse($value)->gte($cutoff);
                }
                return true;
            });
        })->values();
    }

    public function groupByCounselor(Collection $trackerRows): array
    {
        $unassignedRows = $trackerRows->filter(fn ($row) => ! $row['client']->counselor)->values();
        $counselorGroups = $trackerRows->filter(fn ($row) => $row['client']->counselor)
            ->groupBy(fn ($row) => $row['client']->counselor->id);

        return [
            'unassignedRows' => $unassignedRows,
            'counselorGroups' => $counselorGroups,
        ];
    }
}
