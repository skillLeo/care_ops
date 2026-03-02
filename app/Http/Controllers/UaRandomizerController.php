<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\House;
use App\Models\LevelOfCare;
use App\Models\UaRandomizer;
use App\Models\UaRandomizerClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UaRandomizerController extends Controller
{
    public function index()
    {
        $randomizers = UaRandomizer::with(['levelOfCare', 'house', 'randomizedBy'])
            ->withCount('clients')
            ->orderByDesc('generated_for_date')
            ->orderByDesc('id')
            ->get();

        return view('ua_randomizers.index', compact('randomizers'));
    }

    public function create()
    {
        $levelOfCares = LevelOfCare::orderBy('display_name')->get();
        $houses = House::orderBy('house_name')->get();

        return view('ua_randomizers.create', [
            'levelOfCares' => $levelOfCares,
            'houses' => $houses,
            'initialClients' => [],
            'initialSelected' => [],
        ]);
    }

    public function filter(Request $request)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'exclude_randomizer_id' => ['nullable', 'integer', 'exists:ua_randomizers,id'],
        ]);

        $payload = $this->buildFilteredClientsPayload(
            $data['generated_for_date'],
            Arr::get($data, 'level_of_care_id'),
            Arr::get($data, 'house_id'),
            Arr::get($data, 'exclude_randomizer_id')
        );

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'target_count' => ['required', 'integer', 'min:0'],
            'total_clients' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'selected_clients' => ['nullable', 'array'],
            'selected_clients.*' => ['integer', 'exists:clients,id'],
        ]);

        $randomizer = UaRandomizer::create([
            'level_of_care_id' => $data['level_of_care_id'] ?? null,
            'house_id' => $data['house_id'] ?? null,
            'generated_for_date' => $data['generated_for_date'],
            'randomized_at' => now(),
            'randomized_by' => $request->user()->id,
            'total_clients' => $data['total_clients'],
            'percentage' => $data['percentage'],
            'target_count' => $data['target_count'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $randomizer->clients()->sync($data['selected_clients'] ?? []);

        return redirect()->route('ua-randomizers.index')
            ->with('success', 'UA randomizer saved successfully.');
    }

    public function edit(UaRandomizer $uaRandomizer)
    {
        $levelOfCares = LevelOfCare::orderBy('display_name')->get();
        $houses = House::orderBy('house_name')->get();

        $payload = $this->buildFilteredClientsPayload(
            $uaRandomizer->generated_for_date->toDateString(),
            $uaRandomizer->level_of_care_id,
            $uaRandomizer->house_id,
            $uaRandomizer->id
        );

        return view('ua_randomizers.edit', [
            'randomizer' => $uaRandomizer->load('clients'),
            'levelOfCares' => $levelOfCares,
            'houses' => $houses,
            'initialClients' => $payload['clients'],
            'initialSelected' => $uaRandomizer->clients->pluck('id'),
        ]);
    }

    public function show(UaRandomizer $uaRandomizer)
    {
        $uaRandomizer->load([
            'clients' => function ($query) {
                $query->orderBy('last_name')->orderBy('first_name');
            },
            'levelOfCare',
            'house',
            'randomizedBy',
        ]);

        return view('ua_randomizers.show', [
            'randomizer' => $uaRandomizer,
        ]);
    }

    public function viewPdf(UaRandomizer $uaRandomizer)
    {
        $uaRandomizer = $this->loadRandomizerRelations($uaRandomizer);
        $fileName = $this->buildPdfFileName($uaRandomizer);
        $pdf = $this->buildRandomizerPdf($uaRandomizer);

        return $pdf->stream($fileName);
    }

    public function download(UaRandomizer $uaRandomizer)
    {
        $uaRandomizer = $this->loadRandomizerRelations($uaRandomizer);
        $fileName = $this->buildPdfFileName($uaRandomizer);
        $pdf = $this->buildRandomizerPdf($uaRandomizer);

        return $pdf->download($fileName);
    }

    public function update(Request $request, UaRandomizer $uaRandomizer)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'target_count' => ['required', 'integer', 'min:0'],
            'total_clients' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'selected_clients' => ['nullable', 'array'],
            'selected_clients.*' => ['integer', 'exists:clients,id'],
        ]);

        $uaRandomizer->update([
            'level_of_care_id' => $data['level_of_care_id'] ?? null,
            'house_id' => $data['house_id'] ?? null,
            'generated_for_date' => $data['generated_for_date'],
            'total_clients' => $data['total_clients'],
            'percentage' => $data['percentage'],
            'target_count' => $data['target_count'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $uaRandomizer->clients()->sync($data['selected_clients'] ?? []);

        return redirect()->route('ua-randomizers.index')
            ->with('success', 'UA randomizer updated successfully.');
    }

    public function destroy(UaRandomizer $uaRandomizer)
    {
        $uaRandomizer->clients()->detach();
        $uaRandomizer->delete();

        return redirect()->route('ua-randomizers.index')
            ->with('success', 'UA randomizer deleted successfully.');
    }

    private function loadRandomizerRelations(UaRandomizer $uaRandomizer): UaRandomizer
    {
        return $uaRandomizer->load([
            'clients' => function ($query) {
                $query->orderBy('last_name')->orderBy('first_name');
            },
            'levelOfCare',
            'house',
            'randomizedBy',
        ]);
    }

    private function buildPdfFileName(UaRandomizer $uaRandomizer): string
    {
        $generatedForDate = $uaRandomizer->generated_for_date->format('Y-m-d');
        $randomizedBy = $uaRandomizer->randomizedBy?->name ?? 'ua-randomizer';

        return sprintf(
            'ua_randomizer_%s_%s.pdf',
            Str::slug($randomizedBy),
            $generatedForDate
        );
    }

    private function buildRandomizerPdf(UaRandomizer $uaRandomizer)
    {
        return Pdf::loadView('ua_randomizers.pdf', [
            'randomizer' => $uaRandomizer,
            'generatedAt' => now(),
        ])->setPaper('letter');
    }

    private function buildFilteredClientsPayload(string $date, ?int $levelOfCareId, ?int $houseId, ?int $excludeRandomizerId = null): array
    {
        $clientsQuery = Client::query()
            ->where(function ($query) use ($date) {
                $query->whereNull('discharge_date')
                    ->orWhere('discharge_date', '>', $date);
            });

        if ($levelOfCareId) {
            $clientsQuery->whereHas('levelOfCareHistory', function ($query) use ($date, $levelOfCareId) {
                $query->where('level_of_care', $levelOfCareId)
                    ->whereDate('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                    });
            });
        }

        if ($houseId) {
            $clientsQuery->whereHas('apartment', function ($query) use ($houseId) {
                $query->where('house_id', $houseId);
            });
        }

        $clients = $clientsQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $referenceDate = Carbon::parse($date);
        $last7Start = $referenceDate->copy()->subDays(6);

        $alreadyPickedQuery = UaRandomizerClient::query()
            ->whereHas('randomizer', function ($query) use ($last7Start, $referenceDate, $excludeRandomizerId) {
                $query->whereBetween('generated_for_date', [$last7Start->toDateString(), $referenceDate->toDateString()]);

                if ($excludeRandomizerId) {
                    $query->where('id', '!=', $excludeRandomizerId);
                }
            });

        $alreadyPickedCounts = $alreadyPickedQuery
            ->select('client_id')
            ->selectRaw('count(*) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $payload = $clients->map(function ($client) use ($alreadyPickedCounts) {
            $count = (int) ($alreadyPickedCounts[$client->id] ?? 0);
            return [
                'id' => $client->id,
                'name' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
                'last_7_count' => $count,
            ];
        });

        return [
            'clients' => $payload,
            'total' => $clients->count(),
        ];
    }
}
