@extends('adminlte::page')

@section('title', 'Productivity - Clinician - Clinical Group')

@section('content_header')
    <h1>Productivity - Clinician - Clinical Group</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3">
        <a href="{{ route('clinical-notes.index') }}" class="btn btn-secondary">Back</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('clinical-notes.group_therapy_store') }}" id="groupTherapyForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" required
                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', $selectedDate ?? '') }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="level_of_care">Level of Care</label>
                <select name="level_of_care" id="level_of_care" class="form-control" required>
                    <option value="">Select Level</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->level_of_care }}" {{ $selectedLevel === $level->level_of_care ? 'selected' : '' }}>
                            {{ $level->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="group_id">Group</label>
                <select name="group_id" id="group_id" class="form-control" required {{ $groups->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Select Group</option>
                    <option value="all" {{ $selectedGroupId === 'all' ? 'selected' : '' }}>All Groups</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" {{ (string) $selectedGroupId === (string) $group->id ? 'selected' : '' }}>
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
                        <option value="{{ $code->id }}" {{ (string) $selectedServiceCodeId === (string) $code->id ? 'selected' : '' }}>
                            {{ $code->friendly_name }}
                        </option>
                    @endforeach
                </select>
                @if ($selectedLevel && $filteredServiceCodes->isEmpty())
                    <small class="text-danger">No session types available for this level of care.</small>
                @endif
            </div>
            <div class="col-md-4 mb-2">
                <label for="time_start">Time Start</label>
                <input type="time" name="time_start" id="time_start" class="form-control" step="60" required value="{{ old('time_start', request('time_start')) }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="time_end">Time End</label>
                <input type="time" name="time_end" id="time_end" class="form-control" step="60" required value="{{ old('time_end', request('time_end')) }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="remarks">Group Note Title</label>
                <input type="text" name="remarks" id="remarks" class="form-control" value="{{ old('remarks', request('remarks')) }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="total_complete">Notes Completed</label>
                <input type="number" id="total_complete" class="form-control" readonly value="">
            </div>
            <div class="col-md-4 mb-2">
                <label for="group_therapy_attachments">Attachments</label>
                <input type="file" name="attachments[]" id="group_therapy_attachments" class="form-control" multiple>
                <div id="storedAttachmentsInfo" class="mt-2 text-muted small"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-right">
                <button type="button" id="markAllBtn" class="btn btn-success mr-2">Mark all notes complete</button>
                <button type="button" id="filterBtn" class="btn btn-primary mr-2">Filter</button>
                
                <a href="{{ route('clinical-notes.group_therapy') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>

        <table class="table table-bordered" id="clientTable">
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
                            <input type="checkbox" id="complete-{{ $client->id }}" class="complete-checkbox"
                                name="attendance[{{ $client->id }}][complete]">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center">
                            {{ $selectedDate && $selectedLevel && $selectedGroupId ? 'No clients found.' : 'Click filter button to get client list.' }}
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <button type="submit" class="btn btn-success mt-3" {{ $filteredServiceCodes->isEmpty() ? 'disabled' : '' }}>Submit</button>
    </form>

    <div class="modal fade" id="groupTherapyWarningModal" tabindex="-1" role="dialog" aria-labelledby="groupTherapyWarningLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupTherapyWarningLabel">Before you submit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    This productivity sheet is to be submitted only after completing the notes identified in this report.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmGroupTherapySubmit">Submit</button>
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
        const table = $('#clientTable').DataTable({
            pageLength: -1,
            lengthMenu: [
                [-1, 25, 50, 100],
                ['All', 25, 50, 100],
            ],
        });

        const levels = @json($levelOptions);
        const allGroups = @json($allGroups);
        const allServiceCodes = @json($serviceCodeOptions);
        const initialGroupId = @json((string) $selectedGroupId);
        const hasFilters = @json((bool) ($selectedDate && $selectedLevel && $selectedGroupId));
        const emptyMessage = hasFilters ? 'No clients found.' : 'Click filter button to get client list.';

        const warningModalElement = document.getElementById('groupTherapyWarningModal');
        let warningModalInstance = null;
        let warningModalBackdrop = null;
        let pendingSubmit = false;

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

        function resetClientList(message = emptyMessage) {
            table.clear().draw();
            if (message) {
                table.row.add([message, '', '']).draw(false);
            }
            document.getElementById('total_complete').value = '';
            updateMarkAllLabel();
        }

        updateTotalComplete();

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('complete-checkbox')) {
                updateTotalComplete();
            }
        });

        function applyFilters() {
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
            const serviceCodeId = document.getElementById('service_code_id').value;
            if (serviceCodeId) {
                url.searchParams.set('service_code_id', serviceCodeId);
            } else {
                url.searchParams.delete('service_code_id');
            }
            const remarks = document.getElementById('remarks').value;
            if (remarks) {
                url.searchParams.set('remarks', remarks);
            } else {
                url.searchParams.delete('remarks');
            }
            window.location.href = url.toString();
        }

        document.getElementById('filterBtn').addEventListener('click', applyFilters);


        document.getElementById('markAllBtn').addEventListener('click', function() {
            const checkboxes = Array.from(document.querySelectorAll('.complete-checkbox'));
            const allChecked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = !allChecked;
            });
            updateTotalComplete();
        });

        const initialServiceCodeId = @json((string) $selectedServiceCodeId);

        let isInitialLoad = true;

        document.getElementById('level_of_care').addEventListener('change', function() {
            const level = this.value;
            const serviceSelect = document.getElementById('service_code_id');
            const groupSelect = document.getElementById('group_id');
            serviceSelect.innerHTML = '<option value="">Select Session Type</option>';
            serviceSelect.disabled = true;
            groupSelect.innerHTML = '<option value="">Select Group</option>';
            groupSelect.disabled = true;
            if (!isInitialLoad) {
                resetClientList('Click filter button to get client list.');
            }

            if (!level) {
                isInitialLoad = false;
                return;
            }

            const levelId = levels.find((item) => item.code === level)?.id;
            const filteredGroups = levelId
                ? allGroups.filter((group) => group.level_of_care_id === levelId)
                : [];
            const allGroupsOption = document.createElement('option');
            allGroupsOption.value = 'all';
            allGroupsOption.textContent = 'All Groups';
            if (initialGroupId && initialGroupId === 'all') {
                allGroupsOption.selected = true;
            }
            groupSelect.appendChild(allGroupsOption);
            filteredGroups.forEach((group) => {
                const option = document.createElement('option');
                option.value = group.id;
                option.textContent = group.name;
                if (initialGroupId && initialGroupId === String(group.id)) {
                    option.selected = true;
                }
                groupSelect.appendChild(option);
            });
            groupSelect.disabled = filteredGroups.length === 0;

            const filteredCodes = levelId
                ? allServiceCodes.filter((code) => code.level_ids.includes(levelId))
                : [];
            filteredCodes.forEach((code) => {
                const option = document.createElement('option');
                option.value = code.id;
                option.textContent = code.friendly_name ?? '';
                if (initialServiceCodeId && initialServiceCodeId === String(code.id)) {
                    option.selected = true;
                }
                serviceSelect.appendChild(option);
            });
            serviceSelect.disabled = filteredCodes.length === 0;
            isInitialLoad = false;
        });

        ['service_date', 'group_id'].forEach((fieldId) => {
            document.getElementById(fieldId).addEventListener('change', function() {
                resetClientList('Click filter button to get client list.');
            });
        });

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

        $('#groupTherapyForm').on('submit', async function(e) {
            if (pendingSubmit) {
                return;
            }
            $('#groupTherapyForm input[type=hidden][name^="attendance"]').remove();

            table.rows({ search: 'none' }).every(function() {
                const $row = $(this.node());
                const checkbox = $row.find('.complete-checkbox');
                const namePrefix = checkbox.attr('name').replace(/\[complete\]$/, '');
                const isChecked = checkbox.prop('checked');

                $('<input>').attr({
                    type: 'hidden',
                    name: `${namePrefix}[complete]`,
                    value: isChecked ? 1 : 0
                }).appendTo('#groupTherapyForm');
            });

            const hasSelection = document.querySelectorAll('.complete-checkbox:checked').length > 0;
            if (!hasSelection) {
                e.preventDefault();
                alert('Please select at least one client as complete before submitting.');
                return;
            }
            e.preventDefault();

            const shouldContinue = await window.checkSubmissionDuplicates({
                type: 'clinical_group_therapy',
                service_date: document.getElementById('service_date').value,
                client_group_id: document.getElementById('group_id').value,
                viewRoute: 'clinical-notes.submissions.show',
            });

            if (!shouldContinue) {
                return;
            }

            showWarningModal();
        });

        document.getElementById('confirmGroupTherapySubmit').addEventListener('click', () => {
            pendingSubmit = true;
            hideWarningModal();
            document.getElementById('groupTherapyForm').submit();
        });

        warningModalElement.querySelectorAll('[data-dismiss="modal"]').forEach((button) => {
            button.addEventListener('click', hideWarningModal);
        });

        if (document.querySelectorAll('.complete-checkbox').length === 0) {
            resetClientList(emptyMessage);
        }

        if (document.getElementById('level_of_care').value) {
            document.getElementById('level_of_care').dispatchEvent(new Event('change'));
        } else {
            isInitialLoad = false;
        }

        const storedDraft = sessionStorage.getItem('clinicalNotesDraft');
        if (storedDraft) {
            try {
                const draft = JSON.parse(storedDraft);
                if (draft?.remarks) {
                    document.getElementById('remarks').value = draft.remarks;
                }
                if (Array.isArray(draft?.attendanceRows)) {
                    draft.attendanceRows.forEach((row) => {
                        const checkbox = document.querySelector(`#complete-${row.clientId}`);
                        if (checkbox) {
                            checkbox.checked = row.complete;
                        }
                    });
                    updateTotalComplete();
                }
                if (Array.isArray(draft?.storedAttachments) && draft.storedAttachments.length > 0) {
                    draft.storedAttachments.forEach((file) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'stored_attachments[]';
                        input.value = file;
                        document.getElementById('groupTherapyForm').appendChild(input);
                    });
                    document.getElementById('storedAttachmentsInfo').textContent =
                        `${draft.storedAttachments.length} attachment(s) already added.`;
                }
            } catch (error) {
                console.warn('Unable to restore clinical notes draft.', error);
            }
            sessionStorage.removeItem('clinicalNotesDraft');
        }
    </script>
@stop
