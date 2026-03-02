@extends('adminlte::page')

@section('title', 'Attendance 2026 - Batch Outing')

@section('content_header')
    <h1>Attendance 2026 - Batch Outing</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <div class="btn-group" role="group" aria-label="Batch outing actions">
            <a href="{{ route('attendance2026.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('attendance2026.batch_outing') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
    @include('partials.flash')
    <form method="POST" action="{{ route('attendance2026.batch_outing_store') }}" id="attendance2026Form" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', '') }}">
            </div>

            <div class="col-md-4 mb-2">
                <label for="time_start">Time Start</label>
                <input type="time" name="time_start" id="time_start" class="form-control" step="60" required
                    value="{{ old('time_start', '') }}">
            </div>

            <div class="col-md-4 mb-2">
                <label for="time_end">Time End</label>
                <input type="time" name="time_end" id="time_end" class="form-control" step="60" required
                    value="{{ old('time_end', '') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="units">Units</label>
                <input type="number" name="units" id="units" class="form-control" readonly
                    value="{{ old('units', '') }}">
                <small class="form-text text-muted">15 min = 1 unit</small>
            </div>

            <div class="col-md-4 mb-2">
                <label for="service_code_display">Session Type</label>
                <input type="text" id="service_code_display" class="form-control" readonly
                    value="{{ $serviceCode->friendly_name ?? $serviceCode->service_code }}">
                <input type="hidden" name="service_code_id" value="{{ $serviceCode->id }}">
            </div>

            <div class="col-md-4 mb-2">
                <label for="total_present">Total Present</label>
                <input type="number" id="total_present" class="form-control" readonly value="0">
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="1" style="resize: vertical;">{{ old('remarks') }}</textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="batch_outing_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="batch_outing_attachments" class="form-control" multiple>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <strong>Batch Outing Rows</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="batchOutingTable">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Client</th>
                            <th>Remarks</th>
                            <th style="width: 120px;">Existing Units</th>
                            <th style="width: 90px;"></th>
                        </tr>
                        <tr>
                            <th></th>
                            <th>
                                <div class="d-flex align-items-center">
                                    <label for="session_title" class="mb-0 mr-2">Session Title</label>
                                    <input type="text" id="session_title" class="form-control form-control-sm"
                                        placeholder="Apply to all rows">
                                </div>
                            </th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="batchOutingBody"></tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-sm btn-success" id="addRowButton">+ Add Client</button>
        </div>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const clients = @json($clientOptions);
        const existingRows = @json(old('rows', []));
        const existingUnitsByClient = @json($existingUnitsByClient ?? []);

        let rowIndex = 0;

        const timeStartInput = document.getElementById('time_start');
        const timeEndInput = document.getElementById('time_end');
        const unitsInput = document.getElementById('units');
        const totalPresentInput = document.getElementById('total_present');
        const sessionTitleInput = document.getElementById('session_title');

        function parseTimeToMinutes(value) {
            if (!value) return null;
            const parts = value.split(':');
            if (parts.length < 2) return null;
            return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
        }

        function updateUnits() {
            const startMinutes = parseTimeToMinutes(timeStartInput.value);
            const endMinutes = parseTimeToMinutes(timeEndInput.value);
            if (startMinutes === null || endMinutes === null) {
                unitsInput.value = '';
                return;
            }

            const diff = Math.max(0, endMinutes - startMinutes);
            unitsInput.value = Math.ceil(diff / 15);
        }

        function updateTotalPresent() {
            const count = document.querySelectorAll('#batchOutingBody tr').length;
            totalPresentInput.value = count;
        }

        function addRow(rowData = {}) {
            const sessionTitleValue = sessionTitleInput?.value ?? '';
            const row = document.createElement('tr');
            row.dataset.index = rowIndex;
            row.innerHTML = `
                <td>
                    <select name="rows[${rowIndex}][client_id]" class="form-control form-control-sm client-select" required>
                        <option value="">Select Client</option>
                        ${clients.map(c => `<option value="${c.id}">${c.label}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="text" name="rows[${rowIndex}][remarks]" class="form-control form-control-sm row-remarks">
                </td>
                <td class="text-center">
                    <span class="existing-units">0</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;

            document.getElementById('batchOutingBody').appendChild(row);
            $(row).find('.client-select').select2({
                placeholder: 'Type to search...',
                allowClear: true,
            });
            $(row).find('.client-select').on('change', function() {
                const selectedId = this.value;
                const units = selectedId ? (existingUnitsByClient[selectedId] ?? 0) : 0;
                row.querySelector('.existing-units').textContent = units;
            });

            const rowRemarks = row.querySelector('.row-remarks');
            if (rowData.remarks) {
                rowRemarks.value = rowData.remarks;
            } else if (sessionTitleValue) {
                rowRemarks.value = sessionTitleValue;
            }

            row.querySelector('.remove-row').addEventListener('click', () => {
                row.remove();
                updateTotalPresent();
            });

            if (rowData.client_id) {
                $(row).find('.client-select').val(String(rowData.client_id)).trigger('change');
            }

            updateTotalPresent();
            rowIndex += 1;
        }

        timeStartInput.addEventListener('change', updateUnits);
        timeEndInput.addEventListener('change', updateUnits);
        updateUnits();

        document.addEventListener('DOMContentLoaded', () => {
            const addButton = document.getElementById('addRowButton');
            if (addButton) {
                addButton.addEventListener('click', () => addRow());
            }

            if (existingRows.length > 0) {
                existingRows.forEach((row) => addRow(row));
            } else {
                addRow();
            }

            if (sessionTitleInput) {
                sessionTitleInput.addEventListener('input', () => {
                    document.querySelectorAll('.row-remarks').forEach((input) => {
                        input.value = sessionTitleInput.value;
                    });
                });
            }

            document.getElementById('attendance2026Form').addEventListener('submit', function(event) {
                const rows = document.querySelectorAll('#batchOutingBody tr');
                if (rows.length === 0) {
                    event.preventDefault();
                    alert('Please add at least one client.');
                }
            });
        });
    </script>
@stop
