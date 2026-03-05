@extends('adminlte::page')

@section('title', 'Attendance Report')

@section('content_header')
    <h1>Attendance Report1</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.attendance') }}">
        <div class="row mb-1">
            <div class="col-md-4 mb-2">
                <label for="date">Service Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="session_type">Session Type</label>
                <select name="session_type" id="session_type" class="form-control">
                    @foreach (['group', 'peer_individual', 'peer_group'] as $type)
                        <option value="{{ $type }}" {{ $sessionType === $type ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="counselor_id">Counselor</label>
                <select name="counselor_id" id="counselor_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($counselors as $counselor)
                        <option value="{{ $counselor->id }}" {{ $counselorId == $counselor->id ? 'selected' : '' }}>
                            {{ $counselor->name }}
                        </option>
                    @endforeach
                </select>
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
            <div class="col-md-4 mb-2">
                <label for="house_id">House</label>
                <select name="house_id" id="house_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->id }}" {{ $houseId == $house->id ? 'selected' : '' }}>{{ $house->house_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="apartment_id">Apartment</label>
                <select name="apartment_id" id="apartment_id" class="form-control" {{ $houseId === 'all' ? 'disabled' : '' }}>
                    <option value="all">All</option>
                    @foreach ($apartments as $apt)
                        <option value="{{ $apt->id }}" {{ $apartmentId == $apt->id ? 'selected' : '' }}>{{ $apt->apartment_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="level_of_care">Client Level of Care</label>
                <select name="level_of_care" id="level_of_care" class="form-control">
                    <option value="all">All</option>
                    @foreach ($levels as $loc)
                        <option value="{{ $loc->level_of_care }}" {{ $levelOfCareFilter == $loc->level_of_care ? 'selected' : '' }}>{{ $loc->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="present_only" id="present_only" class="form-check-input" value="1" {{ $presentOnly ? 'checked' : '' }}>
                    <label for="present_only" class="form-check-label">Present Only</label>
                </div>
            </div>
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered " id="clientTable">
        <thead>
            <tr>
                <th>Client Name</th>
                <th class="text-center">Level of Care</th>
                <th class="text-center">Counselor</th>
                <th class="text-center">Peer</th>
                <th class="text-center">House: Apartment</th>
                <th class="text-center">Present</th>
                <th class="text-center">Units</th>
                <th class="text-center">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                @php $att = $attendanceData[$client->id] ?? null; @endphp
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ $client->level_of_care }}</td>
                    <td class="text-center">{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name }}</td>
                    <td class="text-center">{{ optional($client->peer)->short_name ?? optional($client->peer)->name }}</td>
                    <td class="text-center">
                        {{ optional(optional($client->apartment)->house)->house_name }}{{ $client->apartment ? ': ' . $client->apartment->apartment_number : '' }}
                    </td>
                    <td class="text-center">{{ $att ? 'Yes' : 'No' }}</td>
                    <td class="text-center">{{ $att->units ?? '' }}</td>
                    <td class="text-center">{{ $att->remarks ?? '' }}</td>
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

        const reportDate = @json(\Carbon\Carbon::parse($selectedDate)->format('m/d/Y'));
        const reportDate2 = @json(\Carbon\Carbon::parse($selectedDate)->format('m-d-Y'));

        // Option: column alignment settings (same count as table columns)
        // Options: 'left', 'center', 'right'
        window.PDF_COL_ALIGN = ['left', 'center', 'center', 'center', 'left', 'center', 'center', 'left'];

        // Option: column widths in percentage
        window.PDF_COL_WIDTHS = [18, 10, 10, 10, 20, 8, 8, 16];

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
                    filename: 'Attendance_Report ' + reportDate2,
                    className: 'btn btn-secondary-brand ',
                    title: 'Attendance Report — ' + reportDate,
                    pageSize: 'LETTER',
                    orientation: 'landscape',
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        // margins
                        doc.pageMargins = [12, 35, 12, 28];

                        // adjust widths
                        doc.content[1].table.widths = getPdfWidths(doc);

                        // adjust alignment
                        applyColumnAlignment(doc);

                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 12;

                        // header (repeated on every page)
                        doc.header = function () {
                            return {
                                columns: [
                                    { text: 'Attendance Report — ' + reportDate, alignment: 'center', fontSize: 10, bold: true }
                                ],
                                margin: [0, 10, 0, 0]
                            };
                        };

                        // footer: Page x of y
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
                    className: 'btn btn-secondary-brand',
                    filename: 'Attendance_Report ' + reportDate2
                }
            ]
        });

        attachDataTableColumnSearch(table);
    </script>

@stop
