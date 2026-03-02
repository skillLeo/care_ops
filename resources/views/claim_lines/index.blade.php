@extends('adminlte::page')

@section('title', 'Claim Line Items')

@section('content_header')
    <h1>All Claim Line of Services</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Line Items</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover text-nowrap">
                <thead class="bg-light">
                    <tr>
                        <th>Claim #</th>
                        <th>Service Date</th>
                        <th>Service Code</th>
                        <th>Diagnosis Code</th>
                        <th>Units</th>
                        <th>Billed</th>
                        <th>Processed</th>
                        <th>Denied</th>
                        <th>Check #</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ $line->claim->claim_number }}</td>
                            <td>{{ $line->service_date }}</td>
                            <td>{{ $line->service_code }}</td>
                            <td>{{ $line->diagnosis_code }}</td>
                            <td>{{ $line->units }}</td>
                            <td>${{ number_format($line->billed_amount, 2) }}</td>
                            <td>${{ number_format($line->processed_amount ?? 0, 2) }}</td>
                            <td>${{ number_format($line->denied_amount ?? 0, 2) }}</td>
                            <td>{{ $line->check?->check_number ?? '--' }}</td>
                            <td>
                                @if($line->check_id)
                                    <span class="badge bg-success">Paid</span>
                                @elseif($line->processed_amount || $line->denied_amount)
                                    <span class="badge bg-warning">Processed</span>
                                @else
                                    <span class="badge bg-secondary">Submitted</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('claims.show', $line->claim_id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Claim
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if($lines->isEmpty())
                        <tr><td colspan="11" class="text-center text-muted">No claim lines found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@stop
