<?php

namespace App\Http\Controllers;

use App\Models\AuthLineOfService;
use App\Models\Authorization;
use App\Models\Client;
use App\Models\LevelOfCare;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    public function index()
    {
        $authorizations = Authorization::with(['client', 'levelOfCare'])->get();

        return view('authorizations.index', compact('authorizations'));
    }

    public function create()
    {
        $clients = Client::all();

        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();

        return view('authorizations.create', compact('clients', 'levelOfCares'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            // 'submission_date' => 'required|date',
            'auth_starting_date' => 'required|date',
            // 'ending_date' => 'required|date',
            // 'units' => 'required|integer',
            // 'status' => 'required|in:approved,denied,in-process',
            // 'diagnosis_code' => 'required|string',
            'level_of_care' => 'required|exists:level_of_cares,id',
            'auth_number' => 'nullable|string',
            'remarks' => 'nullable|string',
            'attachment.*' => 'nullable|file|max:2048',
        ]);
        // $validated['auth_starting_date'] = $validated['starting_date'];
        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $originalName = $file->getClientOriginalName();
                $timestamp = now()->timestamp;
                $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME).'_'.$timestamp.'.'.$file->getClientOriginalExtension();
                $file->storeAs('attachments', $uniqueFileName);
                $attachments[] = $uniqueFileName;
            }
        }
        $validated['attachment'] = json_encode($attachments);

        $authorization = Authorization::create($validated);
        $request['auth_id'] = $authorization->id;

        // AuthLineOfService::create($request->all());
        return redirect()->route('authorizations.index')->with('success', 'Authorization created successfully.');
    }

    public function show(Authorization $authorization)
    {
        return view('authorizations.show', compact('authorization'));
    }

    public function edit(Authorization $authorization)
    {
        $clients = Client::all();

        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();

        return view('authorizations.edit', compact('authorization', 'clients', 'levelOfCares'));
    }

    public function update(Request $request, Authorization $authorization)
    {
        $validated = $request->validate([
            'level_of_care' => 'required|exists:level_of_cares,id',
            'auth_number' => 'nullable|string',
            'auth_starting_date' => 'required|date',
            'auth_ending_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'attachment.*' => 'nullable|file|max:2048',
        ]);

        // Handle new file uploads
        $attachments = json_decode($authorization->attachment, true) ?? [];
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $originalName = $file->getClientOriginalName();
                $timestamp = now()->timestamp;
                $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME).'_'.$timestamp.'.'.$file->getClientOriginalExtension();
                $file->storeAs('attachments', $uniqueFileName);
                $attachments[] = $uniqueFileName;
            }
        }
        $validated['attachment'] = json_encode($attachments);

        $authorization->update($validated);

        return redirect()->route('authorizations.index')->with('success', 'Authorization updated successfully.');
    }

    public function destroy(Authorization $authorization)
    {
        $authorization->delete();

        return redirect()->route('authorizations.index')->with('success', 'Authorization deleted successfully.');
    }

    public function tracker()
    {
        $clients = Client::with(['authorizations.lineOfServices', 'hospitalizations'])->get();
        $levels = LevelOfCare::orderBy('level_of_care')->get();
        $today = now()->toDateString();

        $resolveDueDate = function ($auth) {
            $lines = $auth->lineOfServices->sortByDesc('starting_date');

            if ($lines->isEmpty()) {
                return $auth->auth_starting_date ? Carbon::parse($auth->auth_starting_date) : null;
            }

            $latestLine = $lines->firstWhere('type', '!=', 'pause');
            $latestPause = $lines->firstWhere('type', 'pause');

            if ($latestLine && $latestLine->ending_date) {
                $baseDue = Carbon::parse($latestLine->ending_date)->addDay();

                if ($latestPause) {
                    $pauseStart = Carbon::parse($latestPause->starting_date);
                    $pauseEnd = $latestPause->ending_date ? Carbon::parse($latestPause->ending_date) : null;

                    if (! $pauseEnd) {
                        return $baseDue->gte($pauseStart) ? null : $baseDue;
                    }

                    if ($baseDue->betweenIncluded($pauseStart, $pauseEnd)) {
                        return $pauseEnd->copy()->addDay();
                    }
                }

                return $baseDue;
            }

            return $auth->auth_ending_date ? Carbon::parse($auth->auth_ending_date) : null;
        };

        $activeHospitalizations = $clients->mapWithKeys(function ($client) use ($today) {
            $hospitalization = $client->hospitalizations
                ->filter(function ($record) use ($today) {
                    return $record->start_date <= $today
                        && (is_null($record->end_date) || $record->end_date >= $today);
                })
                ->sortByDesc('start_date')
                ->first();

            return [$client->id => $hospitalization];
        });

        $clients = $clients->filter(function ($client) use ($activeHospitalizations, $resolveDueDate) {
            $hospitalization = $activeHospitalizations->get($client->id);
            if (! $hospitalization) {
                return true;
            }

            return $client->authorizations->contains(function ($auth) use ($hospitalization, $resolveDueDate) {
                $dueDate = $resolveDueDate($auth);

                return $dueDate && $dueDate->lte(Carbon::parse($hospitalization->start_date));
            });
        })->values();

        $processData = function ($auth) {
            $lines = $auth->lineOfServices->sortByDesc('starting_date');

            $latestLine = $lines->firstWhere('type', '!=', 'pause');
            $latestPause = $lines->firstWhere('type', 'pause');
            $due = null;

            if ($lines->isEmpty()) {
                $due = \Carbon\Carbon::parse($auth->auth_starting_date)->format('m/d/Y');
            } elseif ($latestLine && $latestLine->ending_date) {
                $baseDue = \Carbon\Carbon::parse($latestLine->ending_date)->addDay();

                if ($latestPause) {
                    $pauseStart = \Carbon\Carbon::parse($latestPause->starting_date);
                    $pauseEnd = $latestPause->ending_date ? \Carbon\Carbon::parse($latestPause->ending_date) : null;

                    if (! $pauseEnd) {
                        $due = $baseDue >= $pauseStart ? 'Pause' : $baseDue->format('m/d/Y');
                    } else {
                        if ($baseDue < $pauseStart || $baseDue >= $pauseEnd) {
                            $due = $baseDue->format('m/d/Y');
                        } else {
                            $newDue = $pauseEnd->copy()->addDay();
                            $due = $newDue->format('m/d/Y');
                        }
                    }
                } else {
                    $due = $baseDue->format('m/d/Y');
                }
            }

            return [
                'client' => $auth->client,
                'latest_auth' => $auth,
                'latest_line' => $latestLine,
                'latest_pause' => $latestPause,
                'due' => $due,
                'redetermination_date' => $auth->client->redetermination_date,
            ];
        };

        $trackerDataByLevel = [];
        $excludedIds = [];

        foreach ($levels as $level) {
            $trackerData = $clients->flatMap(function ($client) use ($processData, $level, $activeHospitalizations, $resolveDueDate) {
                return $client->authorizations
                    ->where('level_of_care', $level->id)
                    ->sortByDesc('auth_starting_date')
                    ->map(function ($auth) use ($processData, $activeHospitalizations, $resolveDueDate) {
                        $data = $processData($auth);
                        $hospitalization = $activeHospitalizations->get($auth->client_id);

                        if (! $hospitalization) {
                            return $data;
                        }

                        $dueDate = $resolveDueDate($auth);

                        return $dueDate && $dueDate->lte(Carbon::parse($hospitalization->start_date)) ? $data : null;
                    });
            })->filter(function ($data) {
                if (! $data) {
                    return false;
                }

                $auth = $data['latest_auth'];
                $line = $data['latest_line'];

                return $auth && (! $auth->auth_ending_date || ! $line || ($line && $line->ending_date < $auth->auth_ending_date));
            })->values();

            $trackerDataByLevel[$level->id] = $trackerData;
            $excludedIds = array_merge($excludedIds, $trackerData->pluck('client.id')->filter()->all());
        }

        $otherClients = $clients->filter(function ($client) use ($excludedIds) {
            return $client->status === 'active' && ! in_array($client->id, $excludedIds);
        });

        return view('authorizations.tracker', compact('levels', 'trackerDataByLevel', 'otherClients'));
    }

    public function deniedAuthsTracker()
    {
        $activeClients = Client::with([
            'authorizations' => function ($query) {
                $query->with(['lineOfServices', 'levelOfCare'])->orderByDesc('auth_starting_date');
            },
        ])
            ->where('status', 'active')
            ->get();

        $deniedLines = $activeClients
            ->flatMap(function ($client) {
                return $client->authorizations->flatMap(function ($authorization) use ($client) {
                    return $authorization->lineOfServices
                        ->filter(function ($line) {
                            return strtolower((string) $line->status) === 'denied';
                        })
                        ->map(function ($line) use ($client, $authorization) {
                            return [
                                'client' => $client,
                                'authorization' => $authorization,
                                'line' => $line,
                            ];
                        });
                });
            })
            ->sortByDesc(function ($item) {
                return $item['line']->submission_date
                    ?? $item['line']->starting_date
                    ?? $item['line']->created_at;
            })
            ->values();

        $deniedWithRemarks = $deniedLines
            ->filter(function ($item) {
                return ! empty(trim((string) $item['line']->remarks));
            })
            ->values();

        $deniedWithoutRemarks = $deniedLines
            ->filter(function ($item) {
                return empty(trim((string) $item['line']->remarks));
            })
            ->values();

        return view('authorizations.denied-auths-tracker', compact('deniedWithRemarks', 'deniedWithoutRemarks'));
    }


    public function pendingAuthsTracker()
    {
        $activeClients = Client::with([
            'authorizations' => function ($query) {
                $query->with(['lineOfServices', 'levelOfCare'])->orderByDesc('auth_starting_date');
            },
        ])
            ->where('status', 'active')
            ->get();

        $pendingLines = $activeClients
            ->flatMap(function ($client) {
                return $client->authorizations->flatMap(function ($authorization) use ($client) {
                    return $authorization->lineOfServices
                        ->filter(function ($line) {
                            $status = strtolower(str_replace('_', '-', trim((string) $line->status)));

                            return in_array($status, ['in-process', 'in process'], true);
                        })
                        ->map(function ($line) use ($client, $authorization) {
                            return [
                                'client' => $client,
                                'authorization' => $authorization,
                                'line' => $line,
                            ];
                        });
                });
            })
            ->sortByDesc(function ($item) {
                return $item['line']->submission_date
                    ?? $item['line']->starting_date
                    ?? $item['line']->created_at;
            })
            ->values();

        $pendingWithRemarks = $pendingLines
            ->filter(function ($item) {
                return ! empty(trim((string) $item['line']->remarks));
            })
            ->values();

        $pendingWithoutRemarks = $pendingLines
            ->filter(function ($item) {
                return empty(trim((string) $item['line']->remarks));
            })
            ->values();

        return view('authorizations.pending-auths-tracker', compact('pendingWithRemarks', 'pendingWithoutRemarks'));
    }
}

