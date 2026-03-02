@extends('adminlte::page')

@section('title', 'Edit CPRS Individual Submission')

@section('content_header')
    <h1>Edit CPRS Individual Submission</h1>
@stop

@section('content')
    @include('partials.flash')

    <form method="POST" action="{{ route('cprs.submissions.update', $submission) }}" enctype="multipart/form-data" id="cprsIndividualEditForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
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
                        <label for="service_date">Service Date</label>
                        <input type="date" id="service_date" name="service_date" class="form-control" required
                            value="{{ old('service_date', optional($submission->service_date)->toDateString()) }}">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="remarks">Remarks</label>
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
                        <strong>Client Rows</strong>
                        <button type="button" class="btn btn-sm btn-success" id="addIndividualRow">+ Add Client</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0" id="individualRowsTable">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th style="width: 160px;">Time Start</th>
                                    <th style="width: 160px;">Time End</th>
                                    <th style="width: 110px;">Units</th>
                                    <th>Individual Remarks</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody id="individualRowsBody"></tbody>
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
            'time_start' => $row->time_start ? substr((string) $row->time_start, 0, 5) : '',
            'time_end' => $row->time_end ? substr((string) $row->time_end, 0, 5) : '',
            'units' => $row->units,
            'remarks' => $row->remarks,
        ])->values()->all()));

        const tbody = document.getElementById('individualRowsBody');

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
                <td><input type="time" class="form-control form-control-sm" name="rows[${index}][time_start]" step="60" value="${rowData.time_start || ''}" required></td>
                <td><input type="time" class="form-control form-control-sm" name="rows[${index}][time_end]" step="60" value="${rowData.time_end || ''}" required></td>
                <td><input type="number" class="form-control form-control-sm" name="rows[${index}][units]" min="0" value="${rowData.units ?? 1}" required></td>
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

        document.getElementById('addIndividualRow').addEventListener('click', () => renderRow());

        if (existingRows.length) {
            existingRows.forEach((row) => renderRow(row));
        } else {
            renderRow();
        }
    </script>
@stop
