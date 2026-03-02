@extends('adminlte::page')

@section('title', 'Batch Notes')

@section('content_header')
    <h1>Batch Notes</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('notes.batchStore') }}" id="noteForm">
        @csrf

        <div class="d-flex mb-3 align-items-center">
            <label class="mr-2" for="service_date">Service Date</label>
            <input type="date"
                   name="service_date"
                   id="service_date"
                   class="form-control w-25 mr-4"
                   required
                   max="{{ \Carbon\Carbon::today()->toDateString() }}"
                   value="{{ old('service_date', $selectedDate ?? \Carbon\Carbon::today()->toDateString()) }}">

            <label class="mr-2" for="session_type">Session Type</label>
            <select name="session_type"
                    id="session_type"
                    class="form-control w-25"
                    onchange="onSessionTypeChange(this)">
                <option value="">-- Select --</option>
                @foreach (['group', 'peer_individual', 'peer_group'] as $type)
                    <option value="{{ $type }}" {{ $sessionType === $type ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <input type="hidden" name="session_type" id="session_type_input" value="{{ $sessionType }}">

        <table class="table table-bordered" id="clientTable">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th class="text-center">Level of Care</th>
                    <th class="text-center">Attendance</th>
                    <th class="text-center">Attendance Unit</th>

                    <th class="text-center">
                        <input type="number" id="masterUnits" class="form-control form-control-sm w-50 text-center mx-auto" placeholder="Units">
                        <div>Note Units</div>
                    </th>

                    {{-- <th class="text-center">
                        <select id="masterStatus" class="form-control form-control-sm">
                            <option value="">--</option>
                            <option value="complete">Complete</option>
                            <option value="incomplete">Incomplete</option>
                            <option value="partial_complete">Partial Complete</option>
                        </select>
                        <div>Status</div>
                    </th> --}}


                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                    @php
                        $att = $attendanceData[$client->id] ?? null;
                        $note = $noteData[$client->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        <td class="text-center">{{ $client->level_of_care }}</td>
                        <td class="text-center">
                            {{ $att ? 'Present' : '-' }}
                        </td>
                        <td class="text-center">
                            {{ $att && $att->units ? $att->units : '-' }}
                        </td>

                        <td class="text-center">
                            <input type="number"
                                   name="notes[{{ $client->id }}][units]"
                                   id="units-{{ $client->id }}"
                                   class="form-control form-control-sm w-50 text-center mx-auto unit-input"
                                   value="{{ $note->units ?? '' }}"
                                   {{-- {{ $att ? '' : 'disabled' }} --}}
                                   >
                        </td>

                        {{-- <td class="text-center">
                            <select name="attendance[{{ $client->id }}][status]" class="form-control form-control-sm attendance-status">
                                <option value="">--</option>
                                <option value="complete" {{ $att && $att->status === 'complete' ? 'selected' : '' }}>Complete</option>
                                <option value="incomplete" {{ $att && $att->status === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                                <option value="partial_complete" {{ $att && $att->status === 'partial_complete' ? 'selected' : '' }}>Partial Complete</option>
                            </select>
                        </td> --}}


                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>
@stop


@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<style>
    .dataTables_length select {
        background-color: #343a40; /* dark gray */
        color: #ffffff;            /* white text */
        border: 1px solid #6c757d;
        border-radius: 4px;
        padding: 4px 8px;
    }

    .dataTables_length select option {
        background-color: #343a40;
        color: #ffffff;
    }
</style>
@endsection


@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
    @include('partials.pdf-letterhead-js')

    $('#clientTable').DataTable({
        dom: 'Blfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                filename: 'Attendance {{ \Carbon\Carbon::parse($selectedDate)->format("m-d-Y") }} {{ ucfirst(str_replace("_", " ", $sessionType)) }}',
                pageSize: 'LETTER',
                className: 'btn btn-danger',

                // title: `Client Attendance ({{ \Carbon\Carbon::parse($selectedDate)->format('m/d/Y') }} - {{ ucfirst(str_replace('_', ' ', $sessionType)) }})`,
                exportOptions: {
                    columns: [0, 1, 2, 3] // All 4 columns
                },
                customize: function (doc) {
                    applyPdfLetterhead(doc, 'portrait');
                    const formattedDate = '{{ \Carbon\Carbon::parse($selectedDate)->format("m/d/Y") }}';
                    const sessionType = '{{ ucfirst(str_replace("_", " ", $sessionType)) }}';
                    const titleText = `Client Attendance (${formattedDate} - ${sessionType})`;

                    // Now use titleText safely
                    doc['header'] = function(currentPage, pageCount) {
                        return {
                            text: titleText,
                            alignment: 'center',
                            margin: [0, 20, 0, 20],
                            fontSize: 12,
                            bold: true
                        };
                    };

                    const body = doc.content[1].table.body;
                    const rows = $('#clientTable tbody tr');

                    for (let i = 1; i < body.length; i++) {
                        const row = rows.eq(i - 1);
                        const presentCheckbox = row.find('input[type=checkbox]');
                        const unitsInput = row.find('input[type=number]');

                        const isPresent = presentCheckbox.prop('checked');
                        const units = unitsInput.val();

                        body[i][1].alignment = 'center';

                        body[i][2].text = isPresent ? 'Yes' : '';
                        body[i][2].alignment = 'center';

                        body[i][3].text = units || '';
                        body[i][3].alignment = 'center';
                    }

                    doc.content[1].table.widths = ['40%', '25%', '15%', '15%'];

                    body.forEach(row => {
                        row.forEach(cell => {
                            cell.border = [true, true, true, true];
                        });
                    });

                    doc.footer = function(currentPage, pageCount) {
                        const now = new Date();
                        const date = now.toLocaleDateString('en-US');
                        const time = now.toLocaleTimeString('en-US', { hour12: true });

                        return {
                            columns: [
                                {
                                    text: `Page ${currentPage} of ${pageCount}`,
                                    alignment: 'left',
                                    margin: [40, 10]
                                },
                                {
                                    text: `Generated on ${date} ${time}`,
                                    alignment: 'right',
                                    margin: [40, 10]
                                }
                            ]
                        };
                    };

                    doc.content[1].layout = {
                        hLineWidth: () => 0.5,
                        vLineWidth: () => 0.5,
                        hLineColor: () => '#aaa',
                        vLineColor: () => '#aaa'
                    };
                }

            }
        ],

        lengthMenu: [
            [10, 20, 50, -1],
            [10, 20, 50, 'All']
        ],
        pageLength: -1,
        order: [[1, 'asc']],
        drawCallback: function () {

        $('.present-checkbox').on('change', function () {
            const row = $(this).closest('tr');
            const unitInput = row.find('.unit-input');

            if (this.checked) {
                unitInput.prop('disabled', false);
            } else {
                unitInput.prop('disabled', true);
                unitInput.val(''); // clear the value
            }
        });

    }
    });


    const masterPresent = document.getElementById('masterPresent');
    const masterUnits = document.getElementById('masterUnits');
    const checkboxes = document.querySelectorAll('.present-checkbox');
    const unitInputs = document.querySelectorAll('.unit-input');
    const form = document.getElementById('noteForm');
    const datePicker = document.getElementById('service_date');

    function updateMasterUnits() {
        const values = new Set();
        checkboxes.forEach((cb, i) => {
            if (cb.checked) {
                const val = unitInputs[i].value;
                if (val !== '') values.add(val);
            }
        });
        masterUnits.value = (values.size === 1) ? [...values][0] : '';
    }

    masterUnits.addEventListener('input', () => {
        const val = masterUnits.value;

        unitInputs.forEach(input => {
            input.value = val;
        });
    });
    unitInputs.forEach((input, i) => {
        input.addEventListener('input', updateMasterUnits);
    });

    function onSessionTypeChange(select) {
        const sessionType = select.value;
        const date = datePicker.value;
        const url = new URL(window.location.href);
        if (sessionType) url.searchParams.set('session_type', sessionType);
        if (date) url.searchParams.set('date', date);
        window.location.href = url.toString();
    }

    datePicker.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('date', datePicker.value);
        url.searchParams.delete('session_type');
        window.location.href = url.toString();
    });

    $('#noteForm').on('submit', function(e) {
        let valid = true;
    const table = $('#clientTable').DataTable();

    // Remove previously appended hidden inputs
    $('#noteForm input[type=hidden][name^="attendance"]').remove();

    table.rows().every(function () {
        const $row = $(this.node());
        const checkbox = $row.find('.present-checkbox');
        const unitInput = $row.find('.unit-input');

        const namePrefix = checkbox.attr('name').replace(/\[present\]$/, '');

        const isChecked = checkbox.prop('checked');
        const unitVal = unitInput.val();

        // Validation: if present is checked, unit must be filled
        if (isChecked && unitVal === '') {
            valid = false;
            unitInput.addClass('is-invalid');
        } else {
            unitInput.removeClass('is-invalid');
        }

        // Always inject present field (checked or not)
        $('<input>').attr({
            type: 'hidden',
            name: `${namePrefix}[present]`,
            value: isChecked ? 1 : 0
        }).appendTo('#noteForm');

        // Inject units only if present is checked
        if (isChecked) {
            $('<input>').attr({
                type: 'hidden',
                name: `${namePrefix}[units]`,
                value: unitVal
            }).appendTo('#noteForm');
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Please fill in units for all selected clients.');
    }

    // Ensure session type is always submitted
    document.getElementById('session_type_input').value = document.getElementById('session_type').value;
});

</script>
@stop
