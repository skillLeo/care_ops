@extends('adminlte::page')

@section('title', 'Productivity - CPRS - Peer Individual')

@section('content_header')
    <h1>Productivity - CPRS - Peer Individual</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <div class="btn-group" role="group" aria-label="Productivity sheet actions">
            <a href="{{ route('cprs.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('cprs.prod_sheet') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('cprs.prod_sheet_store') }}" id="prodSheetForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-3 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', '') }}">
            </div>
            <div class="col-md-3 mb-2">
                <label for="total_present">Today's Total Clients</label>
                <input type="number" id="total_present" class="form-control" readonly value="0">
            </div>
            <div class="col-md-3 mb-2">
                <label for="total_units">Today's Total Units</label>
                <input type="number" id="total_units" class="form-control" readonly value="0">
            </div>
            <div class="col-md-3 mb-2">
                <label for="prod_sheet_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="prod_sheet_attachments" class="form-control" multiple>
            </div>
        </div>
        <div id="prodSheetAlert" class="alert alert-warning d-none" role="alert"></div>
        <input type="hidden" name="service_code_id" value="{{ $serviceCode->id }}">

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
                            <th style="width: 40%;">Client</th>
                            <th>Individual Remarks</th>
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

    <div class="modal fade" id="prodSheetWarningModal" tabindex="-1" role="dialog" aria-labelledby="prodSheetWarningLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prodSheetWarningLabel">Before you submit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This productivity sheet is to be submitted only after completing the notes identified in this report.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmProdSheetSubmit">Submit</button>
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
        const storedDraft = sessionStorage.getItem('prodSheetDraft');
        const storedMeta = storedDraft ? JSON.parse(storedDraft) : null;
        const draftRows = storedMeta?.rows ?? existingRows;
        const prodSheetAlert = document.getElementById('prodSheetAlert');
        const warningModalElement = document.getElementById('prodSheetWarningModal');
        let warningModalInstance = null;
        let warningModalBackdrop = null;
        let pendingSubmit = false;
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
                updateTotals();
                return;
            }
            const diff = Math.max(0, endMinutes - startMinutes);
            unitsInput.value = Math.ceil(diff / 15);
            updateTotals();
        }

        function updateTotals() {
            const rows = document.querySelectorAll('#prodSheetBody tr');
            let totalClients = 0;
            let totalUnits = 0;

            rows.forEach((row) => {
                const clientId = row.querySelector('.client-select')?.value;
                const startValue = row.querySelector('.time-start')?.value;
                const endValue = row.querySelector('.time-end')?.value;
                const unitsValue = row.querySelector('.units-input')?.value;
                const isComplete = clientId && startValue && endValue;

                if (isComplete) {
                    totalClients += 1;
                    const units = parseInt(unitsValue, 10);
                    if (!Number.isNaN(units)) {
                        totalUnits += units;
                    }
                }
            });

            document.getElementById('total_present').value = totalClients;
            document.getElementById('total_units').value = totalUnits;
        }

        function showFormAlert(message) {
            if (!prodSheetAlert) {
                return;
            }
            prodSheetAlert.textContent = message;
            prodSheetAlert.classList.remove('d-none');
        }

        function clearFormAlert() {
            if (!prodSheetAlert) {
                return;
            }
            prodSheetAlert.textContent = '';
            prodSheetAlert.classList.add('d-none');
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
            $(row).find('.client-select').on('change', updateTotals);

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
                updateTotals();
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
                        showFormAlert('Please enter start/end times in the previous row first.');
                        return;
                    }
                    const duration = prevEndMinutes - prevStartMinutes;
                    if (duration <= 0) {
                        showFormAlert('Previous row end time must be after the start time.');
                        return;
                    }
                    clearFormAlert();
                    const roundedStart = roundUpToQuarterHour(prevEndMinutes);
                    row.querySelector('.time-start').value = minutesToTime(roundedStart);
                    row.querySelector('.time-end').value = minutesToTime(roundedStart + duration);
                    updateUnits(row);
                });
            }

            updateTotals();
            rowIndex += 1;

            if (rowData.client_id) {
                row.querySelector('.client-select').value = String(rowData.client_id);
                $(row).find('.client-select').trigger('change');
                updateTotals();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const addButton = document.getElementById('addRowButton');
            if (addButton) {
                addButton.addEventListener('click', addRow);
            }
            if (storedMeta) {
                document.getElementById('service_date').value = storedMeta.service_date ?? '';
                sessionStorage.removeItem('prodSheetDraft');
            }
            if (draftRows.length > 0) {
                draftRows.forEach((row) => addRow(row));
            } else {
                addRow();
            }
            updateTotals();


            document.getElementById('prodSheetForm').addEventListener('submit', async function(event) {
                if (pendingSubmit) {
                    return;
                }
                const rows = document.querySelectorAll('#prodSheetBody tr');
                if (rows.length === 0) {
                    event.preventDefault();
                    showFormAlert('Please add at least one client.');
                    return;
                }
                event.preventDefault();
                clearFormAlert();

                const shouldContinue = await window.checkSubmissionDuplicates({
                    type: 'prod_sheet',
                    service_date: document.getElementById('service_date').value,
                    submitted_by: @json(auth()->id()),
                    viewRoute: 'cprs.submissions.show',
                });

                if (!shouldContinue) {
                    return;
                }

                showWarningModal();
            });
        });

        document.getElementById('confirmProdSheetSubmit').addEventListener('click', () => {
            pendingSubmit = true;
            hideWarningModal();
            document.getElementById('prodSheetForm').submit();
        });

        warningModalElement.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
            button.addEventListener('click', hideWarningModal);
        });
    </script>
@stop
