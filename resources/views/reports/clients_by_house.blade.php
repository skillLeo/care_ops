@extends('adminlte::page')

@section('title', 'Client List by House')

@section('content_header')
    <h1>Client List by House</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.clientsByHouse') }}">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-4 mb-2">
                <label for="counselor_id">Counselor</label>
                <select name="counselor_id" id="counselor_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($counselors as $counselor)
                        <option value="{{ $counselor->id }}" {{ $counselorId == $counselor->id ? 'selected' : '' }}>
                            {{ $counselor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="peer_id">Peer</label>
                <select name="peer_id" id="peer_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($peers as $peer)
                        <option value="{{ $peer->id }}" {{ $peerId == $peer->id ? 'selected' : '' }}>{{ $peer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="house_id">House</label>
                <select name="house_id" id="house_id" class="form-control">
                    <option value="all">All</option>
                    <option value="unhoused" {{ $houseId == 'unhoused' ? 'selected' : '' }}>Unhoused</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->id }}" {{ $houseId == $house->id ? 'selected' : '' }}>
                            {{ $house->house_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="apartment_id">Apartment</label>
                <select name="apartment_id" id="apartment_id" class="form-control"
                    {{ $houseId === 'all' || $houseId === 'unhoused' ? 'disabled' : '' }}>
                    <option value="all">All</option>
                    @foreach ($apartments as $apt)
                        <option value="{{ $apt->id }}" {{ $apartmentId == $apt->id ? 'selected' : '' }}>
                            {{ $apt->apartment_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label for="level_of_care">Client Level of Care</label>
                <select name="level_of_care" id="level_of_care" class="form-control">
                    <option value="all">All</option>
                    @foreach ($levels as $loc)
                        <option value="{{ $loc->level_of_care }}" {{ $levelOfCareFilter == $loc->level_of_care ? 'selected' : '' }}>
                            {{ $loc->display_name }}</option>
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
                <th class="text-center">House</th>
                <th class="text-center">Apt</th>
                <th>Name</th>
                <th class="text-center">MRN</th>
                <th class="text-center">LOC</th>
                <th class="text-center">LOC History</th>
                <th class="text-center">Counselor</th>
                <th class="text-center">Peer</th>
                <th class="text-center">Starting Date</th>
                <th class="text-center">Phone</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td class="text-center">{{ optional(optional($client->apartment)->house)->house_name ?? 'Unhoused' }}
                    </td>
                    <td class="text-center">{{ $client->apartment->apartment_number ?? '' }}</td>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ $client->mrn }}</td>
                    <td class="text-center">{{ $client->current_level_of_care }}</td>
                    <td class="text-center">{!! $client->level_of_care_history !!}</td>
                    <td class="text-center">
                        {{ optional($client->counselor)->short_name ?? optional($client->counselor)->name }}</td>
                    <td class="text-center">{{ optional($client->peer)->short_name ?? optional($client->peer)->name }}</td>
                    <td class="text-center">
                        {{ $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y') : '' }}
                    </td>
                    <td class="text-center">{{ $client->phone }}</td>
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

        const columnWidths = ['4%', '20%', '4%', '4%', '27%', '10%', '10%', '10%', '11%']; // adjust widths as needed
        const table = $('#clientTable').DataTable({
            dom: 'Blfrtip',
            order: [
                [0, 'asc'],
                [1, 'asc']
            ],
            buttons: [{
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    filename: 'Clients_By_House',
                    pageSize: 'LETTER',
                    exportOptions: {
                        stripHtml: false,
                        format: {
                            body: function(data, row, column, node) {
                                return data.replace(/<br\s*\/?>/gi, '\n');
                            }
                        }
                    },
                    customize: function(doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        const body = doc.content[1].table.body;
                        // put this near the top of customize, before doc.content assignment
                        doc.pageMargins = [40, 60, 40, 84]; // left, top, right, bottom (bigger bottom)

                        const header = body[0].map(cell => typeof cell === 'object' ? cell : {
                            text: cell
                        });
                        const dataRows = body.slice(1);
                        const groups = {};
                        dataRows.forEach(row => {
                            const house = row[0].text || row[0];
                            if (!groups[house]) groups[house] = [];
                            groups[house].push(row.slice(1));
                        });

                        const content = [];
                        let first = true;

                        // Sort houses so "Unhoused" comes last
                        const sortedHouses = Object.keys(groups).sort((a, b) => {
                            if ((a || 'Unhoused') === 'Unhoused') return 1;
                            if ((b || 'Unhoused') === 'Unhoused') return -1;
                            return a.localeCompare(b);
                        });

                        sortedHouses.forEach(house => {
                            const houseName = house || 'Unhoused';

                            // House header row
                            const houseHeaderRow = [{
                                    text: houseName,
                                    style: 'houseHeader',
                                    alignment: 'center',
                                    colSpan: columnWidths.length,
                                    margin: [0, 0, 0, 6],
                                    fillColor: '#e0e0e0',
                                    bold: true
                                },
                                ...Array(columnWidths.length - 1).fill({})
                            ];

                            // Column header row
                            const tableHeaderRow = header.slice(1).map(c =>
                                typeof c === 'object' ? {
                                    ...c,
                                    style: 'tableHeader',
                                    alignment: 'center',
                                    fillColor: '#e0e0e0',
                                    bold: true
                                } : {
                                    text: c,
                                    style: 'tableHeader',
                                    alignment: 'center',
                                    fillColor: '#e0e0e0',
                                    bold: true
                                }
                            );

                            content.push({
                                pageBreak: first ? undefined : 'before',
                                table: {
                                    headerRows: 2, // both rows repeat
                                    widths: columnWidths,
                                    body: [houseHeaderRow, tableHeaderRow, ...groups[house]]
                                },
                                layout: {
                                    hLineWidth: () => 0.5,
                                    vLineWidth: () => 0.5,
                                    hLineColor: () => '#aaa',
                                    vLineColor: () => '#aaa'
                                }
                            });

                            first = false;
                        });

                        doc.content = content;
                        doc.styles.tableHeader = {
                            bold: true,
                            alignment: 'center'
                        };
                        doc.styles.houseHeader = {
                            fontSize: 14,
                            bold: true
                        };

                        // Align data rows
                        doc.content.forEach(item => {
                            if (!item.table) return;
                            item.table.body.forEach((row, i) => {
                                if (i < 2) return; // skip header rows
                                row.forEach((cell, idx) => {
                                    if (typeof cell === 'string') row[idx] = {
                                        text: cell
                                    };
                                    row[idx].margin = [0, 5, 0, 5];
                                    row[idx].verticalAlignment = 'middle';
                                });
                                row[0].alignment = 'center'; // Apt
                                row[2].alignment = 'center'; // MRN
                                row[3].alignment = 'center'; // LOC
                                row[5].alignment = 'center'; // Counselor
                                row[6].alignment = 'center'; // Peer
                                row[7].alignment = 'center'; // Starting Date
                                row[8].alignment = 'center'; // Phone
                            });
                        });



                        doc.footer = function(currentPage, pageCount) {
                            return {
                                columns: [{
                                        text: '{{ \Carbon\Carbon::parse($selectedDate)->format('m/d/Y') }}',
                                        alignment: 'left',
                                        margin: [40, 20, 0, 0]
                                    },
                                    {
                                        text: 'Page ' + currentPage + ' of ' + pageCount,
                                        alignment: 'right',
                                        margin: [0, 20, 40, 0]
                                    }
                                ]
                            };
                        };

                    }
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    filename: 'Clients by House ' + @json(\Carbon\Carbon::parse($selectedDate)->format('m-d-Y'))
                }

            ]
        });

        attachDataTableColumnSearch(table);
    </script>
@stop
