@extends('adminlte::page')

@section('title', 'Edit Attendance Submission')

@section('content_header')
    <h1>Edit Attendance Submission</h1>
@stop

@section('content')
    @include('partials.flash')

    @php
        $isGroupTherapy = $submission->type === 'group_therapy';
    @endphp

    <form method="POST" action="{{ route('attendance2026.submissions.update', $submission) }}" enctype="multipart/form-data" id="attendanceSubmissionForm">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Submission Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label>Submission Date</label>
                        <input type="text" class="form-control" readonly value="{{ optional($submission->submission_date)->format('m/d/Y') }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Service Date</label>
                        <input type="text" class="form-control" readonly value="{{ optional($submission->service_date)->format('m/d/Y') }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="remarks">Remarks</label>
                        <input type="text" name="remarks" id="remarks" class="form-control" value="{{ old('remarks', $submission->remarks) }}">
                    </div>
                </div>
                @if ($isGroupTherapy)
                    <div class="row">
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
                                <option value="all" {{ $selectedGroupId === 'all' ? 'selected' : '' }}>All Group</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" {{ (string) $selectedGroupId === (string) $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="filterBtn">Filter Clients</button>
                        </div>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label for="submission_attachments">Attachments</label>
                        <input type="file" name="attachments[]" id="submission_attachments" class="form-control" multiple>
                        @if (! empty($submission->attachments))
                            <div class="mt-2 d-flex flex-column gap-1">
                                @foreach ($submission->attachments as $file)
                                    <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
                                    <a href="{{ route('attendance2026.submissions.attachments.download', [$submission, $file]) }}" class="btn btn-sm btn-outline-info">Download {{ $loop->iteration }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isGroupTherapy)
                    <table class="table table-bordered mt-3">
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
                                        <input type="checkbox" name="attendance[{{ $client->id }}][present]" value="1"
                                            {{ $attendanceRows->has($client->id) ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center" colspan="3">
                                        {{ $selectedLevel && $selectedGroupId ? 'No clients found.' : 'Select level of care and group, then filter.' }}
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
                                        <th class="text-center">Present</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($outOfGroupRows as $row)
                                        <tr>
                                            <td>{{ strtoupper($row->client->last_name ?? '') }}, {{ strtoupper($row->client->first_name ?? '') }}</td>
                                            <td class="text-center">{{ $row->client->level_of_care ?? '' }}</td>
                                            <td class="text-center">
                                                <input type="checkbox" name="attendance[{{ $row->client_id }}][present]" value="1" checked>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('attendance2026.submissions.show', $submission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>
@stop

@section('js')
    @if ($isGroupTherapy)
        <script>
            const allGroups = @json($allGroups);

            function updateGroupsForLevel(levelValue) {
                const groupSelect = document.getElementById('group_id');
                groupSelect.innerHTML = '<option value="">Select Group</option><option value="all">All Group</option>';
                const levelId = @json($levels->map(fn ($level) => ['id' => $level->id, 'code' => $level->level_of_care]))
                    .find((level) => level.code === levelValue)?.id;
                const filteredGroups = levelId ? allGroups.filter((group) => group.level_of_care_id === levelId) : [];
                filteredGroups.forEach((group) => {
                    const option = document.createElement('option');
                    option.value = group.id;
                    option.textContent = group.name;
                    groupSelect.appendChild(option);
                });
                groupSelect.disabled = filteredGroups.length === 0;
            }

            document.getElementById('level_of_care').addEventListener('change', function() {
                updateGroupsForLevel(this.value);
            });

            document.getElementById('filterBtn').addEventListener('click', function() {
                const selectedLevel = document.getElementById('level_of_care').value;
                const selectedGroup = document.getElementById('group_id').value;
                if (!selectedLevel || !selectedGroup) {
                    alert('Please select level of care and group before filtering.');
                    return;
                }
                const url = new URL(window.location.href);
                url.searchParams.set('level_of_care', selectedLevel);
                url.searchParams.set('group_id', selectedGroup);
                window.location.href = url.toString();
            });
        </script>
    @endif
@stop
