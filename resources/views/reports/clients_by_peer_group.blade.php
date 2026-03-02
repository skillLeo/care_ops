@extends('adminlte::page')

@section('title', 'Client List by Peer Group')

@section('content_header')
    <h1>Client List by Peer Group</h1>
@stop

@section('content')
    <form method="GET" action="{{ route('reports.clientsByPeerGroup') }}" class="mb-4">
        <div class="row align-items-end">
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" value="{{ $selectedDate }}" class="form-control">
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                <label for="peer_group_id">Peer Group</label>
                <select name="peer_group_id" id="peer_group_id" class="form-control">
                    <option value="all" {{ $peerGroupId === 'all' ? 'selected' : '' }}>All</option>
                    <option value="ungrouped" {{ $peerGroupId === 'ungrouped' ? 'selected' : '' }}>Ungrouped</option>
                    @foreach ($peerGroups as $group)
                        <option value="{{ $group->id }}" {{ (string) $peerGroupId === (string) $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                <label for="counselor_id">Counselor</label>
                <select name="counselor_id" id="counselor_id" class="form-control">
                    <option value="all" {{ $counselorId === 'all' ? 'selected' : '' }}>All</option>
                    @foreach ($counselors as $counselor)
                        <option value="{{ $counselor->id }}" {{ (string) $counselorId === (string) $counselor->id ? 'selected' : '' }}>
                            {{ $counselor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                <label for="peer_id">Peer</label>
                <select name="peer_id" id="peer_id" class="form-control">
                    <option value="all" {{ $peerId === 'all' ? 'selected' : '' }}>All</option>
                    @foreach ($peers as $peer)
                        <option value="{{ $peer->id }}" {{ (string) $peerId === (string) $peer->id ? 'selected' : '' }}>
                            {{ $peer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                <label for="level_of_care">Level of Care</label>
                <select name="level_of_care" id="level_of_care" class="form-control">
                    <option value="all" {{ $levelOfCareFilter === 'all' ? 'selected' : '' }}>All</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->level_of_care }}" {{ $levelOfCareFilter === $level->level_of_care ? 'selected' : '' }}>{{ $level->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 mb-3 text-right">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered" id="clientsByPeerGroupTable">
            <thead>
                <tr>
                    <th>Peer Group</th>
                    <th>Client</th>
                    <th class="no-search">Signature</th>
                    <th class="no-search">Phone</th>
                    <th class="no-search">Email</th>
                    <th>Counselor</th>
                    <th>Peer</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td>{{ optional($client->peerGroup)->name ?? 'Ungrouped' }}</td>
                        <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        <td></td>
                        <td>{{ $client->phone ?? '' }}</td>
                        <td>{{ $client->email ?? '' }}</td>
                        <td>{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name ?? '' }}</td>
                        <td>{{ optional($client->peer)->short_name ?? optional($client->peer)->name ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
    <script>
        @include('partials.pdf-letterhead-js')

        $(document).ready(function() {
            const columnWidths = ['25%', '20%', '10%', '25%', '10%', '10%'];
            const table = $('#clientsByPeerGroupTable').DataTable({
                dom: 'Blfrtip',
                order: [[0, 'asc'], [1, 'asc']],
                buttons: [{
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    orientation: 'landscape',
                    pageSize: 'LETTER',
                    title: 'Clients by Peer Group',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function(doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        const today = new Date();
                        const formattedDate =
                            String(today.getMonth() + 1).padStart(2, '0') + '/' +
                            String(today.getDate()).padStart(2, '0') + '/' +
                            today.getFullYear();

                        doc.header = function (currentPage, pageCount) {
                            return {
                                margin: [20, 15, 20, 0],
                                columns: [
                                    { text: formattedDate, alignment: 'left', fontSize: 9 },
                                    { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'right', fontSize: 9 }
                                ]
                            };
                        };
                        doc.pageMargins = [0, 30, 0, 30];
                        const body = doc.content[1].table.body;
                        const headerRow = body[0];
                        const dataRows = body.slice(1);
                        const groups = {};

                        dataRows.forEach(row => {
                            const groupNameRaw = row[0]?.text || row[0] || '';
                            const groupName = (groupNameRaw || '').trim() || 'Ungrouped';
                            if (!groups[groupName]) {
                                groups[groupName] = [];
                            }
                            const rowWithoutGroup = row.slice(1).map((cell, colIndex) => {
                                const text = typeof cell === 'object' ? (cell.text || '') : cell;

                                return {
                                    text: (text || '').replace(/<br\s*\/?>/gi, '\n'),
                                    alignment: colIndex === 0 ? 'left' : 'center',
                                    margin: colIndex === 0 ? [20, 4, 20, 4] : [0, 4, 0, 4]
                                };
                            });
                            groups[groupName].push(rowWithoutGroup);
                        });

                        const headerCells = headerRow.slice(1).map(cell => typeof cell === 'object' ? (cell.text || '') : cell);
                        const pdfContent = [];
                        const sortedGroupNames = Object.keys(groups).sort((a, b) => {
                            if (a === 'Ungrouped') return 1;
                            if (b === 'Ungrouped') return -1;
                            return a.localeCompare(b);
                        });

                        sortedGroupNames.forEach((groupName, index) => {
                            if (index !== 0) {
                                pdfContent.push({ text: '', pageBreak: 'before' });
                            }
                            const tableBody = [[{
                                    text: groupName,
                                    colSpan: headerCells.length,
                                    style: 'groupHeader',
                                    alignment: 'center',
                                    margin: [0, 6, 0, 6]
                                }, ...Array(headerCells.length - 1).fill({})],
                                headerCells.map(text => ({
                                    text,
                                    style: 'tableHeader',
                                    alignment: 'center'
                                })),
                                ...groups[groupName]
                            ];

                            pdfContent.push({
                                table: {
                                    headerRows: 2,
                                    widths: columnWidths,
                                    body: tableBody
                                },
                                layout: 'lightHorizontalLines'
                            });
                        });

                        doc.content = pdfContent;
                        doc.styles.groupHeader = {
                            fontSize: 16,
                            bold: true,
                            alignment: 'center'
                        };
                        doc.styles.tableHeader = {
                            bold: true,
                            fillColor: '#e0e0e0'
                        };
                    }
                }]
            });

            attachDataTableColumnSearch(table);
        });
    </script>
@stop
