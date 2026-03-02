@extends('adminlte::page')

@section('title', 'Productivity - CPRS - Peer Outing')

@section('content_header')
    <h1>Productivity - CPRS - Peer Outing</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <div class="btn-group" role="group" aria-label="Batch outing actions">
            <a href="{{ route('cprs.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('cprs.batch_outing') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <form method="POST" action="{{ route('cprs.batch_outing_store') }}" id="attendance2026Form" enctype="multipart/form-data">
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
                <label for="total_present">Today's Total Clients</label>
                <input type="number" id="total_present" class="form-control" readonly value="0">
            </div>
            <div class="col-md-4 mb-2">
                <label for="batch_outing_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="batch_outing_attachments" class="form-control" multiple>
            </div>
        </div>
        <div id="batchOutingAlert" class="alert alert-warning d-none" role="alert"></div>
        <input type="hidden" name="service_code_id" value="{{ $serviceCode->id }}">

        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="remarks">Session Title</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="1" style="resize: vertical;" required>{{ old('remarks') }}</textarea>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <strong>Peer Outing Rows</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="batchOutingTable">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Client</th>
                            <th>Individual Remarks</th>
                            <th style="width: 90px;"></th>
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

    <div class="modal fade" id="batchOutingWarningModal" tabindex="-1" role="dialog"
        aria-labelledby="batchOutingWarningLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchOutingWarningLabel">Before you submit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This productivity sheet is to be submitted only after completing the notes identified in this report.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmBatchOutingSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

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
        const existingRows = @json(old('rows', []));
        const storedDraft = sessionStorage.getItem('batchOutingDraft');
        const storedMeta = storedDraft ? JSON.parse(storedDraft) : null;
        const draftRows = storedMeta?.rows ?? existingRows;
        let rowIndex = 0;

        const timeStartInput = document.getElementById('time_start');
        const timeEndInput = document.getElementById('time_end');
        const unitsInput = document.getElementById('units');
        const totalPresentInput = document.getElementById('total_present');
        const batchOutingAlert = document.getElementById('batchOutingAlert');
        const warningModalElement = document.getElementById('batchOutingWarningModal');
        let warningModalInstance = null;
        let warningModalBackdrop = null;
        let pendingSubmit = false;

        function showFormAlert(message) {
            if (!batchOutingAlert) {
                return;
            }
            batchOutingAlert.textContent = message;
            batchOutingAlert.classList.remove('d-none');
        }

        function clearFormAlert() {
            if (!batchOutingAlert) {
                return;
            }
            batchOutingAlert.textContent = '';
            batchOutingAlert.classList.add('d-none');
        }

        function showWarningModal() {
            if (window.bootstrap?.Modal) {
                if (!warningModalInstance) {
                    warningModalInstance = new bootstrap.Modal(warningModalElement);
                }
                warningModalInstance.show();
                return;
            }
            warningModalElement.classList.add('show');
            warningModalElement.style.display = 'block';
            warningModalElement.removeAttribute('aria-hidden');
            warningModalBackdrop = document.createElement('div');
            warningModalBackdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(warningModalBackdrop);
            document.body.classList.add('modal-open');
        }

        function hideWarningModal() {
            if (warningModalInstance) {
                warningModalInstance.hide();
                return;
            }
            warningModalElement.classList.remove('show');
            warningModalElement.style.display = 'none';
            warningModalElement.setAttribute('aria-hidden', 'true');
            if (warningModalBackdrop) {
                warningModalBackdrop.remove();
                warningModalBackdrop = null;
            }
            document.body.classList.remove('modal-open');
        }

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
            const count = Array.from(document.querySelectorAll('#batchOutingBody .client-select'))
                .filter((select) => select.value)
                .length;
            totalPresentInput.value = count || '';
        }

        function addRow(rowData = {}) {
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
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;

            document.getElementById('batchOutingBody').appendChild(row);
            $(row).find('.client-select').select2({
                placeholder: 'Type to search...',
                allowClear: true,
            });
            $(row).find('.client-select').on('change', updateTotalPresent);
            const rowRemarks = row.querySelector('.row-remarks');
            if (rowData.remarks) {
                rowRemarks.value = rowData.remarks;
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

            if (storedMeta) {
                if (storedMeta.service_date) {
                    document.getElementById('service_date').value = storedMeta.service_date;
                }
                if (storedMeta.time_start) {
                    timeStartInput.value = storedMeta.time_start;
                }
                if (storedMeta.time_end) {
                    timeEndInput.value = storedMeta.time_end;
                }
                if (storedMeta.remarks) {
                    document.getElementById('remarks').value = storedMeta.remarks;
                }
                sessionStorage.removeItem('batchOutingDraft');
                updateUnits();
            }

            if (draftRows.length > 0) {
                draftRows.forEach((row) => addRow(row));
            } else {
                addRow();
            }

            document.getElementById('attendance2026Form').addEventListener('submit', async function(event) {
                if (pendingSubmit) {
                    return;
                }
                const rows = document.querySelectorAll('#batchOutingBody tr');
                if (rows.length === 0) {
                    event.preventDefault();
                    showFormAlert('Please add at least one client.');
                    return;
                }
                event.preventDefault();
                clearFormAlert();

                const shouldContinue = await window.checkSubmissionDuplicates({
                    type: 'batch_outing',
                    service_date: document.getElementById('service_date').value,
                    submitted_by: @json(auth()->id()),
                    time_start: document.getElementById('time_start').value,
                    time_end: document.getElementById('time_end').value,
                    viewRoute: 'cprs.submissions.show',
                });

                if (!shouldContinue) {
                    return;
                }

                showWarningModal();
            });

            warningModalElement.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
                button.addEventListener('click', hideWarningModal);
            });
        });

        document.getElementById('confirmBatchOutingSubmit').addEventListener('click', () => {
            pendingSubmit = true;
            hideWarningModal();
            document.getElementById('attendance2026Form').submit();
        });
    </script>
@stop
