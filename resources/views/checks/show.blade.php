@extends('adminlte::page')

@section('title', 'Check Details')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Check #{{ $check->check_number }}</h1>
        <div class="d-flex align-items-center">
            @can('check.edit')
                @if ($check->open)
                    <a href="{{ route('checks.batch.show', $check) }}" class="btn btn-success mr-2">
                        <i class="fas fa-layer-group"></i> Batch Import
                    </a>
                @endif
                <a href="{{ route('checks.edit', $check) }}" class="btn btn-info">
                    <i class="fas fa-edit"></i> Edit Check
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="card mb-4">
        <div class="card-header"><strong>Check Info</strong></div>
        <div class="card-body row">
            <div class="col-md-4"><strong>Payment
                    Date:</strong><br>{{ \Carbon\Carbon::parse($check->payment_date)->format('m/d/Y') }}</div>
            <div class="col-md-4"><strong>Total Paid:</strong><br>${{ number_format($check->totalPaidAmount(), 2) }}</div>
            <div class="col-md-4"><strong>Total Denied:</strong><br>${{ number_format($check->totalDeniedAmount(), 2) }}
            </div>
            <div class="col-md-4"><strong>Total Billed:</strong><br>${{ number_format($check->totalBilledAmount(), 2) }}
            </div>
            <div class="col-md-4"><strong>Total Claims:</strong><br>{{ $check->totalClaims() }}</div>
            <div class="col-md-4"><strong>Lines of Services:</strong><br>{{ $check->totalLinesOfServices() }}</div>
        </div>
    </div>

    @if (! empty($check->attachments))
        <div class="card mb-4">
            <div class="card-header"><strong>Attachments</strong></div>
            <div class="card-body d-flex flex-column gap-2">
                @foreach ($check->attachments as $file)
                    <a href="{{ route('checks.attachments.download', [$check, $file]) }}" class="btn btn-outline-info btn-sm">Download Attachment {{ $loop->iteration }}</a>
                @endforeach
            </div>
        </div>
    @endif

    @foreach ($check->lines->groupBy('claim_id') as $claimId => $lines)
        @php $claim = $lines->first()->claim; @endphp
        <div class="card mb-2">
            <div class="card-header">
                <h5 class="mb-0">
                    <button class="btn btn-link text-left w-100" data-toggle="collapse" data-target="#claim-{{ $claimId }}">
                        <div class="row">
                            <div class="col-md-4"><strong>Client:</strong> {{ $claim->client->last_name }},
                                {{ $claim->client->first_name }}</div>
                            <div class="col-md-4"><strong>Service Dates:</strong> {{ $claim->serviceDates() }}</div>
                            <div class="col-md-4"><strong>Service Dates:</strong> {{ \Carbon\Carbon::parse($claim->submission_date)->format('m/d/Y') }}</div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-md-4"><strong>Billed:</strong> ${{ number_format($claim->totalBilledAmount(), 2) }}</div>
                            <div class="col-md-4"><strong>Paid:</strong> ${{ number_format($claim->totalProcessedAmount(), 2) }}</div>
                            <div class="col-md-4"><strong>Denied:</strong> ${{ number_format($claim->totalDeniedAmount(), 2) }}</div>
                        </div>
                    </button>
                </h5>
            </div>
            <div id="claim-{{ $claimId }}" class="collapse hidden">
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Service Date</th>
                                <th>Service Code</th>
                                <th>Diagnosis</th>
                                <th>Units</th>
                                <th>Billed</th>
                                <th>Processed</th>
                                <th>Denied</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalBilled = 0;
                                $totalProcessed = 0;
                                $totalDenied = 0;
                            @endphp
                            @foreach ($lines as $line)
                                @php
                                    $totalBilled += $line->billed_amount;
                                    $totalProcessed += $line->processed_amount;
                                    $totalDenied += $line->denied_amount;
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($line->service_date)->format('m/d/Y') }}</td>
                                    <td>{{ $line->service_code }}</td>
                                    <td>{{ $line->diagnosis_code }}</td>
                                    <td>{{ $line->units }}</td>
                                    <td>${{ number_format($line->billed_amount, 2) }}</td>
                                    <td>${{ number_format($line->processed_amount, 2) }}</td>
                                    <td>${{ number_format($line->denied_amount, 2) }}</td>
                                    <td>{{ $line->remarks }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Total</th>
                                <th>${{ number_format($totalBilled, 2) }}</th>
                                <th>${{ number_format($totalProcessed, 2) }}</th>
                                <th>${{ number_format($totalDenied, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    <a href="{{ route('checks.index') }}" class="btn btn-secondary mt-4">
        <i class="fas fa-arrow-left"></i> Back to Checks
    </a>
@stop
