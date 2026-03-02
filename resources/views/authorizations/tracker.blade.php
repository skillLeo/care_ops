@extends('adminlte::page')

@section('title', 'Authorizations Tracker')

@section('content_header')
    <h1>Authorization Tracker</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @foreach ($levels as $level)
                <h2>LOC: {{ $level->display_name }}</h2>
                <table id="trackerTable-{{ $level->id }}" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>DOB</th>
                            <th>MRN</th>
                            <th>Date of Initial Contact</th>
                            <th>Eligibility<br>Redetermination Date</th>
                            <th>Carelon ID</th>
                            <th>Medicaid ID</th>
                            <th>LOC</th>
                            {{-- <th>Auth LOC</th> --}}
                            <th>Current Auth Type</th>
                            <th>Current Auth Number</th>
                            <th>Diagnosis Code</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trackerDataByLevel[$level->id] ?? [] as $data)
                            @php
                                $client = $data['client'];
                                $dob = $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth) : null;
                                $startingDate = $client->starting_date ? \Carbon\Carbon::parse($client->starting_date) : null;
                                $redeterminationDate = $data['redetermination_date'] ? \Carbon\Carbon::parse($data['redetermination_date']) : null;
                                $lineStart = $data['latest_line'] && $data['latest_line']->starting_date
                                    ? \Carbon\Carbon::parse($data['latest_line']->starting_date)
                                    : null;
                                $lineEnd = $data['latest_line'] && $data['latest_line']->ending_date
                                    ? \Carbon\Carbon::parse($data['latest_line']->ending_date)
                                    : null;
                                $dueDate = ! empty($data['due']) && $data['due'] !== 'Pause'
                                    ? \Carbon\Carbon::createFromFormat('m/d/Y', $data['due'])
                                    : null;
                            @endphp
                            <tr>
                                <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                                <td data-order="{{ $dob?->format('Y-m-d') }}">{{ $dob?->format('m/d/Y') }}</td>
                                <td>{{ $client->mrn }}</td>
                                <td data-order="{{ $startingDate?->format('Y-m-d') }}">{{ $startingDate?->format('m/d/Y') }}</td>
                                <td class='redetermination-date' data-order="{{ $redeterminationDate?->format('Y-m-d') }}">
                                    {{ $redeterminationDate?->format('m/d/Y') ?? '' }}
                                </td>
                                <td>
                                    @can('client.view_carelon_id')
                                        {{ $client->carelon_id }}
                                    @else
                                        Restricted
                                    @endcan
                                </td>
                                <td>
                                    @can('client.view_medicaid_id')
                                        {{ $client->medicaid_id }}
                                    @else
                                        Restricted
                                    @endcan
                                </td>
                                <td>{{ $level->display_name }}</td>
                                <td>
                                    <span>
                                        {{ ucfirst($data['latest_line']->type ?? 'Not Yet Applied') }}
                                    </span>
                                </td>
                                <td>{{ $data['latest_auth']->auth_number ?? '-' }}</td>
                                <td>{{ $data['latest_line']->diagnosis_code ?? '-' }}</td>
                                <td data-order="{{ $lineStart?->format('Y-m-d') }}">
                                    {{ $lineStart?->format('m/d/Y') ?? '-' }}
                                </td>

                                <td data-order="{{ $lineEnd?->format('Y-m-d') }}">
                                    {{ $lineEnd?->format('m/d/Y') ?? '-' }}
                                </td>
                                <td class='due-date' data-order="{{ $dueDate?->format('Y-m-d') }}">{{ $data['due'] ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('authorizations.lines', $data['latest_auth']->id) }}"
                                        class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if (!empty($data['latest_line']->attachments))
                                        @foreach (json_decode($data['latest_line']->attachments, true) as $file)
                                            <a href="{{ route('attachments.download', $file) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endforeach
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

            <h2>Other Active Clients</h2>
            <table id="trackerTableOther" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>DOB</th>
                        <th>MRN</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($otherClients as $client)
                        <tr>
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                            <td>{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
                            <td>{{ $client->mrn }}</td>
                            <td>{{ ucfirst($client->status) }}</td>
                            <td>
                                <a href="{{ route('clients.show', $client->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
@stop

@section('css')

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .due-date-red {
            color: red !important;
        }

        .due-date-yellow {
            color: yellow !important;
        }

        .due-date-gray {
            color: gray !important;
        }

        .buttons-toggle-overdue.active-filter {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .dt-buttons .btn {
            margin-right: 6px; /* or whatever spacing you like */
        }
    </style>
@stop

@section('js')
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    @include('partials.pdf-letterhead-js')

    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);

    function isDueOrOverdue(text) {
        if (text === 'Pause') return false;
        const due = new Date(text);
        if (isNaN(due)) return false;

        return (
            due < today.setHours(0, 0, 0, 0) ||
            due.toDateString() === new Date().toDateString()
        );
    }

    function initTableWithFilter(tableId, titlePrefix) {
        let isOverdueFilterActive = false;

        const reportTitle = `${titlePrefix} Auth Tracker (${new Date().toLocaleString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        })})`;

        // Add custom filter to DataTable
        $.fn.dataTable.ext.search.push(function (settings, data) {
            if (settings.nTable.id !== tableId) return true;
            if (!isOverdueFilterActive) return true;

            const dueText = data[13].trim();
            return isDueOrOverdue(dueText);
        });

        const dt = $(`#${tableId}`).DataTable({
            dom: 'Blfrtip',
            order: [[13, 'asc']],
            buttons: [
                {
                    text: 'Download PDF',
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'LETTER',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
                    },
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        doc.defaultStyle.fontSize = 8;
                        doc.styles.tableHeader.fontSize = 8; // smaller header font
                        const dueDateIndex = 13;
                        const redetIndex = 4;
                        doc.pageMargins = [10, 10, 10, 10];
                        doc.content[1].layout = {
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5,
                            hLineColor: () => '#aaa',
                            vLineColor: () => '#aaa'
                        };

                        const body = doc.content[1].table.body;
                        for (let i = 1; i < body.length; i++) {
                            const dueText = body[i][dueDateIndex].text;
                            const redetText = body[i][redetIndex].text;

                            if (dueText === 'Pause') {
                                body[i][dueDateIndex].color = 'gray';
                            } else {
                                const due = new Date(dueText);
                                if (!isNaN(due)) {
                                    if (due < today.setHours(0, 0, 0, 0)) {
                                        body[i][dueDateIndex].color = 'red';
                                    } else if (due.toDateString() === today.toDateString()) {
                                        body[i][dueDateIndex].color = 'orange';
                                    }
                                }
                            }

                            const redet = new Date(redetText);
                            if (!isNaN(redet)) {
                                if (redet < today.setHours(0, 0, 0, 0)) {
                                    body[i][redetIndex].color = 'red';
                                } else if (redet.toDateString() === today.toDateString()) {
                                    body[i][redetIndex].color = 'orange';
                                }
                            }
                        }
                    }
                },
                {
                    text: 'Download Excel',
                    extend: 'excelHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
                    }
                },
                {
                    text: 'Download CSV',
                    extend: 'csvHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
                    }
                },
                {
                    text: 'Filter Overdue',
                    className: 'btn btn-primary',
                    action: function (e, dt, node) {
                        isOverdueFilterActive = !isOverdueFilterActive;
                        dt.draw();
                        node.text(isOverdueFilterActive ? 'Remove Filter' : 'Filter Overdue');
                    }
                }
            ]
        });
    }

    $(document).ready(function () {
        const trackerTables = @json($levels->map(fn ($level) => [
            'id' => "trackerTable-{$level->id}",
            'label' => $level->display_name,
        ]));

        trackerTables.forEach((table) => {
            initTableWithFilter(table.id, table.label);
        });

        $('#trackerTableOther').DataTable({
            dom: 'lfrtip'
        });

        function applyDateColors() {
            document.querySelectorAll('.due-date, .redetermination-date').forEach(cell => {
                cell.classList.remove('due-date-red', 'due-date-yellow', 'due-date-gray');

                const text = cell.textContent.trim();

                if (cell.classList.contains('due-date') && text === 'Pause') {
                    cell.classList.add('due-date-gray');
                    return;
                }

                const parts = text.split('/');
                if (parts.length === 3) {
                    const [month, day, year] = parts;
                    const date = new Date(`${year}-${month}-${day}`);

                    if (isNaN(date)) return;

                    if (date < today.setHours(0, 0, 0, 0)) {
                        cell.classList.add('due-date-red');
                    } else if (date.toDateString() === new Date().toDateString()) {
                        cell.classList.add('due-date-yellow');
                    }
                }
            });
        }

        applyDateColors();
        const trackerSelectors = trackerTables.map(table => `#${table.id}`).join(', ');
        $(`${trackerSelectors}, #trackerTableOther`).on('draw.dt', applyDateColors);
    });
</script>
@stop
