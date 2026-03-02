@extends('adminlte::page')

@section('title', 'Edit Attendance Submission')

@section('content_header')
    <h1>Edit Attendance Submission</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $isCprsGroup = $submission->type === 'cprs_group';
    @endphp

    <form method="POST" action="{{ route('cprs.submissions.update', $submission) }}" enctype="multipart/form-data" id="cprsSubmissionForm">
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
                @if ($isCprsGroup)
                    <div class="row">
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
                                    <a href="{{ route('cprs.submissions.attachments.download', [$submission, $file]) }}" class="btn btn-sm btn-outline-info">Download {{ $loop->iteration }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isCprsGroup)
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
                                        {{ $selectedPeerGroupId ? 'No clients found.' : 'Select a peer group, then filter.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($outOfGroupRows->isNotEmpty())
                        <div class="mt-4">
                            <h5>Previously Submitted (Not in Selected Peer Group)</h5>
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
                <a href="{{ route('cprs.submissions.show', $submission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>
@stop

@section('js')
    @if ($isCprsGroup)
        <script>
            document.getElementById('filterBtn').addEventListener('click', function() {
                const selectedPeerGroup = document.getElementById('peer_group_id').value;
                if (!selectedPeerGroup) {
                    alert('Please select a peer group before filtering.');
                    return;
                }
                const url = new URL(window.location.href);
                url.searchParams.set('peer_group_id', selectedPeerGroup);
                window.location.href = url.toString();
            });
        </script>
    @endif
@stop
