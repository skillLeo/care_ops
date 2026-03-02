@extends('adminlte::page')

@section('title', 'Edit Claim')

@section('content_header')
    <h1>Edit Claim #{{ $claim->claim_number }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('claims.update', $claim) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><strong>Claim Info</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label>Client</label>
                    <input type="text" class="form-control" value="{{ $claim->client->last_name }}, {{ $claim->client->first_name }}" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Claim Number</label>
                    <input type="text" class="form-control" value="{{ $claim->claim_number }}" >
                </div>
                <div class="form-group col-md-4">
                    <label>Carelon Claim Number</label>
                    <input type="text" class="form-control" value="{{ $claim->carelon_claim_number }}" >
                </div>
                <div class="form-group col-md-4">
                    <label>Submission Date</label>
                    <input type="date" name="submission_date" class="form-control"
                        value="{{ \Carbon\Carbon::parse($claim->submission_date)->format('Y-m-d') }}" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="claim_attachments">Attachments</label>
                    <input type="file" name="attachments[]" id="claim_attachments" class="form-control" multiple>
                    @if (! empty($claim->attachments))
                        <div class="mt-2 d-flex flex-column gap-1">
                            @foreach ($claim->attachments as $file)
                                <a href="{{ route('claims.attachments.download', [$claim, $file]) }}" class="btn btn-sm btn-outline-info">Download {{ $loop->iteration }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="form-group col-md-8">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="1">{{ $claim->remarks }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Line of Services</strong>
                <div class="d-flex align-items-center">
                    <select id="master-check" class="form-control form-control-sm mr-2">
                        <option value="">-- Check --</option>
                        @foreach($checks as $check)
                            <option value="{{ $check->id }}">{{ $check->check_number }} ({{ $check->payment_date }})</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-info ml-2" onclick="processAll()">Process All</button>
                    <button type="button" class="btn btn-sm btn-success ml-2" onclick="addLine()">+ Add Line</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="lines-table">
                    <thead>
                        <tr>
                            <th>Service Date</th>
                            <th>Service Code</th>
                            <th>Diagnosis</th>
                            <th>Units</th>
                            <th>Billed</th>
                            <th>Processed</th>
                            <th>Denied</th>
                            <th>Check</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @php $index = 0; @endphp
                        @foreach($claim->lines as $line)
                            <tr>
                                <td><input type="date" name="lines[{{ $index }}][service_date]" value="{{ $line->service_date }}" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="lines[{{ $index }}][service_code]" value="{{ $line->service_code }}" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="lines[{{ $index }}][diagnosis_code]" value="{{ $line->diagnosis_code }}" class="form-control form-control-sm"></td>
                                <td><input type="number" name="lines[{{ $index }}][units]" value="{{ $line->units }}" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.01" name="lines[{{ $index }}][billed_amount]" value="{{ $line->billed_amount }}" class="form-control form-control-sm" required></td>
                                <td><input type="number" step="0.01" name="lines[{{ $index }}][processed_amount]" value="{{ $line->processed_amount }}" class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.01" name="lines[{{ $index }}][denied_amount]" value="{{ $line->denied_amount }}" class="form-control form-control-sm"></td>
                                <td>
                                    <select name="lines[{{ $index }}][check_id]" class="form-control form-control-sm">
                                        <option value="">--</option>
                                        @foreach($checks as $check)
                                            <option value="{{ $check->id }}" {{ $line->check_id == $check->id ? 'selected' : '' }}>
                                                {{ $check->check_number }} ({{ $check->payment_date }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[{{ $index }}][remarks]" value="{{ $line->remarks }}" class="form-control form-control-sm"></td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">×</button></td>
                            </tr>
                            @php $index++; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total:</th>
                        <th id="total-billed">$0.00</th>
                        <th id="total-processed">$0.00</th>
                        <th id="total-denied">$0.00</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>

                </table>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Claim
            </button>
            <a href="{{ route('claims.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </form>
@stop

@section('js')
<script>
let lineIndex = {{ count($claim->lines) }};

function calculateTotals() {
    let totalBilled = 0, totalProcessed = 0, totalDenied = 0;

    document.querySelectorAll('#lines-body tr').forEach(row => {
        totalBilled += parseFloat(row.querySelector('[name*="[billed_amount]"]')?.value) || 0;
        totalProcessed += parseFloat(row.querySelector('[name*="[processed_amount]"]')?.value) || 0;
        totalDenied += parseFloat(row.querySelector('[name*="[denied_amount]"]')?.value) || 0;
    });

    document.getElementById('total-billed').textContent = `$${totalBilled.toFixed(2)}`;
    document.getElementById('total-processed').textContent = `$${totalProcessed.toFixed(2)}`;
    document.getElementById('total-denied').textContent = `$${totalDenied.toFixed(2)}`;
}

function addLine() {
    const checks = @json($checks);
    const row = document.createElement('tr');

    row.innerHTML = `
        <td><input type="date" name="lines[${lineIndex}][service_date]" class="form-control form-control-sm" required></td>
        <td><input type="text" name="lines[${lineIndex}][service_code]" class="form-control form-control-sm" required></td>
        <td><input type="text" name="lines[${lineIndex}][diagnosis_code]" class="form-control form-control-sm"></td>
        <td><input type="number" name="lines[${lineIndex}][units]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][billed_amount]" class="form-control form-control-sm" required></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][processed_amount]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][denied_amount]" class="form-control form-control-sm"></td>
        <td>
            <select name="lines[${lineIndex}][check_id]" class="form-control form-control-sm">
                <option value="">--</option>
                ${checks.map(c => `<option value="${c.id}">${c.check_number} (${c.payment_date})</option>`).join('')}
            </select>
        </td>
        <td><input type="text" name="lines[${lineIndex}][remarks]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-danger btn-sm">×</button></td>
    `;

    // attach remove + recalc
    row.querySelector('button').addEventListener('click', function () {
        row.remove();
        calculateTotals();
    });

    document.getElementById('lines-body').appendChild(row);
    lineIndex++;
    calculateTotals();
}

function processAll() {
    document.querySelectorAll('#lines-body tr').forEach(row => {
        const billed = row.querySelector('[name*="[billed_amount]"]');
        const processed = row.querySelector('[name*="[processed_amount]"]');
        if (billed && processed) {
            processed.value = billed.value;
        }
    });
    calculateTotals();
}

document.addEventListener('input', function (e) {
    if (e.target.closest('#lines-body')) {
        calculateTotals();
    }
});

document.getElementById('master-check').addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('#lines-body select[name*="[check_id]"]').forEach(sel => {
        sel.value = val;
    });
});

document.querySelectorAll('#lines-body button.btn-danger').forEach(btn => {
    btn.addEventListener('click', function () {
        btn.closest('tr').remove();
        calculateTotals();
    });
});

window.addEventListener('DOMContentLoaded', calculateTotals);
</script>
@stop
