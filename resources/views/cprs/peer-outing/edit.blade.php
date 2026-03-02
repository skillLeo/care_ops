@extends('adminlte::page')

@section('title', 'Edit Peer Outing Submission')

@section('content_header')
    <h1>Edit Peer Outing Submission</h1>
@stop

@section('content')
    @include('partials.flash')

    <form method="POST" action="{{ route('cprs.submissions.update', $submission) }}" enctype="multipart/form-data" id="peerOutingEditForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Submission Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label for="service_date">Service Date</label>
                        <input type="date" id="service_date" name="service_date" class="form-control" required value="{{ old('service_date', optional($submission->service_date)->toDateString()) }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="time_start">Time Start</label>
                        <input type="time" id="time_start" name="time_start" class="form-control" step="60" required value="{{ old('time_start', $submission->time_start ? substr((string) $submission->time_start, 0, 5) : '') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="time_end">Time End</label>
                        <input type="time" id="time_end" name="time_end" class="form-control" step="60" required value="{{ old('time_end', $submission->time_end ? substr((string) $submission->time_end, 0, 5) : '') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="units">Units</label>
                        <input type="number" id="units" name="units" class="form-control" min="0" required value="{{ old('units', ($attendanceRows->first()->units ?? 1)) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label for="remarks">Session Title</label>
                        <input type="text" name="remarks" id="remarks" class="form-control" value="{{ old('remarks', $submission->remarks) }}">
                    </div>
                </div>

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

                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Peer Outing Rows</strong>
                        <button type="button" class="btn btn-sm btn-success" id="addPeerOutingRow">+ Add Client</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0" id="peerOutingRowsTable">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Individual Remarks</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody id="peerOutingRowsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('cprs.submissions.show', $submission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        const clients = @json(($clientsList ?? collect())->map(fn($c) => ['id' => $c->id, 'label' => strtoupper($c->last_name) + ', ' + strtoupper($c->first_name)])->values());
        const existingRows = @json(old('rows', ($attendanceRows ?? collect())->map(fn($row) => [
            'client_id' => $row->client_id,
            'remarks' => $row->remarks,
        ])->values()->all()));

        const tbody = document.getElementById('peerOutingRowsBody');

        function clientOptions(selectedId = '') {
            return [`<option value="">Select Client</option>`, ...clients.map((client) =>
                `<option value="${client.id}" ${String(selectedId) === String(client.id) ? 'selected' : ''}>${client.label}</option>`
            )].join('');
        }

        function renderRow(rowData = {}) {
            const index = tbody.children.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select class="form-control form-control-sm" name="rows[${index}][client_id]" required>${clientOptions(rowData.client_id || '')}</select></td>
                <td><input type="text" class="form-control form-control-sm" name="rows[${index}][remarks]" value="${rowData.remarks || ''}"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">Remove</button></td>
            `;
            tr.querySelector('.remove-row').addEventListener('click', () => {
                tr.remove();
                reindexRows();
            });
            tbody.appendChild(tr);
        }

        function reindexRows() {
            Array.from(tbody.children).forEach((tr, index) => {
                tr.querySelectorAll('input,select').forEach((el) => {
                    el.name = el.name.replace(/rows\[\d+\]/, `rows[${index}]`);
                });
            });
        }

        document.getElementById('addPeerOutingRow').addEventListener('click', () => renderRow());

        if (existingRows.length) {
            existingRows.forEach((row) => renderRow(row));
        } else {
            renderRow();
        }
    </script>
@stop
