@extends('adminlte::page')

@section('title', 'Client List')

@section('content_header')
    <h1>Client List</h1>
@stop

@section('content')
    <table class="table table-bordered" id="clientTable">
        <thead>
            <tr>
                <th>Name</th>
                <th class="text-center">DOB</th>
                <th class="text-center">MRN</th>
                <th class="text-center">Carelon ID</th>
                <th class="text-center">Medicaid ID</th>
                <th class="text-center">Start Date</th>
                <th class="text-center">Discharge Date</th>
                <th class="text-center">Active</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
                    <td class="text-center">{{ $client->mrn }}</td>
                    <td class="text-center">
                        @can('client.view_carelon_id')
                            {{ $client->carelon_id }}
                        @else
                            Restricted
                        @endcan
                    </td>
                    <td class="text-center">
                        @can('client.view_medicaid_id')
                            {{ $client->medicaid_id }}
                        @else
                            Restricted
                        @endcan
                    </td>
                    <td class="text-center">{{ $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y') : '-' }}</td>
                    <td class="text-center">{{ $client->discharge_date ? \Carbon\Carbon::parse($client->discharge_date)->format('m/d/Y') : '-' }}</td>
                    <td class="text-center">{{ $client->status === 'active' ? 'Yes' : 'No' }}</td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script>
        @include('partials.pdf-letterhead-js')

        const reportDate = @json(\Carbon\Carbon::today()->format('m/d/Y'));
        const reportDate2 = @json(\Carbon\Carbon::today()->format('m-d-Y'));

        // Option: column alignment settings (same count as table columns)
        // Options: 'left', 'center', 'right'
        window.PDF_COL_ALIGN = ['left', 'center', 'center', 'center', 'center', 'center', 'center', 'center'];

        // Option: column widths in percentage
        window.PDF_COL_WIDTHS = [30, 10, 10, 10, 10, 10, 10, 10];

        function getPdfWidths(doc) {
            const colCount = doc.content[1].table.body[0].length;
            const percents = window.PDF_COL_WIDTHS || Array(colCount).fill(100 / colCount);

            const pageWidth = doc.pageSize.width || 792;
            const left = Array.isArray(doc.pageMargins) ? doc.pageMargins[0] : 12;
            const right = Array.isArray(doc.pageMargins) ? doc.pageMargins[2] : 12;
            const contentWidth = pageWidth - left - right;

            const sum = percents.reduce((a, b) => a + b, 0) || 100;
            const factor = contentWidth / sum;
            return percents.map(p => Math.max(5, p * factor));
        }

        function applyColumnAlignment(doc) {
            const aligns = window.PDF_COL_ALIGN || [];
            const body = doc.content[1].table.body;

            body.forEach((row, rowIndex) => {
                row.forEach((cell, colIndex) => {
                    cell.alignment = aligns[colIndex] || 'left';
                });
            });
        }
        const table = $('#clientTable').DataTable({
            dom: 'Blfrtip',
            buttons: [
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary-brand',
                    filename: 'Client_List ' + reportDate2,
                    title: 'Client List — ' + reportDate2,
                    pageSize: 'LETTER',
                    orientation: 'landscape',
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        doc.pageMargins = [12, 35, 12, 28];

                        // 8 absolute widths in points
                        doc.content[1].table.widths = [150, 85, 50, 85, 85, 70, 90, 50];

                        applyColumnAlignment(doc);

                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 12;

                        doc.header = function () {
                            return {
                                columns: [
                                    { text: 'Client List — ' + reportDate, alignment: 'center', fontSize: 10, bold: true }
                                ],
                                margin: [0, 10, 0, 0]
                            };
                        };

                        doc.footer = function (currentPage, pageCount) {
                            return {
                                columns: [
                                    { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'left', margin: [12, 0, 0, 0] }
                                ],
                                margin: [12, 0, 12, 10]
                            };
                        };
                    }

                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary-brand ', // ✅ Purple (rare)
                    filename: 'Client_List ' + reportDate2
                }
            ]
        });

        attachDataTableColumnSearch(table);
    </script>
@stop
