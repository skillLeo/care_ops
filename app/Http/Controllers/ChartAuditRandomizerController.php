<?php

namespace App\Http\Controllers;

use App\Models\ChartAuditRandomizer;
use App\Models\ChartAuditRandomizerClient;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\House;
use App\Models\LevelOfCare;
use App\Models\PeerGroup;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChartAuditRandomizerController extends Controller
{
    public function index()
    {
        $randomizers = ChartAuditRandomizer::with([
            'levelOfCare',
            'counselor',
            'peer',
            'house',
            'clientGroup',
            'peerGroup',
            'randomizedBy',
        ])
            ->withCount('clients')
            ->orderByDesc('generated_for_date')
            ->orderByDesc('id')
            ->get();

        return view('chart_audit_randomizers.index', compact('randomizers'));
    }

    public function create()
    {
        return view('chart_audit_randomizers.create', $this->buildFormPayload());
    }

    public function filter(Request $request)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'peer_id' => ['nullable', 'integer', 'exists:users,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'client_group_id' => ['nullable', 'integer', 'exists:client_groups,id'],
            'peer_group_id' => ['nullable', 'integer', 'exists:peer_groups,id'],
            'exclude_randomizer_id' => ['nullable', 'integer', 'exists:chart_audit_randomizers,id'],
        ]);

        $payload = $this->buildFilteredClientsPayload(
            $data['generated_for_date'],
            Arr::get($data, 'level_of_care_id'),
            Arr::get($data, 'counselor_id'),
            Arr::get($data, 'peer_id'),
            Arr::get($data, 'house_id'),
            Arr::get($data, 'client_group_id'),
            Arr::get($data, 'peer_group_id'),
            Arr::get($data, 'exclude_randomizer_id')
        );

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'peer_id' => ['nullable', 'integer', 'exists:users,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'client_group_id' => ['nullable', 'integer', 'exists:client_groups,id'],
            'peer_group_id' => ['nullable', 'integer', 'exists:peer_groups,id'],
            'percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'target_count' => ['required', 'integer', 'min:0'],
            'total_clients' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'selected_clients' => ['nullable', 'array'],
            'selected_clients.*' => ['integer', 'exists:clients,id'],
        ]);

        $randomizer = ChartAuditRandomizer::create([
            'generated_for_date' => $data['generated_for_date'],
            'randomized_at' => now(),
            'randomized_by' => $request->user()->id,
            'level_of_care_id' => $data['level_of_care_id'] ?? null,
            'counselor_id' => $data['counselor_id'] ?? null,
            'peer_id' => $data['peer_id'] ?? null,
            'house_id' => $data['house_id'] ?? null,
            'client_group_id' => $data['client_group_id'] ?? null,
            'peer_group_id' => $data['peer_group_id'] ?? null,
            'total_clients' => $data['total_clients'],
            'percentage' => $data['percentage'],
            'target_count' => $data['target_count'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $randomizer->clients()->sync($data['selected_clients'] ?? []);

        return redirect()->route('chart-audit-randomizers.index')
            ->with('success', 'Chart audit randomizer saved successfully.');
    }

    public function edit(ChartAuditRandomizer $chartAuditRandomizer)
    {
        $payload = $this->buildFilteredClientsPayload(
            $chartAuditRandomizer->generated_for_date->toDateString(),
            $chartAuditRandomizer->level_of_care_id,
            $chartAuditRandomizer->counselor_id,
            $chartAuditRandomizer->peer_id,
            $chartAuditRandomizer->house_id,
            $chartAuditRandomizer->client_group_id,
            $chartAuditRandomizer->peer_group_id,
            $chartAuditRandomizer->id
        );

        return view('chart_audit_randomizers.edit', array_merge(
            $this->buildFormPayload(),
            [
                'randomizer' => $chartAuditRandomizer->load('clients'),
                'initialClients' => $payload['clients'],
                'initialSelected' => $chartAuditRandomizer->clients->pluck('id'),
            ]
        ));
    }

    public function show(ChartAuditRandomizer $chartAuditRandomizer)
    {
        $chartAuditRandomizer->load([
            'clients' => function ($query) {
                $query->with('levelOfCareHistory')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
            'levelOfCare',
            'counselor',
            'peer',
            'house',
            'clientGroup',
            'peerGroup',
            'randomizedBy',
        ]);

        return view('chart_audit_randomizers.show', [
            'randomizer' => $chartAuditRandomizer,
        ]);
    }

    public function viewPdf(ChartAuditRandomizer $chartAuditRandomizer)
    {
        $chartAuditRandomizer = $this->loadRandomizerRelations($chartAuditRandomizer);
        $fileName = $this->buildPdfFileName($chartAuditRandomizer);
        $pdf = $this->buildRandomizerPdf($chartAuditRandomizer);

        return $pdf->stream($fileName);
    }

    public function download(ChartAuditRandomizer $chartAuditRandomizer)
    {
        $chartAuditRandomizer = $this->loadRandomizerRelations($chartAuditRandomizer);
        $fileName = $this->buildPdfFileName($chartAuditRandomizer);
        $pdf = $this->buildRandomizerPdf($chartAuditRandomizer);

        return $pdf->download($fileName);
    }

    public function update(Request $request, ChartAuditRandomizer $chartAuditRandomizer)
    {
        $data = $request->validate([
            'generated_for_date' => ['required', 'date'],
            'level_of_care_id' => ['nullable', 'integer', 'exists:level_of_cares,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'peer_id' => ['nullable', 'integer', 'exists:users,id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'client_group_id' => ['nullable', 'integer', 'exists:client_groups,id'],
            'peer_group_id' => ['nullable', 'integer', 'exists:peer_groups,id'],
            'percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'target_count' => ['required', 'integer', 'min:0'],
            'total_clients' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'selected_clients' => ['nullable', 'array'],
            'selected_clients.*' => ['integer', 'exists:clients,id'],
        ]);

        $chartAuditRandomizer->update([
            'generated_for_date' => $data['generated_for_date'],
            'level_of_care_id' => $data['level_of_care_id'] ?? null,
            'counselor_id' => $data['counselor_id'] ?? null,
            'peer_id' => $data['peer_id'] ?? null,
            'house_id' => $data['house_id'] ?? null,
            'client_group_id' => $data['client_group_id'] ?? null,
            'peer_group_id' => $data['peer_group_id'] ?? null,
            'total_clients' => $data['total_clients'],
            'percentage' => $data['percentage'],
            'target_count' => $data['target_count'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $chartAuditRandomizer->clients()->sync($data['selected_clients'] ?? []);

        return redirect()->route('chart-audit-randomizers.index')
            ->with('success', 'Chart audit randomizer updated successfully.');
    }

    public function destroy(ChartAuditRandomizer $chartAuditRandomizer)
    {
        $chartAuditRandomizer->clients()->detach();
        $chartAuditRandomizer->delete();

        return redirect()->route('chart-audit-randomizers.index')
            ->with('success', 'Chart audit randomizer deleted successfully.');
    }

    private function buildFormPayload(): array
    {
        $levelOfCares = LevelOfCare::orderBy('display_name')->get();
        $houses = House::orderBy('house_name')->get();
        $clientGroups = ClientGroup::orderBy('name')->get();
        $peerGroups = PeerGroup::orderBy('name')->get();
        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->orderBy('name')->get();
        $peers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['peer', 'Peer_Support']);
        })->orderBy('name')->get();

        return [
            'levelOfCares' => $levelOfCares,
            'houses' => $houses,
            'clientGroups' => $clientGroups,
            'peerGroups' => $peerGroups,
            'counselors' => $counselors,
            'peers' => $peers,
            'initialClients' => [],
            'initialSelected' => [],
        ];
    }

    private function loadRandomizerRelations(ChartAuditRandomizer $chartAuditRandomizer): ChartAuditRandomizer
    {
        return $chartAuditRandomizer->load([
            'clients' => function ($query) {
                $query->with('levelOfCareHistory')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
            'levelOfCare',
            'counselor',
            'peer',
            'house',
            'clientGroup',
            'peerGroup',
            'randomizedBy',
        ]);
    }

    private function buildPdfFileName(ChartAuditRandomizer $chartAuditRandomizer): string
    {
        $generatedForDate = $chartAuditRandomizer->generated_for_date->format('Y-m-d');
        $randomizedBy = $chartAuditRandomizer->randomizedBy?->name ?? 'chart-audit-randomizer';

        return sprintf(
            'chart_audit_randomizer_%s_%s.pdf',
            Str::slug($randomizedBy),
            $generatedForDate
        );
    }

    private function buildRandomizerPdf(ChartAuditRandomizer $chartAuditRandomizer)
    {
        return Pdf::loadView('chart_audit_randomizers.pdf', [
            'randomizer' => $chartAuditRandomizer,
            'generatedAt' => now(),
        ])->setPaper('letter');
    }

    private function buildFilteredClientsPayload(
        string $date,
        ?int $levelOfCareId,
        ?int $counselorId,
        ?int $peerId,
        ?int $houseId,
        ?int $clientGroupId,
        ?int $peerGroupId,
        ?int $excludeRandomizerId = null
    ): array {
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

        if ($counselorId) {
            $clientsQuery->whereHas('counselorHistory', function ($query) use ($date, $counselorId) { $query->where('counselor_id', $counselorId)->whereDate('start_date', '<=', $date)->where(function ($q) use ($date) { $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date); }); });
        }

        if ($peerId) {
            $clientsQuery->whereHas('peerHistory', function ($query) use ($date, $peerId) { $query->where('peer_id', $peerId)->whereDate('start_date', '<=', $date)->where(function ($q) use ($date) { $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date); }); });
        }

        if ($houseId) {
            $clientsQuery->whereHas('apartment', function ($query) use ($houseId) {
                $query->where('house_id', $houseId);
            });
        }

        if ($clientGroupId) {
            $clientsQuery->whereHas('levelOfCareHistory', function ($query) use ($date, $clientGroupId) {
                $query->where('client_group_id', $clientGroupId)
                    ->whereDate('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $date);
                    });
            });
        }

        if ($peerGroupId) {
            $clientsQuery->whereHas('peerGroupHistory', function ($query) use ($date, $peerGroupId) { $query->where('peer_group_id', $peerGroupId)->whereDate('start_date', '<=', $date)->where(function ($q) use ($date) { $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date); }); });
        }

        $clients = $clientsQuery
            ->with([
                'counselor',
                'peer',
                'apartment.house',
                'peerGroup',
                'levelOfCareHistory',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $referenceDate = Carbon::parse($date);
        $last7Start = $referenceDate->copy()->subDays(6);
        $last7Counts = $this->fetchAuditCounts($last7Start, $referenceDate, $excludeRandomizerId);

        $payload = $clients->map(function ($client) use ($date, $last7Counts) {
            return [
                'id' => $client->id,
                'name' => strtoupper($client->last_name) . ', ' . strtoupper($client->first_name),
                'level_of_care' => $client->getLevelOfCareOnDateWithoutHospitalization($date) ?? '-',
                'counselor' => $client->counselor?->name ?? '-',
                'peer' => $client->peer?->name ?? '-',
                'house' => $client->apartment?->house?->house_name ?? '-',
                'group' => $client->getClientGroupOnDate($date)?->name ?? '-',
                'peer_group' => $client->peerGroup?->name ?? '-',
                'last_7_count' => $last7Counts[$client->id] ?? 0,
            ];
        });

        return [
            'clients' => $payload,
            'total' => $clients->count(),
        ];
    }

    private function fetchAuditCounts(Carbon $startDate, Carbon $endDate, ?int $excludeRandomizerId = null): array
    {
        $query = ChartAuditRandomizerClient::query()
            ->select('client_id', DB::raw('count(*) as total'))
            ->whereHas('randomizer', function ($query) use ($startDate, $endDate, $excludeRandomizerId) {
                $query->whereBetween('generated_for_date', [$startDate->toDateString(), $endDate->toDateString()]);

                if ($excludeRandomizerId) {
                    $query->where('id', '!=', $excludeRandomizerId);
                }
            })
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        return $query->mapWithKeys(fn ($value, $key) => [$key => (int) $value])->all();
    }
}
