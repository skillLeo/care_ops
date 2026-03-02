@extends('adminlte::page')

@section('title', 'Daily Chart Compliance Summary')

@section('content_header')
    <h1>Daily Chart Compliance Summary</h1>
@stop

@section('content')
    <div class="d-flex justify-content-start mb-3 gap-2">
        <a href="{{ route('clinical-notes.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('clinical-notes.tracker.download') }}" class="btn btn-outline-secondary">Download PDF</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upcoming Clinical Notes</h3>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label for="counselorFilter" class="form-label">Counselor</label>
                    <select id="counselorFilter" class="form-select">
                        <option value="">All Counselors</option>
                        @foreach ($counselors as $counselor)
                            <option value="{{ $counselor }}">{{ $counselor }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-outline-secondary filter-status active" data-status="">
                        All
                    </button>
                    <button type="button" class="btn btn-outline-danger filter-status" data-status="overdue">
                        Overdue
                    </button>
                    <button type="button" class="btn btn-outline-primary filter-status" data-status="due">
                        Due Soon
                    </button>
                </div>
            </div>
            <table class="table table-bordered" id="clinicalNoteTrackerTable">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Counselor Name</th>
                        @foreach ($noteTypeOptions as $noteType)
                            <th>{{ $noteType }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trackerRows as $row)
                        @php
                            $rowOverdue = false;
                            $rowDueSoon = false;
                            $today = \Carbon\Carbon::today();
                            $cutoffDate = \Carbon\Carbon::parse('2026-02-09');
                            foreach ($noteTypeOptions as $noteType) {
                                $dueDate = $row['due_dates']->get($noteType);
                                $dueDates = is_array($dueDate) ? $dueDate : ($dueDate ? [$dueDate] : []);
                                foreach ($dueDates as $date) {
                                    if ($date === 'Complete') {
                                        continue;
                                    }
                                    $parsedDue = \Carbon\Carbon::parse($date);
                                    if ($parsedDue->lt($cutoffDate)) {
                                        continue;
                                    }
                                    if ($parsedDue->lt($today)) {
                                        $rowOverdue = true;
                                    } elseif ($parsedDue->lte($today->copy()->addDays(5))) {
                                        $rowDueSoon = true;
                                    }
                                }
                            }
                        @endphp
                        <tr data-has-overdue="{{ $rowOverdue ? '1' : '0' }}" data-has-due="{{ $rowDueSoon ? '1' : '0' }}">
                            <td>{{ strtoupper($row['client']->last_name ?? '') }}, {{ strtoupper($row['client']->first_name ?? '') }}</td>
                            <td>{{ $row['client']->counselor?->name ?? 'Unassigned' }}</td>
                            @foreach ($noteTypeOptions as $noteType)
                                @php
                                    $dueDate = $row['due_dates']->get($noteType);
                                @endphp
                                <td class="text-center">
                                    @if ($dueDate === 'Complete')
                                        <span class="text-success">Complete</span>
                                    @elseif (is_array($dueDate))
                                        @php
                                            $cutoffDate = \Carbon\Carbon::parse('2026-02-09');
                                            $filteredDueDates = collect($dueDate)->filter(function ($date) use ($cutoffDate) {
                                                return \Carbon\Carbon::parse($date)->gte($cutoffDate);
                                            })->values();
                                        @endphp
                                        @forelse ($filteredDueDates as $date)
                                            @php
                                                $dueClass = '';
                                                $today = \Carbon\Carbon::today();
                                                $parsedDue = \Carbon\Carbon::parse($date);
                                                if ($parsedDue) {
                                                    if ($parsedDue->lt($today)) {
                                                        $dueClass = 'text-danger';
                                                    } elseif ($parsedDue->lte($today->copy()->addDays(5))) {
                                                        $dueClass = 'text-primary';
                                                    }
                                                }
                                            @endphp
                                            @php
                                                $statusClass = 'status-neutral';
                                                if ($parsedDue->lt($today)) {
                                                    $statusClass = 'status-overdue';
                                                } elseif ($parsedDue->lte($today->copy()->addDays(5))) {
                                                    $statusClass = 'status-due';
                                                }
                                            @endphp
                                            <div class="due-date {{ $dueClass }} {{ $statusClass }}">{{ $parsedDue->format('m/d/Y') }}</div>
                                        @empty
                                            <span class="text-muted no-date">—</span>
                                        @endforelse
                                    @elseif ($dueDate)
                                        @php
                                            $singleDue = \Carbon\Carbon::parse($dueDate);
                                            $cutoffDate = \Carbon\Carbon::parse('2026-02-09');
                                            if ($singleDue->lt($cutoffDate)) {
                                                $singleDue = null;
                                            }
                                        @endphp
                                        @if ($singleDue)
                                            @php
                                                $statusClass = 'status-neutral';
                                                $singleClass = '';
                                                if ($singleDue->lt(\Carbon\Carbon::today())) {
                                                    $statusClass = 'status-overdue';
                                                    $singleClass = 'text-danger';
                                                } elseif ($singleDue->lte(\Carbon\Carbon::today()->copy()->addDays(5))) {
                                                    $statusClass = 'status-due';
                                                    $singleClass = 'text-primary';
                                                }
                                            @endphp
                                            <div class="due-date {{ $singleClass }} {{ $statusClass }}">{{ $singleDue->format('m/d/Y') }}</div>
                                        @else
                                            <span class="text-muted no-date">—</span>
                                        @endif
                                    @else
                                        <span class="text-muted no-date">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($trackerRows->isEmpty())
                <div class="text-center text-muted mt-2">No clients available.</div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .d-none { display: none !important; }
    </style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        let statusFilter = '';
        const trackerTable = $('#clinicalNoteTrackerTable').DataTable({
            pageLength: 50,
            scrollX: true,
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'clinicalNoteTrackerTable') {
                return true;
            }
            const row = trackerTable.row(dataIndex).node();
            const hasOverdue = $(row).data('has-overdue') === 1 || $(row).data('has-overdue') === '1';
            const hasDue = $(row).data('has-due') === 1 || $(row).data('has-due') === '1';

            if (!statusFilter) {
                return true;
            }
            if (statusFilter === 'overdue') {
                return hasOverdue;
            }
            if (statusFilter === 'due') {
                return hasDue;
            }
            return true;
        });

        $('#counselorFilter').on('change', function() {
            trackerTable.column(1).search(this.value).draw();
        });

        $('.filter-status').on('click', function() {
            statusFilter = $(this).data('status');
            $('.filter-status').removeClass('active');
            $(this).addClass('active');
            trackerTable.draw();
            updateVisibleDates();
        });

        function updateVisibleDates() {
            $('#clinicalNoteTrackerTable tbody tr').each(function() {
                const $row = $(this);
                $row.find('td').each(function() {
                    const $cell = $(this);
                    const $dates = $cell.find('.due-date');
                    if ($dates.length === 0) {
                        return;
                    }
                    $dates.removeClass('d-none');
                    if (statusFilter === 'overdue') {
                        $dates.not('.status-overdue').addClass('d-none');
                    } else if (statusFilter === 'due') {
                        $dates.not('.status-overdue, .status-due').addClass('d-none');
                    }
                    const hasVisible = $dates.filter(':not(.d-none)').length > 0;
                    $cell.find('.no-date').toggleClass('d-none', hasVisible);
                });
            });
        }

        trackerTable.on('draw', updateVisibleDates);
    </script>
@stop
