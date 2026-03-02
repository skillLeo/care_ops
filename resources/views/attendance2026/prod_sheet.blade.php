@extends('adminlte::page')

@section('title', 'CPRS Productivity Sheet')

@section('content_header')
    <h1>CPRS Productivity Sheet</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <div class="btn-group" role="group" aria-label="Productivity sheet actions">
            <a href="{{ route('attendance2026.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('attendance2026.prod_sheet') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
    @include('partials.flash')

    <form method="POST" action="{{ route('attendance2026.prod_sheet_store') }}" id="prodSheetForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', '') }}">
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
                <label for="prod_sheet_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="prod_sheet_attachments" class="form-control" multiple>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <strong>Productivity Rows</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="prodSheetTable">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Auto</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th style="width: 70px;">Units</th>
                            <th style="width: 110px;">Existing Units</th>
                            <th style="width: 40%;">Client</th>
                            <th>Remarks</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody id="prodSheetBody"></tbody>
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
        const storedDraft = sessionStorage.getItem('prodSheetDraft');
        const storedMeta = storedDraft ? JSON.parse(storedDraft) : null;
        const draftRows = storedMeta?.rows ?? existingRows;
        const existingUnitsByClient = @json($existingUnitsByClient ?? []);

        let rowIndex = 0;

        function roundUpToQuarterHour(minutes) {
            const remainder = minutes % 15;
            if (remainder === 0) return minutes;
            return minutes + (15 - remainder);
        }

        function parseTimeToMinutes(value) {
            if (!value) return null;
            const parts = value.split(':');
            if (parts.length < 2) return null;
            return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
        }

        function minutesToTime(minutes) {
            const hours = Math.floor(minutes / 60) % 24;
            const mins = minutes % 60;
            return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
        }

        function updateUnits(row) {
            const startInput = row.querySelector('.time-start');
            const endInput = row.querySelector('.time-end');
            const unitsInput = row.querySelector('.units-input');

            const startMinutes = parseTimeToMinutes(startInput.value);
            const endMinutes = parseTimeToMinutes(endInput.value);
            if (startMinutes === null || endMinutes === null) {
                unitsInput.value = '';
                return;
            }
            const diff = Math.max(0, endMinutes - startMinutes);
            unitsInput.value = Math.ceil(diff / 15);
        }

        function updateTotalPresent() {
            const count = document.querySelectorAll('#prodSheetBody tr').length;
            document.getElementById('total_present').value = count;
        }

        function addRow(rowData = {}) {
            const row = document.createElement('tr');
            row.dataset.index = rowIndex;
            row.innerHTML = `
                <td class="text-center">
                    ${rowIndex === 0 ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary auto-btn">Auto</button>'}
                </td>
                <td><input type="time" name="rows[${rowIndex}][time_start]" class="form-control form-control-sm time-start" required></td>
                <td><input type="time" name="rows[${rowIndex}][time_end]" class="form-control form-control-sm time-end" required></td>
                <td>
                    <input type="number" name="rows[${rowIndex}][units]" class="form-control form-control-sm units-input" readonly>
                </td>
                <td class="text-center">
                    <span class="existing-units">0</span>
                </td>
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
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;

            document.getElementById('prodSheetBody').appendChild(row);
            $(row).find('.client-select').select2({
                placeholder: 'Type to search...',
                allowClear: true,
            });
            $(row).find('.client-select').on('change', function() {
                const selectedId = this.value;
                const units = selectedId ? (existingUnitsByClient[selectedId] ?? 0) : 0;
                row.querySelector('.existing-units').textContent = units;
            });

            row.querySelector('.time-start').addEventListener('change', () => updateUnits(row));
            row.querySelector('.time-end').addEventListener('change', () => updateUnits(row));
            if (rowData.time_start) {
                row.querySelector('.time-start').value = rowData.time_start;
            }
            if (rowData.time_end) {
                row.querySelector('.time-end').value = rowData.time_end;
            }
            if (rowData.remarks) {
                row.querySelector('.row-remarks').value = rowData.remarks;
            }
            updateUnits(row);
            row.querySelector('.remove-row').addEventListener('click', () => {
                row.remove();
                updateTotalPresent();
            });
            const autoBtn = row.querySelector('.auto-btn');
            if (autoBtn) {
                autoBtn.addEventListener('click', () => {
                    const prevRow = row.previousElementSibling;
                    if (!prevRow) return;
                    const prevStart = prevRow.querySelector('.time-start').value;
                    const prevEnd = prevRow.querySelector('.time-end').value;
                    const prevStartMinutes = parseTimeToMinutes(prevStart);
                    const prevEndMinutes = parseTimeToMinutes(prevEnd);
                    if (prevStartMinutes === null || prevEndMinutes === null) {
                        alert('Please enter start/end times in the previous row first.');
                        return;
                    }
                    const duration = prevEndMinutes - prevStartMinutes;
                    if (duration <= 0) {
                        alert('Previous row end time must be after the start time.');
                        return;
                    }
                    const roundedStart = roundUpToQuarterHour(prevEndMinutes);
                    row.querySelector('.time-start').value = minutesToTime(roundedStart);
                    row.querySelector('.time-end').value = minutesToTime(roundedStart + duration);
                    updateUnits(row);
                });
            }

            updateTotalPresent();
            rowIndex += 1;

            if (rowData.client_id) {
                $(row).find('.client-select').val(String(rowData.client_id)).trigger('change');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const addButton = document.getElementById('addRowButton');
            if (addButton) {
                addButton.addEventListener('click', addRow);
            }
            if (storedMeta) {
                document.getElementById('service_date').value = storedMeta.service_date ?? '';
                document.getElementById('remarks').value = storedMeta.remarks ?? '';
                sessionStorage.removeItem('prodSheetDraft');
            }
            if (draftRows.length > 0) {
                draftRows.forEach((row) => addRow(row));
            } else {
                addRow();
            }


            document.getElementById('prodSheetForm').addEventListener('submit', function(event) {
                const rows = document.querySelectorAll('#prodSheetBody tr');
                if (rows.length === 0) {
                    event.preventDefault();
                    alert('Please add at least one client.');
                }
            });
        });
    </script>
@stop
