@extends('adminlte::page')

@section('title', 'Productivity - Clinician - Productivity Sheet')

@section('content_header')
    <h1>Productivity - Clinician - Productivity Sheet</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <div class="btn-group" role="group" aria-label="Productivity sheet actions">
            <a href="{{ route('clinical-notes.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('clinical-notes.productivity_sheet') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
    @include('partials.flash')

    <form method="POST" action="{{ route('clinical-notes.productivity_sheet_store') }}" id="clinicalProdSheetForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', '') }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="prod_sheet_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="prod_sheet_attachments" class="form-control" multiple>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <strong>Productivity Rows</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="clinicalProdSheetTable">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th style="width: 28%;">Client</th>
                            <th style="width: 18%;">Note Type</th>
                            <th style="width: 160px;">Interaction Date</th>
                            <th>Remarks</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody id="clinicalProdSheetBody"></tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <button type="button" class="btn btn-sm btn-success" id="addRowButton">+ Add Client</button>
        </div>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>

    @include('partials.duplicate-check-modal')
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const clients = @json($clientOptions);
        const noteTypes = @json($noteTypeOptions);
        const existingRows = @json(old('rows', []));
        const storedDraft = sessionStorage.getItem('clinicalProdSheetDraft');
        const storedMeta = storedDraft ? JSON.parse(storedDraft) : null;
        const draftRows = storedMeta?.rows ?? existingRows;

        let rowIndex = 0;
        let duplicateConfirmedSubmit = false;

        function addRow(rowData = {}) {
            const row = document.createElement('tr');
            row.dataset.index = rowIndex;
            row.innerHTML = `
                <td><input type="time" name="rows[${rowIndex}][time_start]" class="form-control form-control-sm time-start" required></td>
                <td><input type="time" name="rows[${rowIndex}][time_end]" class="form-control form-control-sm time-end" required></td>
                <td>
                    <select name="rows[${rowIndex}][client_id]" class="form-control form-control-sm client-select" required>
                        <option value="">Select Client</option>
                        ${clients.map(c => `<option value="${c.id}">${c.label}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select name="rows[${rowIndex}][note_type]" class="form-control form-control-sm note-type-select" required>
                        <option value="">Select Note Type</option>
                        ${noteTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="date" name="rows[${rowIndex}][interaction_date]" class="form-control form-control-sm interaction-date" required
                        max="{{ \Carbon\Carbon::today()->toDateString() }}">
                </td>
                <td>
                    <input type="text" name="rows[${rowIndex}][remarks]" class="form-control form-control-sm row-remarks">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;

            document.getElementById('clinicalProdSheetBody').appendChild(row);
            $(row).find('.client-select').select2({
                placeholder: 'Type to search...',
                allowClear: true,
            });

            if (rowData.time_start) {
                row.querySelector('.time-start').value = rowData.time_start;
            }
            if (rowData.time_end) {
                row.querySelector('.time-end').value = rowData.time_end;
            }
            if (rowData.note_type) {
                row.querySelector('.note-type-select').value = rowData.note_type;
            }
            if (rowData.interaction_date) {
                row.querySelector('.interaction-date').value = rowData.interaction_date;
            }
            if (rowData.remarks) {
                row.querySelector('.row-remarks').value = rowData.remarks;
            }
            row.querySelector('.remove-row').addEventListener('click', () => {
                row.remove();
            });
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
                sessionStorage.removeItem('clinicalProdSheetDraft');
            }
            if (draftRows.length > 0) {
                draftRows.forEach((row) => addRow(row));
            } else {
                addRow();
            }

            document.getElementById('clinicalProdSheetForm').addEventListener('submit', async function(event) {
                const rows = document.querySelectorAll('#clinicalProdSheetBody tr');
                if (rows.length === 0) {
                    event.preventDefault();
                    alert('Please add at least one client.');
                    return;
                }

                if (!duplicateConfirmedSubmit) {
                    event.preventDefault();
                    const shouldContinue = await window.checkSubmissionDuplicates({
                        type: 'clinical_prod_sheet',
                        service_date: document.getElementById('service_date').value,
                        submitted_by: @json(auth()->id()),
                        viewRoute: 'clinical-notes.submissions.show',
                    });

                    if (!shouldContinue) {
                        return;
                    }

                    duplicateConfirmedSubmit = true;
                    document.getElementById('clinicalProdSheetForm').submit();
                }
            });
        });
    </script>
@stop
