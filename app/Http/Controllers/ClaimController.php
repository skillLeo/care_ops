<?php

namespace App\Http\Controllers;

use App\Models\Attendance2026;
use App\Models\Check;
use App\Models\Claim;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class ClaimController extends Controller
{
    public function index()
    {
        $claims = Claim::withCount('lines')->get();
        $openChecks = Check::where('open', true)->get();
        return view('claims.index', compact('claims', 'openChecks'));
    }

    public function create()
    {
        $clients = Client::all();
        $checks = Check::where('open', true)->get();
        return view('claims.create', compact('clients', 'checks'));
    }

    public function calendar($id, Request $request)
    {
        $checks = Check::where('open', true)->get();
        $clients = Client::all();
        $client = Client::findOrFail($id);
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $levelOfCareHistory = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->orderBy('start_date')
            ->get();

        $start = \Carbon\Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = \Carbon\Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $sessionTypes = ['group', 'peer_group', 'peer_individual'];
        $attendances = [];

        foreach ($sessionTypes as $type) {
            $attendances[$type] = $client->attendances()
                // ->whereBetween('service_date', [$start, $end])
                ->where('session_type', $type)
                ->get()
                ->keyBy(fn($a) => $a->service_date->format('Y-m-d'));
        }

        $claimLines = $client->claims()
            ->with('lines')
            ->get()
            ->flatMap(fn ($claim) => $claim->lines)
            ->filter(function ($line) use ($start, $end) {
                $serviceDate = Carbon::parse($line->service_date);
                return $serviceDate->betweenIncluded($start, $end);
            })
            ->groupBy(fn ($line) => Carbon::parse($line->service_date)->format('Y-m-d'));



        return view('claims.calendar', [
            'client' => $client,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $levelOfCareHistory,
            'attendances' => $attendances,
            'claimLines' => $claimLines,
            'checks' => $checks,
        ]);
    }

    public function calendar2026($id, Request $request)
    {
        $checks = Check::where('open', true)->get();
        $client = Client::findOrFail($id);
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $levelOfCareHistory = $client->levelOfCareHistory()
            ->with('levelOfCare')
            ->orderBy('start_date')
            ->get();

        $start = Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $attendanceRows = Attendance2026::with(['serviceCode.prices'])
            ->where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->get();

        $attendanceByDate = $this->buildAttendance2026Map($attendanceRows);
        $hospitalizations = $client->hospitalizations()->orderBy('start_date')->get();

        $claimLines = $client->claims()
            ->with('lines')
            ->get()
            ->flatMap(fn ($claim) => $claim->lines)
            ->filter(function ($line) use ($start, $end) {
                $serviceDate = Carbon::parse($line->service_date);
                return $serviceDate->betweenIncluded($start, $end);
            })
            ->groupBy(fn ($line) => Carbon::parse($line->service_date)->format('Y-m-d'));

        $processedClaims = $this->buildProcessedClaimMap($claimLines);

        return view('claims.calendar_2026', [
            'client' => $client,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $levelOfCareHistory,
            'attendance2026' => $attendanceByDate,
            'claimLines' => $claimLines,
            'processedClaims' => $processedClaims,
            'checks' => $checks,
            'hospitalizations' => $hospitalizations,
        ]);
    }

    public function calendarBody($id, Request $request)
    {
        $client = Client::findOrFail($id);
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $start = Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $claimLines = $client->claims()
            ->with('lines')
            ->get()
            ->flatMap(fn ($claim) => $claim->lines)
            ->filter(function ($line) use ($start, $end) {
                $serviceDate = Carbon::parse($line->service_date);
                return $serviceDate->betweenIncluded($start, $end);
            })
            ->groupBy(fn ($line) => Carbon::parse($line->service_date)->format('Y-m-d'));

        $sessionTypes = ['group', 'peer_group', 'peer_individual'];
        $attendances = [];

        foreach ($sessionTypes as $type) {
            $attendances[$type] = $client->attendances()
                ->whereBetween('service_date', [$start, $end])
                ->where('session_type', $type)
                ->get()
                ->keyBy(fn($a) => $a->service_date->format('Y-m-d'));
        }

        return view('claims.calendar_body', [
            'client' => $client,
            'claimLines' => $claimLines,
            'attendances' => $attendances,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $client->levelOfCareHistory()->with('levelOfCare')->get(),
            'checks' => Check::where('open', true)->get(),
        ]);
    }

    public function calendarBody2026($id, Request $request)
    {
        $client = Client::findOrFail($id);
        $currentMonth = $request->input('month', now()->format('Y-m'));

        $start = Carbon::parse($currentMonth)->startOfMonth()->startOfWeek();
        $end = Carbon::parse($currentMonth)->endOfMonth()->endOfWeek();

        $attendanceRows = Attendance2026::with(['serviceCode.prices'])
            ->where('client_id', $client->id)
            ->whereBetween('service_date', [$start, $end])
            ->get();

        $attendanceByDate = $this->buildAttendance2026Map($attendanceRows);
        $hospitalizations = $client->hospitalizations()->orderBy('start_date')->get();

        $claimLines = $client->claims()
            ->with('lines')
            ->get()
            ->flatMap(fn ($claim) => $claim->lines)
            ->filter(function ($line) use ($start, $end) {
                $serviceDate = Carbon::parse($line->service_date);
                return $serviceDate->betweenIncluded($start, $end);
            })
            ->groupBy(fn ($line) => Carbon::parse($line->service_date)->format('Y-m-d'));

        $processedClaims = $this->buildProcessedClaimMap($claimLines);

        return view('claims.calendar_body_2026', [
            'client' => $client,
            'claimLines' => $claimLines,
            'attendance2026' => $attendanceByDate,
            'processedClaims' => $processedClaims,
            'currentMonth' => $currentMonth,
            'levelOfCares' => $client->levelOfCareHistory()->with('levelOfCare')->get(),
            'checks' => Check::where('open', true)->get(),
            'hospitalizations' => $hospitalizations,
        ]);
    }

    private function buildAttendance2026Map($attendanceRows): array
    {
        $attendanceByDate = [];

        foreach ($attendanceRows as $row) {
            $date = $row->service_date?->format('Y-m-d');
            $code = $row->serviceCode?->service_code;
            $label = $row->serviceCode?->friendly_name;

            if (! $date || ! $code) {
                continue;
            }

            $units = $row->units ?? 1;

            if (! isset($attendanceByDate[$date][$code])) {
                $attendanceByDate[$date][$code] = [
                    'units' => 0,
                    'price' => $this->resolveServiceCodePrice($row),
                    'label' => $label,
                ];
            }

            $attendanceByDate[$date][$code]['units'] += $units;
        }

        return $attendanceByDate;
    }

    private function resolveServiceCodePrice(Attendance2026 $attendance): ?float
    {
        $prices = $attendance->serviceCode?->prices ?? collect();

        if ($prices->isEmpty()) {
            return null;
        }

        $date = $attendance->service_date?->format('Y-m-d');

        $match = $prices
            ->sortByDesc('starting_date')
            ->first(function ($price) use ($date) {
                $starts = $price->starting_date?->format('Y-m-d');
                $ends = $price->ending_date?->format('Y-m-d');

                if (! $starts || $starts > $date) {
                    return false;
                }

                if ($ends && $ends < $date) {
                    return false;
                }

                return true;
            });

        return $match?->price ? (float) $match->price : null;
    }

    private function buildProcessedClaimMap($claimLines): array
    {
        $processedClaims = [];

        foreach ($claimLines as $date => $lines) {
            foreach ($lines as $line) {
                if ($line->check_id && $line->processed_amount > 0) {
                    $processedClaims[$date][$line->service_code] = true;
                }
            }
        }

        return $processedClaims;
    }


    public function store(Request $request)
    {

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'claim_number' => 'required',
            'carelon_claim_number' => 'nullable',
            'submission_date' => 'required|date',
            'attachments.*' => 'nullable|file|max:2048',
        ]);

        $attachments = $this->storeAttachments($request);

        $claim = Claim::create($request->only([
            'client_id', 'claim_number', 'carelon_claim_number',
            'submission_date', 'remarks'
        ]) + ['attachments' => $attachments ?: null]);

        foreach ($request->lines as $line) {
            $claim->lines()->create($line);
        }

        return redirect()->route('claims.show', $claim)->with('success', 'Claim created.');
    }

    public function show(Claim $claim)
    {
        $claim->load('client', 'lines.check');
        $checks = Check::where('open', true)->get();
        return view('claims.show', compact('claim', 'checks'));
    }

    public function edit(Claim $claim)
    {
        $claim->load('client', 'lines.check');
        $lineCheckIds = $claim->lines->pluck('check_id')->filter()->unique();
        $checks = Check::where('open', true)
            ->orWhereIn('id', $lineCheckIds)
            ->get();

        return view('claims.edit', compact('claim', 'checks'));
    }

    public function update(Request $request, Claim $claim)
    {
        $request->validate([
            'attachments.*' => 'nullable|file|max:2048',
        ]);

        $attachments = $this->storeAttachments($request, $claim->attachments ?? []);

        $claim->update([
            'submission_date' => $request->input('submission_date'),
            'remarks' => $request->input('remarks'),
            'attachments' => $attachments ?: null,
        ]);

        $claim->lines()->delete();
        foreach ($request->lines as $line) {
            $claim->lines()->create($line);
        }

        return redirect()->route('claims.index')->with('success', 'Claim updated.');
    }

    public function checkStatus($id)
    {
        $claim = Claim::findOrFail($id);

        // Step 1: Get OAuth Token
        $tokenResponse = Http::asForm()->post(env('AVAILITY_DEMO_TOKEN_URL'), [
            'grant_type' => 'client_credentials',
            'client_id' => env('AVAILITY_DEMO_CLIENT_ID'),
            'client_secret' => env('AVAILITY_DEMO_CLIENT_SECRET'),
            'scope' => 'hipaa'

        ]);


        if (!$tokenResponse->successful()) {
            return response()->json(['error' => 'Failed to get access token', 'details' => $tokenResponse->json()], 500);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Step 2: Build fake payload for demo claim (you can hardcode or use dummy values here)
        $payload = [
            "tradingPartnerServiceId" => env('AVAILITY_DEMO_PAYER_ID', 'AVAILITYDEMO'),
            "provider" => [
                "npi" => env('AVAILITY_DEMO_NPI', '1467560003'),
            ],
            "subscriber" => [
                "memberId" => "123456789",
            ],
            "patient" => [
                "firstName" => "John",
                "lastName" => "Doe",
                "dob" => "1970-01-01",
            ],
            "claim" => [
                "serviceDate" => "2024-04-01",
            ]
        ];

        // Step 3: Make Claim Status Request
        $claimStatusResponse = Http::withToken($accessToken)
            ->get(env('AVAILITY_DEMO_CLAIM_STATUS_URL'));

        if (!$claimStatusResponse->successful()) {
            return response()->json(['error' => 'Claim status request failed', 'details' => $claimStatusResponse->json()], 500);
        }

        // Show the demo response
        return response()->json($claimStatusResponse->json());
    }

    public function processAll(Claim $claim)
    {
        foreach ($claim->lines as $line) {
            $line->processed_amount = $line->billed_amount;
            $line->save();
        }
        return redirect()->route('claims.index')->with('success', 'All lines processed.');
    }

    public function addCheck(Claim $claim)
    {
        $check = Check::where('open', true)->first();
        if ($check) {
            foreach ($claim->lines as $line) {
                $line->check_id = $check->id;
                $line->save();
            }
            return redirect()->route('claims.index')->with('success', 'Check added to claim.');
        }

        return redirect()->route('claims.index')->with('error', 'No open check available.');
    }

    public function destroy(Claim $claim)
    {
        $claim->lines()->delete();
        $claim->delete();

        return redirect()->route('claims.index')->with('success', 'Claim deleted successfully.');
    }

    public function downloadAttachment(Claim $claim, string $filename)
    {
        $attachments = $claim->attachments ?? [];
        if (! in_array($filename, $attachments, true)) {
            abort(404, 'File not found.');
        }

        $path = 'attachments/claims/' . $filename;
        if (! Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path, $filename);
    }

    protected function storeAttachments(Request $request, array $existing = []): array
    {
        $attachments = $existing;

        if (! $request->hasFile('attachments')) {
            return $attachments;
        }

        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $timestamp = now()->timestamp;
            $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $file->storeAs('attachments/claims', $uniqueFileName);
            $attachments[] = $uniqueFileName;
        }

        return $attachments;
    }

}
