@extends('adminlte::page')

@section('title', 'Clinical Submission')

@section('content_header')
    <h1>Clinical Submission</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $isGroupTherapy = $submission->type === 'clinical_group_therapy';
    @endphp

    <form method="POST" action="{{ route('clinical-notes.submissions.update', $submission) }}" enctype="multipart/form-data" id="clinicalSubmissionEditForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Submission Details</h3>
                @can('clinical_notes.download')
                    <a href="{{ route('clinical-notes.submissions.download', $submission) }}" class="btn btn-sm btn-secondary">Download</a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label>Submission Date</label>
                        <input type="text" class="form-control" readonly value="{{ optional($submission->submission_date)->format('m/d/Y') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Attendance Type</label>
                        <input type="text" class="form-control" readonly value="{{ $submission->type }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Submitted By</label>
                        <input type="text" class="form-control" readonly value="{{ $submission->submittedBy?->name }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="remarks">{{ $isGroupTherapy ? 'Group Note Title' : 'Submission Notes' }}</label>
                        <input type="text" name="remarks" id="remarks" class="form-control"
                            value="{{ old('remarks', $isGroupTherapy ? ($selectedRemarks ?? $submission->remarks) : $submission->remarks) }}">
                    </div>
                </div>

                @if ($isGroupTherapy)
                    <div class="row mt-2">
                        <div class="col-md-4 mb-2">
                            <label for="service_date">Service Date</label>
                            <input type="date" name="service_date" id="service_date" class="form-control" required
                                max="{{ \Carbon\Carbon::today()->toDateString() }}"
                                value="{{ old('service_date', $selectedDate ?? optional($submission->service_date)->toDateString()) }}">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="level_of_care">Level of Care</label>
                            <select name="level_of_care" id="level_of_care" class="form-control" required>
                                <option value="">Select Level</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->level_of_care }}" {{ (old('level_of_care', $selectedLevel) === $level->level_of_care) ? 'selected' : '' }}>
                                        {{ $level->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="group_id">Group</label>
                            <select name="group_id" id="group_id" class="form-control" required {{ $groups->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Select Group</option>
                                <option value="all" {{ (old('group_id', $selectedGroupId) === 'all') ? 'selected' : '' }}>All Groups</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" {{ (string) old('group_id', $selectedGroupId) === (string) $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label for="service_code_id">Session Type</label>
                            <select name="service_code_id" id="service_code_id" class="form-control" required {{ $filteredServiceCodes->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Select Session Type</option>
                                @foreach ($filteredServiceCodes as $code)
                                    <option value="{{ $code->id }}" {{ (string) old('service_code_id', $selectedServiceCodeId) === (string) $code->id ? 'selected' : '' }}>
                                        {{ $code->friendly_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="time_start">Time Start</label>
                            <input type="time" name="time_start" id="time_start" class="form-control" step="60" required
                                value="{{ old('time_start', $selectedTimeStart ?? ($submission->time_start ? substr((string) $submission->time_start, 0, 5) : '')) }}">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="time_end">Time End</label>
                            <input type="time" name="time_end" id="time_end" class="form-control" step="60" required
                                value="{{ old('time_end', $selectedTimeEnd ?? ($submission->time_end ? substr((string) $submission->time_end, 0, 5) : '')) }}">
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4 mb-2">
                            <label for="total_complete">Notes Completed</label>
                            <input type="number" id="total_complete" class="form-control" readonly value="">
                        </div>
                        <div class="col-md-8 mb-2 d-flex align-items-end justify-content-end">
                            <button type="button" id="markAllBtn" class="btn btn-success mr-2">Mark all notes complete</button>
                            <button type="button" class="btn btn-primary" id="filterBtn">Filter Clients</button>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label for="submission_attachments">Attachments</label>
                        <input type="file" name="attachments[]" id="submission_attachments" class="form-control" multiple>
                        @if (! empty($submission->attachments))
                            <div class="mt-2 d-flex flex-column gap-2">
                                @foreach ($submission->attachments as $file)
                                    <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
                                    <a href="{{ route('clinical-notes.submissions.attachments.download', [$submission, $file]) }}" class="btn btn-outline-info btn-sm">Download Attachment {{ $loop->iteration }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isGroupTherapy)
                    <table class="table table-bordered mt-3" id="clientTable">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th class="text-center">LOC</th>
                                <th class="text-center">Note Complete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $client)
                                <tr>
                                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                                    <td class="text-center">{{ $client->level_of_care }}</td>
                                    <td class="text-center">
                                        <input type="checkbox" class="complete-checkbox" name="attendance[{{ $client->id }}][present]" value="1"
                                            {{ $noteRows->has($client->id) ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center" colspan="3">
                                        {{ $selectedDate && $selectedLevel && $selectedGroupId ? 'No clients found.' : 'Select date, level of care and group, then filter.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($outOfGroupRows->isNotEmpty())
                        <div class="mt-4">
                            <h5>Previously Submitted (Not in Selected Group)</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th class="text-center">LOC</th>
                                        <th class="text-center">Note Complete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($outOfGroupRows as $row)
                                        <tr>
                                            <td>{{ strtoupper($row->client->last_name ?? '') }}, {{ strtoupper($row->client->first_name ?? '') }}</td>
                                            <td class="text-center">{{ $row->client->level_of_care ?? '' }}</td>
                                            <td class="text-center">
                                                <input type="checkbox" class="complete-checkbox" name="attendance[{{ $row->client_id }}][present]" value="1" checked>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <table class="table table-bordered mt-3">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">#</th>
                                <th>Client Name</th>
                                <th class="text-center">Service Date</th>
                                <th class="text-center">Time Start</th>
                                <th class="text-center">Time End</th>
                                <th class="text-center">Units</th>
                                <th>Individual Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($noteRows as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row->id }}">
                                        <select class="form-control" name="rows[{{ $index }}][client_id]" required>
                                            @foreach (($clientsList ?? collect()) as $client)
                                                <option value="{{ $client->id }}" {{ (string) old("rows.$index.client_id", $row->client_id) === (string) $client->id ? 'selected' : '' }}>
                                                    {{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="date" class="form-control" name="rows[{{ $index }}][service_date]"
                                            value="{{ old("rows.$index.service_date", optional($row->service_date)->toDateString()) }}" required>
                                    </td>
                                    <td class="text-center">
                                        <input type="time" class="form-control" name="rows[{{ $index }}][time_start]" step="60"
                                            value="{{ old("rows.$index.time_start", $row->time_start ? substr((string) $row->time_start, 0, 5) : '') }}" required>
                                    </td>
                                    <td class="text-center">
                                        <input type="time" class="form-control" name="rows[{{ $index }}][time_end]" step="60"
                                            value="{{ old("rows.$index.time_end", $row->time_end ? substr((string) $row->time_end, 0, 5) : '') }}" required>
                                    </td>
                                    <td class="text-center">
                                        <input type="number" class="form-control" name="rows[{{ $index }}][units]" min="0"
                                            value="{{ old("rows.$index.units", $row->units) }}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="rows[{{ $index }}][remarks]"
                                            value="{{ old("rows.$index.remarks", $row->remarks) }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('clinical-notes.submissions.show', $submission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>

    <div class="modal fade" id="clinicalSubmissionWarningModal" tabindex="-1" role="dialog"
        aria-labelledby="clinicalSubmissionWarningLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clinicalSubmissionWarningLabel">Before you submit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This productivity sheet is to be submitted only after completing the notes identified in this report.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmClinicalSubmissionUpdate">Submit</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        const warningModalElement = document.getElementById('clinicalSubmissionWarningModal');
        let warningModalInstance = null;
        let warningModalBackdrop = null;
        let pendingSubmit = false;

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

        document.getElementById('clinicalSubmissionEditForm').addEventListener('submit', (event) => {
            if (pendingSubmit) {
                return;
            }
            event.preventDefault();
            showWarningModal();
        });

        document.getElementById('confirmClinicalSubmissionUpdate').addEventListener('click', () => {
            pendingSubmit = true;
            hideWarningModal();
            document.getElementById('clinicalSubmissionEditForm').submit();
        });

        warningModalElement.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
            button.addEventListener('click', hideWarningModal);
        });
    </script>

    @if ($isGroupTherapy)
        <script>
            const levelMap = @json($levelOptions ?? []);
            const allGroups = @json($allGroups ?? []);
            const allServiceCodes = @json($serviceCodeOptions ?? []);
            const initialGroupId = @json((string) old('group_id', $selectedGroupId ?? ''));
            const initialServiceCodeId = @json((string) old('service_code_id', $selectedServiceCodeId ?? ''));

            function updateTotalComplete() {
                const count = document.querySelectorAll('.complete-checkbox:checked').length;
                document.getElementById('total_complete').value = count || '';
                updateMarkAllLabel();
            }

            function updateMarkAllLabel() {
                const checkboxes = Array.from(document.querySelectorAll('.complete-checkbox'));
                const allChecked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
                document.getElementById('markAllBtn').textContent = allChecked
                    ? 'Mark all notes incomplete'
                    : 'Mark all notes complete';
            }

            function updateGroupsForLevel(levelValue) {
                const groupSelect = document.getElementById('group_id');
                const prevValue = groupSelect.value || initialGroupId;
                groupSelect.innerHTML = '<option value="">Select Group</option><option value="all">All Groups</option>';
                const levelId = levelMap.find((level) => level.code === levelValue)?.id;
                const filteredGroups = levelId ? allGroups.filter((group) => Number(group.level_of_care_id) === Number(levelId)) : [];
                filteredGroups.forEach((group) => {
                    const option = document.createElement('option');
                    option.value = String(group.id);
                    option.textContent = group.name;
                    option.selected = String(prevValue) === String(group.id);
                    groupSelect.appendChild(option);
                });
                if (prevValue === 'all') {
                    groupSelect.value = 'all';
                }
                groupSelect.disabled = filteredGroups.length === 0;
            }

            function updateServiceCodesForLevel(levelValue) {
                const serviceCodeSelect = document.getElementById('service_code_id');
                const prevValue = serviceCodeSelect.value || initialServiceCodeId;
                serviceCodeSelect.innerHTML = '<option value="">Select Session Type</option>';
                const levelId = levelMap.find((level) => level.code === levelValue)?.id;
                const filteredCodes = levelId
                    ? allServiceCodes.filter((code) => Array.isArray(code.level_ids) && code.level_ids.map(Number).includes(Number(levelId)))
                    : [];

                filteredCodes.forEach((code) => {
                    const option = document.createElement('option');
                    option.value = String(code.id);
                    option.textContent = code.friendly_name;
                    option.selected = String(prevValue) === String(code.id);
                    serviceCodeSelect.appendChild(option);
                });

                serviceCodeSelect.disabled = filteredCodes.length === 0;
            }

            document.addEventListener('change', function(event) {
                if (event.target.classList.contains('complete-checkbox')) {
                    updateTotalComplete();
                }
            });

            document.getElementById('markAllBtn').addEventListener('click', function() {
                const checkboxes = Array.from(document.querySelectorAll('.complete-checkbox'));
                const allChecked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = !allChecked;
                });
                updateTotalComplete();
            });

            document.getElementById('level_of_care').addEventListener('change', function() {
                updateGroupsForLevel(this.value);
                updateServiceCodesForLevel(this.value);
            });

            document.getElementById('filterBtn').addEventListener('click', function() {
                const selectedDate = document.getElementById('service_date').value;
                const selectedLevel = document.getElementById('level_of_care').value;
                const selectedGroup = document.getElementById('group_id').value;

                if (!selectedDate || !selectedLevel || !selectedGroup) {
                    alert('Please select service date, level of care, and group before filtering.');
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('date', selectedDate);
                url.searchParams.set('level_of_care', selectedLevel);
                url.searchParams.set('group_id', selectedGroup);

                const serviceCodeId = document.getElementById('service_code_id').value;
                if (serviceCodeId) {
                    url.searchParams.set('service_code_id', serviceCodeId);
                } else {
                    url.searchParams.delete('service_code_id');
                }

                const timeStart = document.getElementById('time_start').value;
                const timeEnd = document.getElementById('time_end').value;
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

                const remarks = document.getElementById('remarks').value;
                if (remarks) {
                    url.searchParams.set('remarks', remarks);
                } else {
                    url.searchParams.delete('remarks');
                }

                window.location.href = url.toString();
            });

            updateGroupsForLevel(document.getElementById('level_of_care').value);
            updateServiceCodesForLevel(document.getElementById('level_of_care').value);
            updateTotalComplete();
        </script>
    @endif
@stop
