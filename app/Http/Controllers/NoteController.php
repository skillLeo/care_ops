<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Note;
use Illuminate\Http\Request;


class NoteController extends Controller
{
    public function batch(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $sessionType = $request->input('session_type');

        $clients = collect();
        $attendanceData = [];
        $noteData = [];

        if ($sessionType) {
            $clients = Client::whereDate('starting_date', '<=', $selectedDate)
                ->where(function ($q) use ($selectedDate) {
                    $q->whereNull('discharge_date')
                    ->orWhereDate('discharge_date', '>=', $selectedDate);
                })
                ->orderBy('last_name')
                ->get()
                ->filter(function ($client) use ($selectedDate) {
                    return $client->status === 'active';
                });

            foreach ($clients as $client) {
                $client->level_of_care = $client->levelOfCareHistory()
                    ->with('levelOfCare')
                    ->where('start_date', '<=', $selectedDate)
                    ->where(function ($q) use ($selectedDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
                    })
                    ->get()
                    ->map(fn ($level) => $level->levelOfCare?->display_name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            }

            $attendanceData = Attendance::whereDate('service_date', $selectedDate)
                ->where('session_type', $sessionType)
                ->get()
                ->keyBy('client_id');

            $noteData = Note::whereDate('service_date', $selectedDate)
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy('client_id');
            // dd($noteData->first()->units);
        }

        return view('notes.batch', compact('clients', 'attendanceData', 'noteData', 'selectedDate', 'sessionType'));
    }

    public function batchStore(Request $request)
    {
        $date = $request->input('service_date');

        $sessionType = $request->input('session_type', 'group');
        foreach ($request->notes as $clientId => $data) {
            if (isset($data['units'])) {
                Note::updateOrCreate(
                    [
                        'client_id' => $clientId,
                        'service_date' => $date,
                        'session_type' => $sessionType,
                    ],
                    [
                        'units' => $data['units'],
                        'status' => 'complete',
                    ]
                );
            }
        }

        return redirect()->route('notes.batch', [
            'date' => $date,
            'session_type' => $sessionType
        ])->with('success', 'Batch notes updated.');
    }

    public function show(Request $request, Client $client)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $sessionType = $request->input('session_type', 'group');

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $notes = Note::where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy(fn($n) => $n->service_date->format('Y-m-d'));

        $attendances = Attendance::where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->where('session_type', $sessionType)
            ->get()
            ->keyBy(fn($a) => $a->service_date->format('Y-m-d'));

        return view('notes.calendar', compact('client', 'month', 'year', 'sessionType', 'notes', 'attendances'));
    }


    public function store(Request $request, Client $client)
    {
        $sessionType = $request->input('session_type', 'group');

        foreach ($request->notes as $date => $data)
        {
            $units = intval($data['units']);

            if (!empty($data['id']))
            {
                Note::where('id', $data['id'])->update([
                    'units' => $units,
                    'status' => 'complete',
                ]);
            }
            elseif ($units > 0)
            {
                Note::create([
                    'client_id' => $client->id,
                    'service_date' => $date,
                    'session_type' => $sessionType,
                    'units' => $units,
                    'status' => 'complete',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Notes saved successfully.');
    }


}
