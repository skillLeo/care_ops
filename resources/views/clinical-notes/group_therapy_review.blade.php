@extends('adminlte::page')

@section('title', 'Productivity - Clinician - Clinical Group')

@section('content_header')
    <h1>Productivity - Clinician - Clinical Group</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Review Note Completed Clients</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_service_date">Service Date</label>
                    <input type="text" id="review_service_date" class="form-control" readonly
                        value="{{ \Carbon\Carbon::parse($meta['service_date'])->format('m/d/Y') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_time_start">Time Start</label>
                    <input type="text" id="review_time_start" class="form-control" readonly
                        value="{{ \Carbon\Carbon::createFromFormat('H:i', $meta['time_start'])->format('h:i A') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_time_end">Time End</label>
                    <input type="text" id="review_time_end" class="form-control" readonly
                        value="{{ \Carbon\Carbon::createFromFormat('H:i', $meta['time_end'])->format('h:i A') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_session_type">Session Type</label>
                    <input type="text" id="review_session_type" class="form-control" readonly
                        value="{{ $serviceCode?->friendly_name }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_level_of_care">Level of Care</label>
                    <input type="text" id="review_level_of_care" class="form-control" readonly
                        value="{{ $levelOfCareName }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="review_group">Group</label>
                    <input type="text" id="review_group" class="form-control" readonly value="{{ $groupName }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="review_remarks">Group Note Title</label>
                    <input type="text" id="review_remarks" class="form-control" readonly value="{{ $meta['remarks'] }}">
                </div>
            </div>

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">#</th>
                        <th>Client Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($completeClients as $client)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('clinical-notes.group_therapy_store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="service_date" value="{{ $meta['service_date'] }}">
        <input type="hidden" name="level_of_care" value="{{ $meta['level_of_care'] }}">
        <input type="hidden" name="group_id" value="{{ $meta['group_id'] }}">
        <input type="hidden" name="service_code_id" value="{{ $meta['service_code_id'] }}">
        <input type="hidden" name="time_start" value="{{ $meta['time_start'] }}">
        <input type="hidden" name="time_end" value="{{ $meta['time_end'] }}">
        <input type="hidden" name="remarks" value="{{ $meta['remarks'] }}">

        @foreach ($attendanceRows as $clientId => $data)
            <input type="hidden" name="attendance[{{ $clientId }}][complete]" value="{{ ! empty($data['complete']) ? 1 : 0 }}">
        @endforeach

        @if (! empty($attachments))
            @foreach ($attachments as $file)
                <input type="hidden" name="stored_attachments[]" value="{{ $file }}">
            @endforeach
        @endif

        @if (! empty($attachments))
            <div class="mb-3">
                <label>Attachments</label>
                <div class="d-flex flex-column gap-2">
                    @foreach ($attachments as $file)
                        <div class="text-muted small">{{ $file }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <button type="button" class="btn btn-secondary" id="backToDraft">Back</button>
        <button type="submit" class="btn btn-success">Confirm & Submit</button>
    </form>
@stop

@section('js')
    <script>
        const clinicalNotesDraft = @json($meta);
        const clinicalNotesRows = @json($attendanceRows);
        const storedAttachments = @json($attachments ?? []);
        document.getElementById('backToDraft').addEventListener('click', () => {
            const draft = {
                remarks: clinicalNotesDraft.remarks ?? '',
                attendanceRows: Object.entries(clinicalNotesRows).map(([clientId, data]) => ({
                    clientId: Number(clientId),
                    complete: Boolean(data?.complete),
                })),
                storedAttachments: storedAttachments ?? [],
            };
            sessionStorage.setItem('clinicalNotesDraft', JSON.stringify(draft));
            const url = new URL(`{{ route('clinical-notes.group_therapy') }}`, window.location.origin);
            url.searchParams.set('date', clinicalNotesDraft.service_date);
            url.searchParams.set('level_of_care', clinicalNotesDraft.level_of_care);
            url.searchParams.set('group_id', clinicalNotesDraft.group_id);
            url.searchParams.set('service_code_id', clinicalNotesDraft.service_code_id);
            url.searchParams.set('time_start', clinicalNotesDraft.time_start);
            url.searchParams.set('time_end', clinicalNotesDraft.time_end);
            window.location.href = url.toString();
        });
    </script>
@stop
