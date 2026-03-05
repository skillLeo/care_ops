@extends('adminlte::page')

@section('title', 'Discharge Report')

@section('content_header')
    <h1>Discharge Report</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.discharges') }}">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="from">From</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="to">To</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="peer_id">Peer</label>
                <select name="peer_id" id="peer_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($peers as $peer)
                        <option value="{{ $peer->id }}" {{ $peerId == $peer->id ? 'selected' : '' }}>{{ $peer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered" id="clientTable">
        <thead>
            <tr>
                <th>Name</th>
                <th class="text-center">DOB</th>
                <th class="text-center">MRN</th>
                <th class="text-center">Counselor</th>
                <th class="text-center">Peer</th>
                <th class="text-center">Current Level</th>
                <th class="text-center">Discharge Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
                    <td class="text-center">{{ $client->mrn }}</td>
                    <td class="text-center">{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name }}</td>
                    <td class="text-center">{{ optional($client->peer)->short_name ?? optional($client->peer)->name }}</td>
                    <td class="text-center">{{ $client->current_level_of_care }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($client->discharge_date)->format('m/d/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        @include('partials.pdf-letterhead-js')

        const table = $('#clientTable').DataTable({
            dom: 'Blfrtip',
            buttons: [{
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-secondary-brand',
                filename: 'Discharge_Report',
                pageSize: 'LETTER',
                orientation: 'landscape',
                customize: function (doc) {
                    applyPdfLetterhead(doc, 'landscape');
                    // Remove default DataTables title
                    doc.content.splice(0, 1);

                    // Make table borders and spacing uniform
                    var objLayout = {};
                    objLayout['hLineWidth'] = function() { return 0.5; };
                    objLayout['vLineWidth'] = function() { return 0.5; };
                    objLayout['hLineColor'] = function() { return '#aaa'; };
                    objLayout['vLineColor'] = function() { return '#aaa'; };
                    objLayout['paddingLeft'] = function() { return 4; };
                    objLayout['paddingRight'] = function() { return 4; };
                    doc.content[0].layout = objLayout;

                    // Force table to stretch equally across PDF width
                    var table = doc.content[0].table;
                    var columnCount = table.body[0].length;
                    table.widths = new Array(columnCount).fill('*');

                    // Minimize page margins for full width
                    doc.pageMargins = [10, 10, 10, 10];
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-secondary-brand',
                filename: 'Discharges'
            }]
        });

        attachDataTableColumnSearch(table);

    </script>
@stop
