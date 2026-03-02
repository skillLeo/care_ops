@extends('adminlte::page')

@section('title', 'Productivity - CPRS - CPRS Group')

@section('content_header')
    <h1>Productivity - CPRS - CPRS Group</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <a href="{{ route('cprs.index') }}" class="btn btn-secondary">Back</a>
    </div>
    @include('partials.flash')

    <form method="POST" action="{{ route('cprs.cprs_group_store') }}" id="cprsGroupForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', $selectedDate ?? '') }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="peer_group_id">Peer Group</label>
                <select name="peer_group_id" id="peer_group_id" class="form-control" required {{ $peerGroups->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Select Peer Group</option>
                    @foreach ($peerGroups as $peerGroup)
                        <option value="{{ $peerGroup->id }}" {{ (string) $selectedPeerGroupId === (string) $peerGroup->id ? 'selected' : '' }}>
                            {{ $peerGroup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="total_present">Today's Total Clients</label>
                <input type="number" id="total_present" class="form-control" readonly value="0">
            </div>
        </div>
        <input type="hidden" name="service_code_id" value="{{ $serviceCode->id }}">

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="time_start">Time Start</label>
                <input type="time" name="time_start" id="time_start" class="form-control" step="60" required
                    value="{{ old('time_start', $attendanceMetaTimeStart ?? '') }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="time_end">Time End</label>
                <input type="time" name="time_end" id="time_end" class="form-control" step="60" required
                    value="{{ old('time_end', $attendanceMetaTimeEnd ?? '') }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="units">Units</label>
                <input type="number" name="units" id="units" class="form-control" readonly
                    value="{{ old('units', '') }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-2">
                <label for="remarks">Session Title</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="1" style="resize: vertical;" required>{{ old('remarks', $attendanceMetaRemarks ?? '') }}</textarea>
            </div>
            <div class="col-md-4 mb-2">
                <label for="cprs_group_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="cprs_group_attachments" class="form-control" multiple>
            </div>
        </div>
        <div id="cprsGroupAlert" class="alert alert-warning d-none" role="alert"></div>

        <div class="row">
            <div class="col-md-12 text-right">
                <button type="button" id="markAllBtn" class="btn btn-success mr-2">Mark everyone present</button>
                <button type="button" id="filterBtn" class="btn btn-primary mr-2">Filter</button>
                <a href="{{ route('cprs.cprs_group') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>

        <table class="table table-bordered" id="clientTable">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th class="text-center">LOC</th>
                    <th class="text-center">Present</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        <td class="text-center">{{ $client->level_of_care }}</td>
                        <td class="text-center">
                            <input type="checkbox" id="present-{{ $client->id }}" class="present-checkbox"
                                name="attendance[{{ $client->id }}][present]">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center">
                            {{ $selectedDate && $selectedPeerGroupId ? 'No clients found.' : 'Click filter button to get client list.' }}
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <button type="button" class="btn btn-success mt-3" id="cprsGroupSubmitButton">Submit</button>
    </form>

    <div class="modal fade" id="cprsGroupWarningModal" tabindex="-1" role="dialog" aria-labelledby="cprsGroupWarningLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cprsGroupWarningLabel">Before you submit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This productivity sheet is to be submitted only after completing the notes identified in this report.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmCprsGroupSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.duplicate-check-modal')
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        const timeStartInput = document.getElementById('time_start');
        const timeEndInput = document.getElementById('time_end');
        const unitsInput = document.getElementById('units');
        const cprsGroupAlert = document.getElementById('cprsGroupAlert');
        const warningModalElement = document.getElementById('cprsGroupWarningModal');
        let warningModalInstance = null;
        let warningModalBackdrop = null;
        let pendingSubmit = false;

        function showFormAlert(message) {
            if (!cprsGroupAlert) {
                return;
            }
            cprsGroupAlert.textContent = message;
            cprsGroupAlert.classList.remove('d-none');
        }

        function clearFormAlert() {
            if (!cprsGroupAlert) {
                return;
            }
            cprsGroupAlert.textContent = '';
            cprsGroupAlert.classList.add('d-none');
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

        timeStartInput.addEventListener('change', updateUnits);
        timeEndInput.addEventListener('change', updateUnits);
        updateUnits();

        const table = $('#clientTable').DataTable({
            pageLength: -1,
            lengthMenu: [
                [-1, 25, 50, 100],
                ['All', 25, 50, 100],
            ],
        });

        const initialPeerGroupId = @json((string) $selectedPeerGroupId);
        const hasFilters = @json((bool) ($selectedDate && $selectedPeerGroupId));
        const emptyMessage = hasFilters ? 'No clients found.' : 'Click filter button to get client list.';

        function updateTotalPresent() {
            const count = document.querySelectorAll('.present-checkbox:checked').length;
            document.getElementById('total_present').value = count || '';
            updateMarkAllLabel();
        }

        function updateMarkAllLabel() {
            const checkboxes = Array.from(document.querySelectorAll('.present-checkbox'));
            const allChecked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
            document.getElementById('markAllBtn').textContent = allChecked
                ? 'Mark everyone absent'
                : 'Mark everyone present';
        }

        function resetClientList(message = emptyMessage) {
            table.clear().draw();
            if (message) {
                table.row.add([message, '', '']).draw(false);
            }
            document.getElementById('total_present').value = '';
            updateMarkAllLabel();
        }

        updateTotalPresent();

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('present-checkbox')) {
                updateTotalPresent();
            }
        });

        document.getElementById('service_date').addEventListener('change', () => {
            timeStartInput.value = '';
            timeEndInput.value = '';
            unitsInput.value = '';
            document.getElementById('remarks').value = '';
            document.querySelectorAll('.present-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });
            updateTotalPresent();
            clearFormAlert();
        });

        function applyFilters() {
            const selectedDate = document.getElementById('service_date').value;
            const selectedPeerGroup = document.getElementById('peer_group_id').value;
            if (!selectedDate || !selectedPeerGroup) {
                showFormAlert('Please select service date and peer group before filtering.');
                return;
            }
            clearFormAlert();
            const url = new URL(window.location.href);
            url.searchParams.set('date', selectedDate);
            url.searchParams.set('peer_group_id', selectedPeerGroup);
            const timeStart = document.getElementById('time_start').value;
            const timeEnd = document.getElementById('time_end').value;
            const remarks = document.getElementById('remarks').value;
            if (timeStart) {
                url.searchParams.set('time_start', timeStart);
            } else {
                url.searchParams.delete('time_start');
            }
            if (timeEnd) {
                url.searchParams.set('time_end', timeEnd);
            } else {
                url.searchParams.delete('time_end');
            }
            if (remarks) {
                url.searchParams.set('remarks', remarks);
            } else {
                url.searchParams.delete('remarks');
            }
            window.location.href = url.toString();
        }

        document.getElementById('filterBtn').addEventListener('click', applyFilters);

        document.getElementById('markAllBtn').addEventListener('click', function() {
            const checkboxes = Array.from(document.querySelectorAll('.present-checkbox'));
            const allChecked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = !allChecked;
            });
            updateTotalPresent();
        });

        const cprsGroupForm = document.getElementById('cprsGroupForm');
        const cprsGroupSubmitButton = document.getElementById('cprsGroupSubmitButton');

        async function handleCprsGroupSubmit(event) {
            if (pendingSubmit) {
                return;
            }
            if (event) {
                event.preventDefault();
            }
            const selectedCount = document.querySelectorAll('.present-checkbox:checked').length;
            if (selectedCount === 0) {
                showFormAlert('Please select at least one client before submitting.');
                return;
            }
            clearFormAlert();

            const shouldContinue = await window.checkSubmissionDuplicates({
                type: 'cprs_group',
                service_date: document.getElementById('service_date').value,
                peer_group_id: document.getElementById('peer_group_id').value,
                viewRoute: 'cprs.submissions.show',
            });

            if (!shouldContinue) {
                return;
            }

            showWarningModal();
        }

        cprsGroupForm.addEventListener('submit', handleCprsGroupSubmit);
        cprsGroupSubmitButton.addEventListener('click', async () => {
            if (!cprsGroupForm.reportValidity()) {
                return;
            }
            handleCprsGroupSubmit();
        });

        document.getElementById('confirmCprsGroupSubmit').addEventListener('click', () => {
            pendingSubmit = true;
            hideWarningModal();
            cprsGroupForm.submit();
        });

        warningModalElement.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
            button.addEventListener('click', hideWarningModal);
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (initialPeerGroupId) {
                document.getElementById('peer_group_id').value = initialPeerGroupId;
            }
        });
    </script>
@stop
