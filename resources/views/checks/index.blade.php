@extends('adminlte::page')

@section('title', 'Checks')

@section('content_header')
    <h1>Checks</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="mb-3">
        @can('check.create')
            <a href="{{ route('checks.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add Check
            </a>
        @endcan
    </div>

    @if ($checks->isEmpty())
        <div class="alert alert-info">No checks recorded yet.</div>
    @else
        <div class="table-responsive">
            <table id="checksTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Check #</th>
                        <th>Payment Date</th>
                        <th>Open</th>
                        <th>Total Paid</th>
                        <th>Total Billed</th>
                        <th>Total Denied</th>
                        <th>Total Claims</th>
                        <th>Line Items</th>
                        <th>Clients</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td>{{ $check->check_number }}</td>
                            <td data-order="{{ \Carbon\Carbon::parse($check->payment_date)->format('Y-m-d') }}">
                                {{ \Carbon\Carbon::parse($check->payment_date)->format('m/d/Y') }}
                            </td>
                            <td>
                                @if ($check->open)
                                    <span class="badge bg-success">Open</span>
                                @else
                                    <span class="badge bg-secondary">Closed</span>
                                @endif
                            </td>
                            <td>${{ number_format($check->totalPaidAmount(), 2) }}</td>
                            <td>${{ number_format($check->totalBilledAmount(), 2) }}</td>
                            <td>${{ number_format($check->totalDeniedAmount(), 2) }}</td>
                            <td>{{ $check->totalClaims() }}</td>
                            <td>{{ $check->totalLinesOfServices() }}</td>
                            <td>{{ $check->totalClient() }}</td>
                            <td>
                                @can('check.view')
                                    <a href="{{ route('checks.show', $check) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endcan
                                @can('check.edit')
                                    <a href="{{ route('checks.edit', $check) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @if ($check->open)
                                        <a href="{{ route('checks.batch.show', $check) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-layer-group"></i> Batch
                                        </a>
                                    @endif
                                @endcan
                                @can('check.toggle')
                                    <form action="{{ route('checks.toggle', $check) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm {{ $check->open ? 'btn-warning' : 'btn-success' }}">
                                            <i class="fas {{ $check->open ? 'fa-lock' : 'fa-unlock' }}"></i>
                                            {{ $check->open ? 'Close' : 'Open' }}
                                        </button>
                                    </form>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('check.delete')
                                        <form action="{{ route('checks.destroy', $check) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#checksTable').DataTable({
                order: [
                    [1, 'desc']
                ],
                pageLength: 25
            });
        });
    </script>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
@stop

@section('css')
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@stop
