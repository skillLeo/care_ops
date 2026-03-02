@extends('adminlte::page')

@section('title', 'Client List by Peer')

@section('content_header')
    <h1>Client List by Peer</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.clientsByPeer') }}">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
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
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered" id="clientTable">
        <thead>
            <tr>
                <th class="text-center">Peer</th>
                <th>Name</th>
                <th class="text-center">MRN</th>
                <th class="text-center">Phone</th>
                <th class="text-center">LOC History</th>
                <th class="text-center">LOC</th>
                <th class="text-center">Counselor</th>
                <th class="text-center">House: Apartment</th>
                <th class="text-center">Starting Date</th>
                <th class="text-center">Discharge Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td class="text-center">{{ optional($client->peer)->short_name ?? optional($client->peer)->name }}</td>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td class="text-center">{{ $client->mrn }}</td>
                    <td class="text-center">{{ $client->phone }}</td>
                    <td class="text-center">{!! $client->level_of_care_history !!}</td>
                    <td class="text-center">{{ $client->current_level_of_care }}</td>
                    <td class="text-center">{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name }}</td>
                    <td class="text-center">
                        {{ optional(optional($client->apartment)->house)->house_name }}{{ $client->apartment ? ': ' . $client->apartment->apartment_number : '' }}
                    </td>
                    <td class="text-center">{{ $client->starting_date }}</td>
                    <td class="text-center">{{ $client->discharge_date }}</td>
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

        const columnWidths = ['18%', '4%', '10%', '24%', '4%', '10%', '10%', '10%', '10%'];
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
                filename: 'Clients_By_Peer',
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
                    const header = body[0].map(cell => typeof cell === 'object' ? cell : { text: cell });
                    const dataRows = body.slice(1);
                    const groups = {};
                    dataRows.forEach(row => {
                        const peer = row[0].text || row[0];
                        if (!groups[peer]) groups[peer] = [];
                        groups[peer].push(row.slice(1));
                    });

                    const content = [];
                    let first = true;
                    Object.keys(groups).sort().forEach(peer => {
                        const peerHeaderRow = [{
                                text: peer,
                                style: 'counselorHeader',
                                alignment: 'center',
                                colSpan: columnWidths.length,
                                margin: [0, 0, 0, 6],
                                fillColor: '#e0e0e0',
                                bold: true
                            },
                            ...Array(columnWidths.length - 1).fill({})
                        ];

                        const tableHeaderRow = header.slice(1).map(c =>
                            typeof c === 'object' ? { ...c, style: 'tableHeader', alignment: 'center', bold: true } : { text: c, style: 'tableHeader', alignment: 'center', fillColor: '#e0e0e0', bold: true }
                        );

                        const tableBody = groups[peer].map(r => r.map(c => typeof c === 'object' ? c : { text: c, alignment: 'center' }));

                        content.push({
                            pageBreak: first ? undefined : 'before',
                            table: {
                                widths: columnWidths,
                                body: [peerHeaderRow, tableHeaderRow, ...tableBody]
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
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                filename: 'Clients by Peer ' + @json(\Carbon\Carbon::parse($selectedDate)->format('m-d-Y'))
            }]
        });

        attachDataTableColumnSearch(table);
    </script>
@stop
