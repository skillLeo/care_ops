@extends('adminlte::page')

@section('title', 'Chart Audit Randomizer')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Chart Audit Randomizer</h1>
        @can('chart_audit_randomizer.create')
            <a href="{{ route('chart-audit-randomizers.create') }}" class="btn btn-primary">Generate</a>
        @endcan
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Generated For</th>
                            <th>Randomized At</th>
                            <th>Randomized By</th>
                            <th>Level of Care</th>
                            <th>Counselor</th>
                            <th>Peer</th>
                            <th>House</th>
                            <th>Group</th>
                            <th>Peer Group</th>
                            <th>Total Clients</th>
                            <th>Target</th>
                            <th>Selected</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($randomizers as $randomizer)
                            <tr>
                                <td>{{ $randomizer->generated_for_date->format('m/d/Y') }}</td>
                                <td>{{ $randomizer->randomized_at->format('m/d/Y g:i A') }}</td>
                                <td>{{ $randomizer->randomizedBy?->name ?? 'N/A' }}</td>
                                <td>{{ $randomizer->levelOfCare?->display_name ?? 'All' }}</td>
                                <td>{{ $randomizer->counselor?->name ?? 'All' }}</td>
                                <td>{{ $randomizer->peer?->name ?? 'All' }}</td>
                                <td>{{ $randomizer->house?->house_name ?? 'All' }}</td>
                                <td>{{ $randomizer->clientGroup?->name ?? 'All' }}</td>
                                <td>{{ $randomizer->peerGroup?->name ?? 'All' }}</td>
                                <td>{{ $randomizer->total_clients }}</td>
                                <td>{{ $randomizer->target_count }} ({{ $randomizer->percentage }}%)</td>
                                <td>{{ $randomizer->clients_count }}</td>
                                <td>{{ $randomizer->remarks ?? '-' }}</td>
                                <td class="text-right">
                                    @can('chart_audit_randomizer.view')
                                        <a href="{{ route('chart-audit-randomizers.show', $randomizer) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @endcan
                                    @can('chart_audit_randomizer.edit')
                                        <a href="{{ route('chart-audit-randomizers.edit', $randomizer) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @endcan
                                    @can('chart_audit_randomizer.delete')
                                        <form action="{{ route('chart-audit-randomizers.destroy', $randomizer) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted">No chart audit randomizer entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
