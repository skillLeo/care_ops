@extends('adminlte::page')

@section('title', 'UA Randomizer')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0">UA Randomizer</h1>
        <div class="d-flex flex-wrap gap-2">
            @can('ua_randomizer.view')
                <a href="{{ route('ua-randomizers.pdf', $randomizer) }}" class="btn btn-outline-primary">
                    <i class="fas fa-eye"></i> View PDF
                </a>
                <a href="{{ route('ua-randomizers.download', $randomizer) }}" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            @endcan
            @can('ua_randomizer.edit')
                <a href="{{ route('ua-randomizers.edit', $randomizer) }}" class="btn btn-outline-secondary">Edit</a>
            @endcan
            <a href="{{ route('ua-randomizers.index') }}" class="btn btn-outline-secondary">Back</a>
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
                    <div class="text-muted">House</div>
                    <div>{{ $randomizer->house?->house_name ?? 'All' }}</div>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($randomizer->clients as $client)
                            <tr>
                                <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted">No clients selected.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
