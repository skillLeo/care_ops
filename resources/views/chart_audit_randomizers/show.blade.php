@extends('adminlte::page')

@section('title', 'Chart Audit Randomizer')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0">Chart Audit Randomizer</h1>
        <div class="d-flex flex-wrap gap-2">
            @can('chart_audit_randomizer.view')
                <a href="{{ route('chart-audit-randomizers.pdf', $randomizer) }}" class="btn btn-outline-primary">
                    <i class="fas fa-eye"></i> View PDF
                </a>
                <a href="{{ route('chart-audit-randomizers.download', $randomizer) }}" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            @endcan
            @can('chart_audit_randomizer.edit')
                <a href="{{ route('chart-audit-randomizers.edit', $randomizer) }}" class="btn btn-outline-secondary">Edit</a>
            @endcan
            <a href="{{ route('chart-audit-randomizers.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Generated For</div>
                    <div>{{ $randomizer->generated_for_date->format('m/d/Y') }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Randomized At</div>
                    <div>{{ $randomizer->randomized_at->format('m/d/Y g:i A') }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Randomized By</div>
                    <div>{{ $randomizer->randomizedBy?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Level of Care</div>
                    <div>{{ $randomizer->levelOfCare?->display_name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Counselor</div>
                    <div>{{ $randomizer->counselor?->name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Peer</div>
                    <div>{{ $randomizer->peer?->name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">House</div>
                    <div>{{ $randomizer->house?->house_name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Group</div>
                    <div>{{ $randomizer->clientGroup?->name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Peer Group</div>
                    <div>{{ $randomizer->peerGroup?->name ?? 'All' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Total Clients</div>
                    <div>{{ $randomizer->total_clients }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Target</div>
                    <div>{{ $randomizer->target_count }} ({{ $randomizer->percentage }}%)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Selected</div>
                    <div>{{ $randomizer->clients->count() }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="text-muted">Remarks</div>
                    <div>{{ $randomizer->remarks ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Selected Clients</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Level of Care</th>
                            <th>Counselor</th>
                            <th>Peer</th>
                            <th>House</th>
                            <th>Group</th>
                            <th>Peer Group</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($randomizer->clients as $client)
                            <tr>
                                <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                                <td>{{ $client->getLevelOfCareOnDateWithoutHospitalization($randomizer->generated_for_date->toDateString()) ?? '-' }}</td>
                                <td>{{ $client->counselor?->name ?? '-' }}</td>
                                <td>{{ $client->peer?->name ?? '-' }}</td>
                                <td>{{ $client->house?->house_name ?? '-' }}</td>
                                <td>{{ $client->getClientGroupOnDate($randomizer->generated_for_date->toDateString())?->name ?? '-' }}</td>
                                <td>{{ $client->peerGroup?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted" colspan="7">No clients selected.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
