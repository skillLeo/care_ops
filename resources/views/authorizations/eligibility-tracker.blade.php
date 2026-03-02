@extends('adminlte::page')

@section('title', 'Eligibility Tracker')

@section('content_header')
    <h1>Eligibility Tracker</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="eligibilityTrackerTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Medicaid ID</th>
                        <th>Eligibility</th>
                        <th>Eligibility Date</th>
                        <th>Last Checked Date</th>
                        <th>Eligibility Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        @php
                            $redeterminationDate = $client->redetermination_date ? \Carbon\Carbon::parse($client->redetermination_date) : null;
                            $lastChecked = $client->redetermination_last_checked ? \Carbon\Carbon::parse($client->redetermination_last_checked) : null;
                            $eligibilityLabel = $client->eligibility ? ucwords(str_replace('_', ' ', $client->eligibility)) : '-';
                        @endphp
                        <tr>
                            <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                            <td>
                                @can('client.view_medicaid_id')
                                    {{ $client->medicaid_id ?? '-' }}
                                @else
                                    Restricted
                                @endcan
                            </td>
                            <td class="eligibility-status" data-eligibility="{{ $client->eligibility }}">
                                {{ $eligibilityLabel }}
                            </td>
                            <td class="redetermination-date" data-date="{{ $redeterminationDate?->format('Y-m-d') }}" data-order="{{ $redeterminationDate?->format('Y-m-d') }}">
                                {{ $redeterminationDate?->format('m/d/Y') ?? '-' }}
                            </td>
                            <td class="last-checked-date" data-date="{{ $lastChecked?->format('Y-m-d') }}" data-order="{{ $lastChecked?->format('Y-m-d') }}">
                                {{ $lastChecked?->format('m/d/Y') ?? '-' }}
                            </td>
                            <td>{{ $client->redetermination_remarks ?? '-' }}</td>
                            <td>
                                @canany(['client.view', 'client.edit'])
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @can('client.view')
                                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary" aria-label="View client">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('client.edit')
                                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit client">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        @endcan
                                    </div>
                                @else
                                    <span>-</span>
                                @endcanany
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
        .date-overdue {
            color: red !important;
        }

        .date-warning {
            color: #d39e00 !important;
        }

        .eligibility-not-eligible {
            color: red !important;
            font-weight: 600;
        }

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

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    function parseCellDate(cell) {
        const dateValue = cell.dataset.date;
        if (!dateValue) {
            return null;
        }

        const parsed = new Date(`${dateValue}T00:00:00`);
        return isNaN(parsed.getTime()) ? null : parsed;
    }

    function parseUsDate(text) {
        const parts = text.split('/');
        if (parts.length !== 3) {
            return null;
        }

        const [month, day, year] = parts;
        const parsed = new Date(`${year}-${month}-${day}T00:00:00`);
        return isNaN(parsed.getTime()) ? null : parsed;
    }

    function getRedeterminationStatus(cell) {
        const date = parseCellDate(cell);
        if (!date) {
            return null;
        }

        const twoMonthsAway = new Date(today);
        twoMonthsAway.setMonth(twoMonthsAway.getMonth() + 2);

        if (date < today) {
            return 'overdue';
        }

        if (date <= twoMonthsAway) {
            return 'warning';
        }

        return null;
    }

    function getLastCheckedStatus(cell) {
        const date = parseCellDate(cell);
        if (!date) {
            return null;
        }

        const oneMonthAgo = new Date(today);
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);

        return date < oneMonthAgo ? 'overdue' : null;
    }

    function applyDateColors() {
        const twoMonthsAway = new Date(today);
        twoMonthsAway.setMonth(twoMonthsAway.getMonth() + 2);

        const oneMonthAgo = new Date(today);
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);

        document.querySelectorAll('.redetermination-date').forEach(cell => {
            cell.classList.remove('date-overdue', 'date-warning');
            const date = parseCellDate(cell);

            if (!date) {
                return;
            }

            if (date < today) {
                cell.classList.add('date-overdue');
            } else if (date <= twoMonthsAway) {
                cell.classList.add('date-warning');
            }
        });

        document.querySelectorAll('.last-checked-date').forEach(cell => {
            cell.classList.remove('date-overdue');
            const date = parseCellDate(cell);

            if (!date) {
                return;
            }

            if (date < oneMonthAgo) {
                cell.classList.add('date-overdue');
            }
        });

        document.querySelectorAll('.eligibility-status').forEach(cell => {
            const eligibility = (cell.dataset.eligibility || '').toLowerCase();
            cell.classList.toggle('eligibility-not-eligible', eligibility === 'not_eligible');
        });
    }

    $(document).ready(function () {
        let filterMode = 'all';
        const reportTitle = `Eligibility Tracker (${new Date().toLocaleString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        })})`;

        const trackerTable = $('#eligibilityTrackerTable').DataTable({
            dom: 'Blfrtip',
            order: [[3, 'asc']],
            buttons: [
                {
                    text: 'Show All',
                    className: 'btn btn-sm btn-primary filter-btn',
                    action: function (e, dt, node) {
                        filterMode = 'all';
                        dt.draw();
                        $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
                        $(node).removeClass('btn-outline-secondary').addClass('btn-primary');
                    }
                },
                {
                    text: 'Not Eligible',
                    className: 'btn btn-sm btn-outline-secondary filter-btn',
                    action: function (e, dt, node) {
                        filterMode = 'not-eligible';
                        dt.draw();
                        $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
                        $(node).removeClass('btn-outline-secondary').addClass('btn-primary');
                    }
                },
                {
                    text: 'Eligibility Due',
                    className: 'btn btn-sm btn-outline-secondary filter-btn',
                    action: function (e, dt, node) {
                        filterMode = 'redetermination-warning';
                        dt.draw();
                        $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
                        $(node).removeClass('btn-outline-secondary').addClass('btn-primary');
                    }
                },
                {
                    text: 'Last Checked Overdue',
                    className: 'btn btn-sm btn-outline-secondary filter-btn',
                    action: function (e, dt, node) {
                        filterMode = 'last-checked-overdue';
                        dt.draw();
                        $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
                        $(node).removeClass('btn-outline-secondary').addClass('btn-primary');
                    }
                },
                {
                    text: 'Download PDF',
                    extend: 'pdfHtml5',
                    orientation: 'portrait',
                    pageSize: 'LETTER',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    },
                    customize: function (doc) {
                        applyPdfLetterhead(doc, 'portrait');
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 9;
                        doc.pageMargins = [10, 10, 10, 10];

                        const redetIndex = 3;
                        const lastCheckedIndex = 4;
                        const body = doc.content[1].table.body;

                        const pdfToday = new Date();
                        pdfToday.setHours(0, 0, 0, 0);
                        const pdfTwoMonthsAway = new Date(pdfToday);
                        pdfTwoMonthsAway.setMonth(pdfTwoMonthsAway.getMonth() + 2);
                        const pdfOneMonthAgo = new Date(pdfToday);
                        pdfOneMonthAgo.setMonth(pdfOneMonthAgo.getMonth() - 1);

                        for (let i = 1; i < body.length; i++) {
                            const redetText = body[i][redetIndex].text;
                            const lastCheckedText = body[i][lastCheckedIndex].text;

                            const redet = parseUsDate(redetText);
                            if (redet) {
                                if (redet < pdfToday) {
                                    body[i][redetIndex].color = 'red';
                                } else if (redet <= pdfTwoMonthsAway) {
                                    body[i][redetIndex].color = '#d39e00';
                                }
                            }

                            const lastChecked = parseUsDate(lastCheckedText);
                            if (lastChecked && lastChecked < pdfOneMonthAgo) {
                                body[i][lastCheckedIndex].color = 'red';
                            }
                        }
                    }
                },
                {
                    text: 'Download Excel',
                    extend: 'excelHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    text: 'Download CSV',
                    extend: 'csvHtml5',
                    title: reportTitle,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ]
        });

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'eligibilityTrackerTable') {
                return true;
            }

            if (filterMode === 'all') {
                return true;
            }

            const rowNode = trackerTable.row(dataIndex).node();
            const eligibilityCell = rowNode.querySelector('.eligibility-status');
            const redeterminationCell = rowNode.querySelector('.redetermination-date');
            const lastCheckedCell = rowNode.querySelector('.last-checked-date');

            if (filterMode === 'not-eligible') {
                const eligibility = (eligibilityCell?.dataset.eligibility || '').toLowerCase();
                return eligibility === 'not_eligible';
            }

            if (filterMode === 'redetermination-warning') {
                const status = getRedeterminationStatus(redeterminationCell);
                return status === 'overdue' || status === 'warning';
            }

            if (filterMode === 'last-checked-overdue') {
                const status = getLastCheckedStatus(lastCheckedCell);
                return status === 'overdue';
            }

            return true;
        });

        applyDateColors();
        $('#eligibilityTrackerTable').on('draw.dt', applyDateColors);
    });
</script>
@stop
