@extends('adminlte::page')

@section('title', 'Medicaid List by Date')

@section('content_header')
    <h1>Medicaid List by Date</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.medicaidByDate') }}" class="mb-3">
        <div class="row align-items-end">
            <div class="col-md-4 mb-2">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered" id="clients-by-date">
        <thead>
            <tr>
                <th>Name</th>
                <th class="text-center">DOB</th>
                <th class="text-center">Medicaid ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
                    <td class="text-center">
                        @can('client.view_medicaid_id')
                            {{ $client->medicaid_id ?? 'Not Provided' }}
                        @else
                            Restricted
                        @endcan
                    </td>
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
    <script src="{{ asset('js/datatables-column-search.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        @include('partials.pdf-letterhead-js')

        const reportDate = new Date(document.getElementById('date').value || '').toLocaleDateString('en-US');

        const table = $('#clients-by-date').DataTable({
            dom: 'Blfrtip',
            buttons: [
                {
                    extend: 'pdfHtml5',
                    orientation: 'portrait',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    filename: 'Medicaid_List_By_Date',
                    pageSize: 'LETTER',
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'portrait');
                        doc.content.splice(0, 1);

                        var objLayout = {};
                        objLayout['hLineWidth'] = function() { return 0.5; };
                        objLayout['vLineWidth'] = function() { return 0.5; };
                        objLayout['hLineColor'] = function() { return '#aaa'; };
                        objLayout['vLineColor'] = function() { return '#aaa'; };
                        objLayout['paddingLeft'] = function() { return 4; };
                        objLayout['paddingRight'] = function() { return 4; };
                        doc.content[0].layout = objLayout;

                        var table = doc.content[0].table;
                        var columnCount = table.body[0].length;
                        table.widths = new Array(columnCount).fill('*');

                        doc.pageMargins = [10, 10, 10, 10];

                        doc.content.unshift({
                            text: 'Medicaid List by Date — ' + reportDate,
                            alignment: 'center',
                            fontSize: 12,
                            bold: true,
                            margin: [0, 0, 0, 10]
                        });
                    }
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    filename: 'Medicaid_List_By_Date'
                }
            ]
        });

        attachDataTableColumnSearch(table);
    </script>
@stop
