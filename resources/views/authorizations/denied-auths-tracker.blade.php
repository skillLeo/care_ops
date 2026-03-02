@extends('adminlte::page')

@section('title', 'Denied Auths Tracker')

@section('content_header')
    <h1>Denied Auths Tracker</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4>Denied Auths with Remarks</h4>
            <table id="deniedAuthsWithRemarksTable" class="table table-bordered table-hover mb-4">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>DOB</th>
                        <th>MRN</th>
                        <th>Authorization #</th>
                        <th>Auth LOC</th>
                        <th>Auth Type</th>
                        <th>Submission Date</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deniedWithRemarks as $item)
                        @php
                            $client = $item['client'];
                            $authorization = $item['authorization'];
                            $line = $item['line'];
                        @endphp
                        <tr>
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                            <td>{{ $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $client->mrn }}</td>
                            <td>{{ $authorization->auth_number ?: '-' }}</td>
                            <td>{{ $authorization->levelOfCare->display_name ?? '-' }}</td>
                            <td>{{ ucfirst($line->type ?? '-') }}</td>
                            <td>{{ $line->submission_date ? \Carbon\Carbon::parse($line->submission_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $line->starting_date ? \Carbon\Carbon::parse($line->starting_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $line->ending_date ? \Carbon\Carbon::parse($line->ending_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $line->remarks }}</td>
                            <td>
                                <a href="{{ route('authorizations.lines', $authorization->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h4>Denied Auths without Remarks</h4>
            <table id="deniedAuthsWithoutRemarksTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>DOB</th>
                        <th>MRN</th>
                        <th>Authorization #</th>
                        <th>Auth LOC</th>
                        <th>Auth Type</th>
                        <th>Submission Date</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deniedWithoutRemarks as $item)
                        @php
                            $client = $item['client'];
                            $authorization = $item['authorization'];
                            $line = $item['line'];
                        @endphp
                        <tr>
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                            <td>{{ $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $client->mrn }}</td>
                            <td>{{ $authorization->auth_number ?: '-' }}</td>
                            <td>{{ $authorization->levelOfCare->display_name ?? '-' }}</td>
                            <td>{{ ucfirst($line->type ?? '-') }}</td>
                            <td>{{ $line->submission_date ? \Carbon\Carbon::parse($line->submission_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $line->starting_date ? \Carbon\Carbon::parse($line->starting_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $line->ending_date ? \Carbon\Carbon::parse($line->ending_date)->format('m/d/Y') : '-' }}</td>
                            <td>
                                <a href="{{ route('authorizations.lines', $authorization->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
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
        .dt-buttons .btn {
            margin-right: 6px;
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

    function initDeniedTrackerTable(tableId, titlePrefix, orderColumn, exportColumns) {
        const reportTitle = `${titlePrefix} (${new Date().toLocaleString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        })})`;

        $(`#${tableId}`).DataTable({
            dom: 'Blfrtip',
            order: [[orderColumn, 'desc']],
            buttons: [
                {
                    text: 'Download PDF',
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'LETTER',
                    title: reportTitle,
                    exportOptions: {
                        columns: exportColumns
                    },
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 8;
                        doc.pageMargins = [10, 10, 10, 10];

                        const table = doc.content.find(c => c.table);
                        if (table) {
                            const columnCount = table.table.body[0].length;
                            table.table.widths = [
                                '28%',  // Client Name
                                '8%',   // DOB
                                '6%',   // MRN
                                '12%',  // Auth #
                                '8%',   // LOC
                                '8%',   // Type
                                '10%',  // Submission
                                '10%',  // Start
                                '10%'   // End
                            ];

                        }
                    }
                },
                {
                    text: 'Download Excel',
                    extend: 'excelHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: exportColumns
                    }
                },
                {
                    text: 'Download CSV',
                    extend: 'csvHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: exportColumns
                    }
                }
            ]
        });
    }

    $(document).ready(function () {
        initDeniedTrackerTable(
            'deniedAuthsWithRemarksTable',
            'Denied Auths Tracker - With Remarks',
            6,
            [0, 1, 2, 3, 4, 5, 6, 7, 8]
        );

        initDeniedTrackerTable(
            'deniedAuthsWithoutRemarksTable',
            'Denied Auths Tracker - Without Remarks',
            6,
            [0, 1, 2, 3, 4, 5, 6, 7, 8]
        );
    });
</script>
@stop
