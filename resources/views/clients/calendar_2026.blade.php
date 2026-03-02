@extends('adminlte::page')

@section('title', 'Client Calendar 2026 - ' . $client->last_name . ', ' . $client->first_name)

@section('content_header')
    <h1>Client Calendar (Attendance 2026) for {{ $client->last_name }}, {{ $client->first_name }}</h1>
@stop

@section('content')
    <div class="form-group d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary" onclick="changeMonth(-1)">&#8592;</button>
            <input type="month" name="month" id="month" class="form-control w-auto mx-2" value="{{ $currentMonth }}" required>
            <button type="button" class="btn btn-primary" onclick="changeMonth(1)">&#8594;</button>
        </div>

        <div class="d-flex align-items-center mt-2 mt-md-0 ml-md-4">
            <strong class="mr-2">Attendance 2026 Legend:</strong>
            <span class="badge badge-secondary">Service codes show when attendance exists.</span>
            <span class="badge text-white ml-1" style="background-color: #dc3545">Hospitalization</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Monday</th>
                    <th>Tuesday</th>
                    <th>Wednesday</th>
                    <th>Thursday</th>
                    <th>Friday</th>
                    <th>Saturday</th>
                    <th>Sunday</th>
                </tr>
            </thead>
            <tbody id="calendar-body">
                @include('claims.calendar_body_2026', [
                    'claimLines' => $claimLines,
                    'attendance2026' => $attendance2026,
                    'processedClaims' => $processedClaims,
                    'currentMonth' => $currentMonth,
                    'levelOfCares' => $client->levelOfCareHistory,
                    'hospitalizations' => $hospitalizations ?? collect(),
                ])
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <h5>Level of Care History:</h5>
        @foreach ($levelOfCares as $loc)
            <p>
                <span class="badge" style="background-color: #3490dc; color: white;">
                    {{ $loc->levelOfCare?->display_name ?? '-' }} ({{ $loc->start_date }} to {{ $loc->end_date ?? 'Present' }})
                </span>
            </p>
        @endforeach
    </div>
@stop

@section('css')
    <style>
        #calendar-body td {
            height: 175px;
            vertical-align: top;
            font-size: 12px;
        }
    </style>
@stop

@section('js')
    <script>
        function changeMonth(offset) {
            let monthInput = document.getElementById('month');
            let [year, month] = monthInput.value.split('-').map(Number);

            month += offset;
            if (month === 0) {
                month = 12;
                year -= 1;
            } else if (month === 13) {
                month = 1;
                year += 1;
            }

            let newMonth = `${year}-${month.toString().padStart(2, '0')}`;
            monthInput.value = newMonth;
            window.location.href = `{{ route('clients.calendar2026', $client->id) }}?month=${newMonth}`;
        }

        document.getElementById('month').addEventListener('change', function() {
            window.location.href = `{{ route('clients.calendar2026', $client->id) }}?month=${this.value}`;
        });
    </script>
@stop
