@extends('adminlte::page')

@section('title', 'Dropbox')

@section('content_header')
    <h1>Dropbox</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="{{ route('dropboxes.index', ['status' => 'all']) }}" class="btn btn-sm {{ $activeStatus === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">All</a>
        <a href="{{ route('dropboxes.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $activeStatus === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">Pending</a>
        <a href="{{ route('dropboxes.index', ['status' => 'approved']) }}" class="btn btn-sm {{ $activeStatus === 'approved' ? 'btn-primary' : 'btn-outline-primary' }}">Accepted</a>
        <a href="{{ route('dropboxes.index', ['status' => 'denied']) }}" class="btn btn-sm {{ $activeStatus === 'denied' ? 'btn-primary' : 'btn-outline-primary' }}">Denied</a>
        <a href="{{ route('dropboxes.index', ['status' => 'created']) }}" class="btn btn-sm {{ $activeStatus === 'created' ? 'btn-primary' : 'btn-outline-primary' }}">Created</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px">#</th>
                        <th>Submission Date</th>
                        <th>Name (DOB)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dropboxes as $dropbox)
                        @php
                            $rowNumber = ($dropboxes->currentPage() - 1) * $dropboxes->perPage() + $loop->iteration;
                            $statusColors = [
                                'pending' => 'warning',
                                'approved' => 'info',
                                'denied' => 'danger',
                                'created' => 'success',
                            ];
                            $statusClass = $statusColors[$dropbox->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>{{ $rowNumber }}</td>
                            <td>{{ optional(optional($dropbox->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i:s A') ?? 'N/A' }}</td>
                            <td>
                                <div class="fw-semibold">{{ strtoupper($dropbox->last_name) }}, {{ strtoupper($dropbox->first_name) }}</div>
                                <small class="text-muted">DOB: {{ optional($dropbox->date_of_birth)->format('m/d/Y') ?? 'N/A' }}</small>
                                <div>
                                    <span class="badge bg-secondary text-uppercase">{{ ucfirst($dropbox->type) }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusClass }} text-uppercase">{{ ucfirst($dropbox->status) }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('dropboxes.show', $dropbox) }}" class="btn btn-outline-primary">View</a>
                                    @if ($dropbox->status === 'approved' && ! $dropbox->client)
                                        @can('client.create')
                                            <a href="{{ route('clients.create', ['dropbox_id' => $dropbox->id]) }}" class="btn btn-outline-success">Create</a>
                                        @endcan
                                    @endif
                                    @if ($dropbox->client)
                                        @can('client.view')
                                            <a href="{{ route('clients.show', $dropbox->client) }}" class="btn btn-outline-secondary">Client</a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No submissions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $dropboxes->onEachSide(1)->links('pagination::simple-bootstrap-4') }}
        </div>
    </div>
@stop
