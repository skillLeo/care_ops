@extends('adminlte::page')

@section('title', 'Create Claim')

@section('content_header')
    <h1>Create Claim</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('claims.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <div class="card-header"><strong>Claim Details</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label for="client_id">Client</label>
                    <select name="client_id" class="form-control select2" required>
                        <option value="">Select Client</option>
                        @foreach ($clients->sortBy('last_name') as $client)
                            <option value="{{ $client->id }}">{{ strtoupper($client->last_name) }},
                                {{ strtoupper($client->first_name) }} - MRN: {{ $client->mrn }} - DOB:
                                {{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Claim Number</label>
                    <input type="text" name="claim_number" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Carelon Claim Number</label>
                    <input type="text" name="carelon_claim_number" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Submission Date</label>
                    <input type="date" name="submission_date" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="claim_attachments">Attachments</label>
                    <input type="file" name="attachments[]" id="claim_attachments" class="form-control" multiple>
                </div>
                <div class="form-group col-md-8">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="1"></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Line of Services</strong>
                <button type="button" class="btn btn-sm btn-success" onclick="addLine()">+ Add Line</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="lines-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Service Date</th>
                            <th>Service Code</th>
                            <th>Diagnosis Code</th>
                            <th>Units</th>
                            <th>Billed ($)</th>
                            <th>Processed ($)</th>
                            <th>Denied ($)</th>
                            <th>Check</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body"></tbody>
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
                <i class="fas fa-save"></i> Submit Claim
            </button>
            <a href="{{ route('claims.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@stop

@section('css')
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Type to search...",
                allowClear: true
            });
        });
        let lineIndex = 0;

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
            document.getElementById('lines-body').appendChild(row);

            // Add remove listener and recalc
            row.querySelector('button').addEventListener('click', function () {
                row.remove();
                calculateTotals();
            });

            lineIndex++;
            calculateTotals();
        }


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

        document.addEventListener('input', function (e) {
            if (e.target.closest('#lines-table')) {
                calculateTotals();
            }
        });

    </script>


@stop
