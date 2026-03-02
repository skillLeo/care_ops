@extends('adminlte::page')

@section('title', 'Claims')

@section('content_header')
    <h1>Claims</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Filter Claims</h3>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row">
                <div class="form-group col-md-3 mb-3">
                    <label for="submission_start">Submission From</label>
                    <input type="date" class="form-control" id="submission_start">
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="submission_end">Submission To</label>
                    <input type="date" class="form-control" id="submission_end">
                </div>

                <div class="form-group col-md-3 mb-3">
                    <label for="payment_start">Payment From</label>
                    <input type="date" class="form-control" id="payment_start">
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="payment_end">Payment To</label>
                    <input type="date" class="form-control" id="payment_end">
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="guest_filter">Guest</label>
                    <select class="form-control" id="guest_filter">
                        <option value="">--</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="service_start">Service From</label>
                    <input type="date" class="form-control" id="service_start">
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="service_end">Service To</label>
                    <input type="date" class="form-control" id="service_end">
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="status_filter">Status</label>
                    <select class="form-control" id="status_filter">
                        <option value="">--</option>
                        <option value="In-Process">In-Process</option>
                        <option value="Processed">Processed</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
                <div class="form-group col-md-12 d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-primary mr-2" id="apply-filters">Search</button>
                    <button type="button" class="btn btn-secondary" id="clear-filters">Clear</button>
                </div>
            </form>

        </div>
    </div>

    <div class="mb-3">
        <div id="pdf-export-container" class="d-inline ml-3"></div>
        <button id="filter-all" class="btn btn-secondary ml-1">Show All</button>
        <button id="filter-paid" class="btn btn-primary ml-2">Paid</button>
        <button id="filter-processed" class="btn btn-info ml-1">Processed</button>
        <button id="filter-inprocess" class="btn btn-warning ml-1">In-Process</button>
        @can('claim.create')
            <a href="{{ route('claims.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Claim
            </a>
        @endcan
        <div id="pdf-export-container" class="d-inline ml-3"></div>


    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Claims</h3>
        </div>
        <div class="card-body p-0">
            <table id="claimsTable" class="table table-bordered table-hover table-striped mb-0">

                <thead class="bg-light">
                    <tr>
                        {{-- <th>Transaction #</th> --}}
                        {{-- <th>Carelon #</th> --}}
                        <th>Client</th>
                        <th class="text-center">Member ID</th>
                        <th class="text-center">Date of Birth</th>
                        <th class="text-center">Guest</th>
                        <th class="text-center">Submission</th>
                        {{-- <th>Lines</th> --}}
                        <th class="text-center">Service Dates</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Billed</th>
                        <th class="text-center">Processed</th>
                        <th class="text-center">Denied</th>
                        <th class="text-center">Paid</th>
                        <th class="text-center">Payment Date</th>
                        <th class="text-center no-search">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($claims as $claim)
                        <tr>
                            {{-- <td>{{ $claim->claim_number }}</td> --}}
                            {{-- <td>{{ $claim->carelon_claim_number }}</td> --}}
                            <td>{{ strtoupper($claim->client->last_name) }}, {{ strtoupper($claim->client->first_name) }}
                            </td>
                            <td class="text-center">
                                @can('client.view_carelon_id')
                                    {{ $claim->client->carelon_id }}
                                @else
                                    Restricted
                                @endcan
                            </td>
                            <td class="text-center" data-order="{{ \Carbon\Carbon::parse($claim->client->date_of_birth)->format('Y-m-d') }}">
                                {{ \Carbon\Carbon::parse($claim->client->date_of_birth)->format('m/d/Y') }}</td>
                            <td class="text-center">{{ $claim->client->guest ? 'Yes' : 'No' }}</td>
                            <td class="text-center" data-order="{{ \Carbon\Carbon::parse($claim->submission_date)->format('Y-m-d') }}">
                                {{ \Carbon\Carbon::parse($claim->submission_date)->format('m/d/Y') }}
                            </td>
                            {{-- <td>{{ $claim->lines_count }}</td> --}}
                            <td class="text-center">{{ $claim->serviceDates() }}</td>
                            <td class="text-center">{{ $claim->status() }}</td>
                            <td class="text-right">${{ number_format($claim->totalBilledAmount(), 2) }}</td>
                            <td class="text-right">${{ number_format($claim->totalProcessedAmount(), 2) }}</td>
                            <td class="text-right">${{ number_format($claim->totalDeniedAmount(), 2) }}</td>
                            <td class="text-right">${{ number_format($claim->totalPaidAmount(), 2) }}</td>
                            <td class="text-center">{{ $claim->paymentDates() }}</td>
                            <td class="text-center">
                                @can('claim.view')
                                    <a href="{{ route('claims.show', $claim) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endcan
                                @can('claim.edit')
                                    <a href="{{ route('claims.edit', $claim) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endcan
                                @if ($claim->status() === 'In-Process')
                                    @can('claim.process_all')
                                        <form action="{{ route('claims.processAll', $claim) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Process All">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                                @if ($openChecks->count() === 1 && $claim->status() != 'Paid')
                                    @can('claim.add_check')
                                        <form action="{{ route('claims.addCheck', $claim) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info" title="Add Check">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                                {{-- <a href="{{ route('claims.checkStatus', $claim->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-sync-alt"></i> Check Status
                                </a> --}}
                                @if(auth()->user()->canDeleteRecords())
                                    @can('claim.delete')
                                        <form action="{{ route('claims.destroy', $claim) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($claims->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center text-muted">No claims found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/datatables-column-search.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <!-- Required for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        @include('partials.pdf-letterhead-js')

        $(document).ready(function() {
            // Add the summary row before the real header
            $('#claimsTable thead').prepend(`
                <tr class="bg-light font-weight-bold" id="summary-row">
                    <th colspan="7" class="text-right">Total:</th>
                    <th id="sum-billed" class="text-right">$0.00</th>
                    <th id="sum-processed" class="text-right">$0.00</th>
                    <th id="sum-denied" class="text-right">$0.00</th>
                    <th id="sum-paid" class="text-right">$0.00</th>
                    <th colspan="2"></th>
                </tr>
            `);

            var table = $('#claimsTable').DataTable({
                pageLength: 20,
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> Export PDF',
                    className: 'btn btn-danger',
                    orientation: 'landscape',
                    pageSize: 'LETTER',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    },
                    customize: function(doc) {
                        applyPdfLetterhead(doc, 'landscape');
                        doc.pageMargins = [10, 10, 10, 10];
                        doc.styles.tableHeader.alignment = 'left';

                        const billed = $('#sum-billed').text();
                        const processed = $('#sum-processed').text();
                        const denied = $('#sum-denied').text();
                        const paid = $('#sum-paid').text();

                        // Insert title first
                        doc.content.unshift({
                            text: 'Claims (' + new Date().toLocaleDateString() + ')',
                            style: 'header',
                            alignment: 'center',
                            margin: [0, 0, 0, 10]
                        });

                        // Insert totals table
                        doc.content.splice(1, 0, {
                            table: {
                                widths: ['*', '*', '*', '*'],
                                body: [
                                    [{
                                            text: 'Total Billed: ' + billed,
                                            bold: true
                                        },
                                        {
                                            text: 'Total Processed: ' + processed,
                                            bold: true
                                        },
                                        {
                                            text: 'Total Denied: ' + denied,
                                            bold: true
                                        },
                                        {
                                            text: 'Total Paid: ' + paid,
                                            bold: true
                                        }
                                    ]
                                ]
                            },
                            layout: {
                                hLineWidth: () => 1,
                                vLineWidth: () => 1,
                                hLineColor: () => '#000',
                                vLineColor: () => '#000'
                            },
                            margin: [0, 0, 0, 10]
                        });
                    }

                }]
            });

            attachDataTableColumnSearch(table);
            table.buttons().container().appendTo('#pdf-export-container');

            function updateTotals() {
                let billed = 0,
                    processed = 0,
                    denied = 0,
                    paid = 0;

                table.rows({
                    search: 'applied'
                }).every(function() {
                    const row = $(this.node());
                    billed += parseFloat(row.find('td').eq(7).text().replace(/[^0-9.-]+/g, '')) || 0;
                    processed += parseFloat(row.find('td').eq(8).text().replace(/[^0-9.-]+/g, '')) || 0;
                    denied += parseFloat(row.find('td').eq(9).text().replace(/[^0-9.-]+/g, '')) || 0;
                    paid += parseFloat(row.find('td').eq(10).text().replace(/[^0-9.-]+/g, '')) || 0;
                });

                $('#sum-billed').text(
                    `$${billed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                );
                $('#sum-processed').text(
                    `$${processed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                );
                $('#sum-denied').text(
                    `$${denied.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                );
                $('#sum-paid').text(
                    `$${paid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                );
            }

            updateTotals();
            table.on('draw', updateTotals);

            function filterByStatus(status) {
                table.columns(6).search(status).draw();
            }

            $('#filter-paid').on('click', function() {
                filterByStatus('Paid');
            });

            $('#filter-processed').on('click', function() {
                filterByStatus('Processed');
            });

            $('#filter-inprocess').on('click', function() {
                filterByStatus('In-Process');
            });

            $('#filter-all').on('click', function() {
                table.columns(6).search('').draw();
            });

            // Set default submission date range
            // let today = new Date().toISOString().split('T')[0];
            // let sevenDaysAgo = new Date(Date.now() - 6 * 86400000).toISOString().split('T')[0];
            // $('#submission_start').val(sevenDaysAgo);
            // $('#submission_end').val(today);

            // Filter logic
            $('#apply-filters').on('click', function() {
                let subStart = $('#submission_start').val();
                let subEnd = $('#submission_end').val();
                let serviceStart = $('#service_start').val();
                let serviceEnd = $('#service_end').val();
                let guest = $('#guest_filter').val();
                let status = $('#status_filter').val();
                let paymentStart = $('#payment_start').val();
                let paymentEnd = $('#payment_end').val();

                $.fn.dataTable.ext.search = [];

                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    let submissionDate = data[4] ? new Date(data[4]) : null;
                    let paymentDate = data[11] ? new Date(data[11]) : null;
                    let serviceDates = data[5] || '';
                    let guestVal = data[3] || '';
                    let statusVal = data[6] || '';

                    // Parse service start/end dates
                    let [serviceStartVal, serviceEndVal] = serviceDates.split(' - ').map(str => new Date(str));

                    let pass = true;

                    // Submission date range
                    if (subStart && submissionDate) {
                        const startDate = new Date(subStart);
                        const submission = new Date(submissionDate);
                        startDate.setHours(0, 0, 0, 0);
                        submission.setHours(0, 0, 0, 0);
                        if (startDate > submission) pass = false;
                    }

                    if (subEnd && submissionDate) {
                        const endDate = new Date(subEnd);
                        const submission = new Date(submissionDate);
                        endDate.setHours(0, 0, 0, 0);
                        submission.setHours(0, 0, 0, 0);
                        if (endDate < submission) pass = false;
                    }


                    // Service date range
                    const claimStart = serviceStart ? new Date(serviceStart) : null;
                    const claimEnd = serviceEnd ? new Date(serviceEnd) : null;
                    const filterStart = serviceStartVal ? new Date(serviceStartVal) : null;
                    const filterEnd = serviceEndVal ? new Date(serviceEndVal) : null;

                    // Normalize dates to ignore time
                    if (claimStart) claimStart.setHours(0, 0, 0, 0);
                    if (claimEnd) claimEnd.setHours(0, 0, 0, 0);
                    if (filterStart) filterStart.setHours(0, 0, 0, 0);
                    if (filterEnd) filterEnd.setHours(0, 0, 0, 0);

                    // Handle overlap logic only when at least one side of both ranges is available
                    if ((claimStart || claimEnd) && (filterStart || filterEnd)) {
                        const claimRangeStart = claimStart || claimEnd;
                        const claimRangeEnd = claimEnd || claimStart;
                        const filterRangeStart = filterStart || filterEnd;
                        const filterRangeEnd = filterEnd || filterStart;

                        if (claimRangeEnd < filterRangeStart || claimRangeStart > filterRangeEnd) {
                            pass = false; // No overlap
                        }
                    }


                    // Guest filter
                    if (guest && guest !== guestVal) pass = false;

                    // Status filter
                    if (status && status !== statusVal) pass = false;

                    if ((paymentStart || paymentEnd) && !paymentDate)
                        pass = false; // If payment range is set but no payment date, exclude

                    if (paymentStart && paymentDate) {
                        const startDate = new Date(paymentStart);
                        const payment = new Date(paymentDate);
                        startDate.setHours(0, 0, 0, 0);
                        payment.setHours(0, 0, 0, 0);
                        if (startDate > payment) pass = false;
                    }

                    if (paymentEnd && paymentDate) {
                        const endDate = new Date(paymentEnd);
                        const payment = new Date(paymentDate);
                        endDate.setHours(0, 0, 0, 0);
                        payment.setHours(0, 0, 0, 0);
                        if (endDate < payment) pass = false;
                    }

                    return pass;
                });

                table.draw();
            });

            // Clear filters
            $('#clear-filters').on('click', function() {
                $('#filter-form')[0].reset();
                // $('#submission_start').val(sevenDaysAgo);
                // $('#submission_end').val(today);
                $.fn.dataTable.ext.search = [];
                table.draw();
            });

        });
    </script>
@stop
