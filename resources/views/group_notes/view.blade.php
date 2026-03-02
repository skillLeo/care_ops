@extends('adminlte::page')

@section('title', 'View Group Notes')

@section('content_header')
    <h1>View Group Notes for {{ $counselor->name }} ({{ $counselor->levelOfCare?->display_name ?? '-' }})</h1>
@stop

@section('content')

        <!-- Month Selector with Clean Arrows -->
        <div class="form-group d-flex align-items-center">
            <button type="button" class="btn btn-primary" onclick="changeMonth(-1)">&#8592;</button>
            <input type="month" name="month" id="month" class="form-control w-auto mx-2" value="{{ $currentMonth }}" required>
            <button type="button" class="btn btn-primary" onclick="changeMonth(1)">&#8594;</button>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="loading-overlay" style="display: none;">
            <div class="spinner-border text-primary"></div>
            <p>Loading...</p>
        </div>

        <!-- Calendar -->
        <div class="table-responsive position-relative">
            <table class="table table-bordered text-center">
                <thead>
                    <tr>
                        <th>Sunday</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                    </tr>
                </thead>
                <tbody id="calendar-body"></tbody>
            </table>
        </div>
@stop

@section('css')
<style>
    #calendar-body td {
        height: 100px; /* Adjust the height as needed */
        /* vertical-align: middle;*/ /*Ensures content is vertically centered */
        text-align: center; /* Ensures text remains centered */
    }
    /* Styling for month navigation buttons */
    .btn-outline-secondary {
        font-size: 20px;
        padding: 5px 15px;
        border-radius: 5px;
    }

    /* Loading Overlay */
    .loading-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.3);
        padding: 200px;
        border-radius: 8px;
        text-align: center;
        z-index: 10;
    }
</style>
@stop

@section('js')
<script>
    let currentMonth = "{{ $currentMonth }}";
    let counselorLevel = "{{ $counselor->levelOfCare?->level_of_care ?? '' }}";

    document.addEventListener('DOMContentLoaded', function() {
        fetchGroupNotes(currentMonth);
    });

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
        fetchGroupNotes(newMonth);
    }

    function fetchGroupNotes(month) {
        document.getElementById('loading-overlay').style.display = "block";

        fetch(`{{ url('/group-notes/data') }}/${month}/{{ $counselor->id }}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading-overlay').style.display = "none";
                generateCalendar(month, data.groupNotes);
            })
            .catch(error => {
                console.error('Error fetching group notes:', error);
                document.getElementById('loading-overlay').style.display = "none";
            });
    }

    function generateCalendar(monthYear, selectedDates) {
        const [year, month] = monthYear.split('-').map(Number);
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const today = new Date().toISOString().split('T')[0];
        const tbody = document.getElementById('calendar-body');
        tbody.innerHTML = '';

        let dateCounter = 1;
        for (let i = 0; i < 6; i++) {
            let row = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                let cell = document.createElement('td');
                if ((i === 0 && j < firstDay) || dateCounter > daysInMonth) {
                    cell.innerHTML = '';
                } else {
                    let date = `${year}-${month.toString().padStart(2, '0')}-${dateCounter.toString().padStart(2, '0')}`;
                    let isPastDate = date > today ? 'hidden' : '';
                    let isCurrentDate = date === today ? 'border: 4px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin: auto;' : '';
                    let isRestricted = (counselorLevel === 'IOP' && (j >= 5 || j === 0)) || (counselorLevel === 'PHP' && j === 0);
                    let checked = selectedDates.includes(date);

                    cell.innerHTML = `
                        <div style="${isCurrentDate} padding: 5px;">${dateCounter}</div>
                        <input type="checkbox" data-date="${date}" onclick="return false" ${checked ? 'checked' : ''} ${isPastDate} ${isRestricted ? 'hidden' : ''}>
                    `;
                    dateCounter++;
                }
                row.appendChild(cell);
            }
            tbody.appendChild(row);
            if (dateCounter > daysInMonth) break;
        }
    }

    document.getElementById('group-note-form').addEventListener('submit', function() {
        document.getElementById('added-dates').value = JSON.stringify(addedDates);
        document.getElementById('removed-dates').value = JSON.stringify(removedDates);
    });
</script>
@stop
