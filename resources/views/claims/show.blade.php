@extends('adminlte::page')

@section('title', 'Show Claim')

@section('content_header')
    <h1>Show Claim #{{ $claim->claim_number }}</h1>
@stop

@section('content')


        <div class="card">
            <div class="card-header"><strong>Claim Info</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label>Client</label>
                    <input type="text" class="form-control" value="{{ $claim->client->last_name }}, {{ $claim->client->first_name }}" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Claim Number</label>
                    <input type="text" class="form-control" value="{{ $claim->claim_number }}" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Carelon Claim Number</label>
                    <input type="text" class="form-control" value="{{ $claim->carelon_claim_number }}" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Submission Date</label>
                    <input type="date" name="submission_date" class="form-control"
                        value="{{ \Carbon\Carbon::parse($claim->submission_date)->format('Y-m-d') }}" readonly>
                </div>
                <div class="form-group col-md-8">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="1" readonly>{{ $claim->remarks }}</textarea>
                </div>
            </div>
        </div>

        @if (! empty($claim->attachments))
            <div class="card mt-3">
                <div class="card-header"><strong>Attachments</strong></div>
                <div class="card-body d-flex flex-column gap-2">
                    @foreach ($claim->attachments as $file)
                        <a href="{{ route('claims.attachments.download', [$claim, $file]) }}" class="btn btn-outline-info btn-sm">Download Attachment {{ $loop->iteration }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Line of Services</strong>

            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0" id="lines-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Service Date</th>
                            <th class="text-left">Service Code</th>
                            <th class="text-left">Diagnosis</th>
                            <th class="text-center">Units</th>
                            <th class="text-right">Billed</th>
                            <th class="text-right">Processed</th>
                            <th class="text-right">Denied</th>
                            <th class="text-left">Check</th>
                            <th class="text-center">Payment Date</th>
                            <th class="text-left">Remarks</th>

                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @php $index = 0; @endphp
                        @foreach($claim->lines as $line)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($line->service_date)->format('m/d/Y') }}</td>
                                <td>{{ $line->service_code }}</td>
                                <td>{{ $line->diagnosis_code }}</td>
                                <td class="text-center">{{ $line->units ?? 0 }}</td>
                                <td class="text-right">${{ number_format($line->billed_amount ?? 0, 2) }}</td>
                                <td class="text-right">${{ number_format($line->processed_amount ?? 0, 2) }}</td>
                                <td class="text-right">${{ number_format($line->denied_amount ?? 0, 2) }}</td>
                                <td>
                                    @if($line->check)
                                        {{ $line->check->check_number }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($line->check)
                                        {{ \Carbon\Carbon::parse($line->check->payment_date)->format('m/d/Y') }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td><input type="text" name="lines[{{ $index }}][remarks]" value="{{ $line->remarks }}" class="form-control form-control-sm" readonly></td>

                            </tr>
                            @php $index++; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Total: </th>
                            <th class="text-right">${{ number_format($claim->totalBilledAmount(), 2) }}</th>
                            <th class="text-right">${{ number_format($claim->totalProcessedAmount(), 2) }}</th>
                            <th class="text-right">${{ number_format($claim->totalDeniedAmount(), 2) }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>

                </table>
            </div>
        </div>



@stop
