@extends('adminlte::page')

@section('title', 'House Report')

@section('content_header')
    <h1>House Report</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.house') }}">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-8 text-right">
                <button type="submit" class="btn btn-primary mt-4">Filter</button>
            </div>
        </div>
    </form>

    <table id="house-report-table" class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>House Name</th>
                <th class="text-center">Capacity (M/F/C)</th>
                <th class="text-center">Occupied (M/F/C)</th>
                <th class="text-center">Vacancy (M/F/C)</th>
                <th class="text-center">PHP Clients</th>
                <th class="text-center">IOP Clients</th>
                <th class="text-center">Other Clients</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $row)
                <tr>
                    <td>{{ $row['house_name'] }}</td>
                    <td class="text-center">
                        {{ $row['total_capacity'] }} ({{ $row['capacity_male'] }}/{{ $row['capacity_female'] }}/{{ $row['capacity_couple'] }})
                    </td>
                    <td class="text-center">
                        {{ $row['total_occupied'] }} ({{ $row['occupied_male'] }}/{{ $row['occupied_female'] }}/{{ $row['occupied_couple'] }})
                    </td>
                    <td class="text-center">
                        {{ $row['vacant_total'] }} ({{ $row['vacant_male'] }}/{{ $row['vacant_female'] }}/{{ $row['vacant_couple'] }})
                    </td>
                    <td class="text-center">{{ $row['php_count'] }}</td>
                    <td class="text-center">{{ $row['iop_count'] }}</td>
                    <td class="text-center">{{ $row['non_level_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

@section('css')
    <!-- DataTables and Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .dt-buttons {
            margin-bottom: 15px;
        }
    </style>
@stop

@section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 
    <!-- DataTables and Extensions -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <!-- PDFMake for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        @include('partials.pdf-letterhead-js')

        $(document).ready(function () {
            const table = $('#house-report-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'pdfHtml5',
                        text: 'Export as PDF',
                        className: 'btn btn-secondary-brand',
                        title: 'House Report - {{ $selectedDate }}',
                        orientation: 'landscape',
                        pageSize: 'LETTER',
                        exportOptions: {
                            columns: ':visible'
                        },
                        customize: function (doc) {
                            applyPdfLetterhead(doc, 'landscape');
                            const columnWidths = ['20%', '15%', '15%', '15%', '10%', '10%', '15%'];
                            doc.content[1].table.widths = columnWidths;

                            doc.styles.tableHeader.alignment = 'center';
                            doc.styles.houseHeader = {
                                fontSize: 14,
                                bold: true,
                                alignment: 'center'
                            };

                            doc.content.forEach(section => {
                                if (section.table) {
                                    section.table.body.forEach((row, i) => {
                                        row.forEach(cell => {
                                            cell.margin = [0, 5, 0, 5]; // top, right, bottom, left
                                            cell.verticalAlignment = 'middle';
                                        });

                                        if (i === 0) {
                                            row.forEach(cell => {
                                                cell.alignment = 'center';
                                            });
                                        } else {
                                            if (row[0]) row[0].alignment = 'center';
                                            if (row[1]) row[1].alignment = 'center';
                                            if (row[2]) row[2].alignment = 'center';
                                            if (row[3]) row[3].alignment = 'center';
                                            if (row[4]) row[4].alignment = 'center';
                                            if (row[5]) row[5].alignment = 'center';
                                            if (row[6]) row[6].alignment = 'center';
                                        }
                                    });
                                }
                            });
                        }
                    }
                ],
                paging: false,
                ordering: false,
                searching: true,
                info: false
            });

            attachDataTableColumnSearch(table);
        });
    </script>
@stop
