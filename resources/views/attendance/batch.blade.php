@extends('adminlte::page')

@section('title', 'Batch Attendance')

@section('content_header')
    <h1>Batch Attendance</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('attendances.batchStore') }}" id="attendanceForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-2">
                <label for="service_date">Service Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control"
                    required max="{{ \Carbon\Carbon::today()->toDateString() }}"
                    value="{{ old('service_date', $selectedDate ?? \Carbon\Carbon::today()->toDateString()) }}">
            </div>

            <div class="col-md-4 mb-2">
                <label for="session_type">Session Type</label>
                <select name="session_type" id="session_type" class="form-control">
                    @foreach (['group', 'peer_individual', 'peer_group'] as $type)
                        <option value="{{ $type }}" {{ ($sessionType ?? 'group') === $type ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label for="counselor_id">Counselor</label>
                <select id="counselor_id" class="form-control">
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
                <select id="peer_id" class="form-control">
                    <option value="all">All</option>
                    @foreach ($peers as $peer)
                        <option value="{{ $peer->id }}" {{ $peerId == $peer->id ? 'selected' : '' }}>{{ $peer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label for="house_id">House</label>
                <select id="house_id" class="form-control">
                    <option value="all">All</option>
                    <option value="unhoused" {{ $houseId == 'unhoused' ? 'selected' : '' }}>Unhoused</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->id }}" {{ $houseId == $house->id ? 'selected' : '' }}>{{ $house->house_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label for="apartment_id">Apartment</label>
                <select id="apartment_id" class="form-control" {{ $houseId === 'all' || $houseId === 'unhoused' ? 'disabled' : '' }}>
                    <option value="all">All</option>
                    @foreach ($apartments as $apt)
                        <option value="{{ $apt->id }}" {{ $apartmentId == $apt->id ? 'selected' : '' }}>{{ $apt->apartment_number }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label for="level_of_care">Client Level of Care</label>
                <select id="level_of_care" class="form-control">
                    <option value="all">All</option>
                    @foreach ($levels as $loc)
                        <option value="{{ $loc->level_of_care }}" {{ $levelOfCareFilter == $loc->level_of_care ? 'selected' : '' }}>{{ $loc->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label for="guest">Guest</label>
                <select id="guest" class="form-control">
                    <option value="all">All</option>
                    <option value="1" {{ $guestFilter == '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $guestFilter == '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <div class="col-md-12 text-right">
                <button type="button" id="filterBtn" class="btn btn-primary">Filter</button>
            </div>
        </div>


        <input type="hidden" name="session_type" id="session_type_input" value="{{ $sessionType }}">

        <table class="table table-bordered" id="clientTable">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th class="text-center">LOC</th>
                    <th class="text-center">Counselor</th>
                    <th class="text-center">Peer</th>
                    <th class="text-center">House</th>
                    <th class="text-center">Appt</th>
                    <th class="text-center">Guest</th>
                    <th class="text-center">
                        <input type="checkbox" id="masterPresent">
                        <div>Present</div>
                    </th>
                    <th class="text-center">
                        <input type="number" id="masterUnits" class="form-control form-control-sm w-50 text-center mx-auto"
                            placeholder="Units">
                        <div>Units</div>
                    </th>
                    <th class="text-center">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    @php
                        $att = $attendanceData[$client->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                        <td class="text-center">{{ $client->level_of_care }}</td>
                        <td class="text-center">{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name }}</td>
                        <td class="text-center">{{ optional($client->peer)->short_name ?? optional($client->peer)->name }}</td>
                        <td class="text-center">{{ optional(optional($client->apartment)->house)->house_name }}</td>
                        <td class="text-center">{{ optional($client->apartment)->apartment_number }}</td>
                        <td class="text-center">{{ $client->guest ? 'Yes' : 'No' }}</td>
                        <td class="text-center">
                            <input type="checkbox" id="present-{{ $client->id }}" class="present-checkbox"
                                name="attendance[{{ $client->id }}][present]" {{ $att ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="number" name="attendance[{{ $client->id }}][units]"
                                id="units-{{ $client->id }}"
                                class="form-control form-control-sm w-50 text-center mx-auto unit-input"
                                value="{{ $att->units ?? '' }}" {{ $att ? '' : 'disabled' }}>
                        </td>
                        <td class="text-center">
                            <input type="text" name="attendance[{{ $client->id }}][remarks]"
                                id="remarks-{{ $client->id }}"
                                class="form-control form-control-sm w-50 text-center mx-auto remarks-input"
                                value="{{ $att->remarks ?? '' }}" {{ $att ? '' : 'disabled' }}>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <label for="attendance_batch_attachments" class="form-label">Attachments</label>
            <input type="file" name="attachments[]" id="attendance_batch_attachments" class="form-control" multiple>
        </div>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>
@stop


@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <style>
        .dataTables_length select {
            background-color: #343a40;
            /* dark gray */
            color: #ffffff;
            /* white text */
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
            buttons: [{
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                filename: 'Attendance {{ \Carbon\Carbon::parse($selectedDate)->format('m-d-Y') }} {{ ucfirst(str_replace('_', ' ', $sessionType)) }}',
                pageSize: 'LETTER',
                className: 'btn btn-danger',
                orientation: 'landscape',

                // title: `Client Attendance ({{ \Carbon\Carbon::parse($selectedDate)->format('m/d/Y') }} - {{ ucfirst(str_replace('_', ' ', $sessionType)) }})`,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
                customize: function(doc) {
                    applyPdfLetterhead(doc, 'landscape');
                    const formattedDate =
                    '{{ \Carbon\Carbon::parse($selectedDate)->format('m/d/Y') }}';
                    const sessionType = '{{ ucfirst(str_replace('_', ' ', $sessionType)) }}';
                    const titleText = `Client Attendance (${formattedDate} - ${sessionType})`;

                    // Now use titleText safely
                    doc['header'] = function(currentPage, pageCount) {
                        return {
                            text: titleText,
                            alignment: 'center',
                            margin: [0, 5, 0, 5],
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
                        body[i][2].alignment = 'center';
                        body[i][3].alignment = 'center';
                        body[i][4].alignment = 'center';
                        body[i][5].alignment = 'center';
                        body[i][6].alignment = 'center';

                        body[i][7].text = isPresent ? 'Yes' : '';
                        body[i][7].alignment = 'center';

                        body[i][8].text = units || '';
                        body[i][8].alignment = 'center';
                    }

                    doc.content[1].table.widths = [ '20%',  // Client Name
                                                    '4%',  // LOC
                                                    '11%',  // Counselor
                                                    '10%',  // Peer
                                                    '10%',  // House
                                                    '7.5%',  // Apartment
                                                    '5%',  // Guest
                                                    '7.5%',  // Present
                                                    '5%',   // Units
                                                    '20%'];  // Remarks

                    body.forEach(row => {
                        row.forEach(cell => {
                            cell.border = [true, true, true, true];
                        });
                    });

                    doc.footer = function(currentPage, pageCount) {
                        const now = new Date();
                        const date = now.toLocaleDateString('en-US');
                        const time = now.toLocaleTimeString('en-US', {
                            hour12: true
                        });

                        return {
                            columns: [{
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

            }],

            lengthMenu: [
                [10, 20, 50, -1],
                [10, 20, 50, 'All']
            ],
            pageLength: -1,
            order: [
                [1, 'asc']
            ],
            drawCallback: function() {

                $('.present-checkbox').on('change', function() {
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
        const form = document.getElementById('attendanceForm');
        const datePicker = document.getElementById('service_date');

        function updateMasterCheckbox() {
            const total = checkboxes.length;
            const checked = [...checkboxes].filter(cb => cb.checked).length;
            masterPresent.indeterminate = (checked > 0 && checked < total);
            masterPresent.checked = (checked === total);
        }

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

        masterPresent.addEventListener('change', () => {
            const isChecked = masterPresent.checked;
            checkboxes.forEach((cb, i) => {
                cb.checked = isChecked;
                unitInputs[i].disabled = !isChecked;
            });
            updateMasterUnits();
        });

        masterUnits.addEventListener('input', () => {
            const val = masterUnits.value;
            checkboxes.forEach((cb, i) => {
                if (cb.checked) unitInputs[i].value = val;
            });
        });

        checkboxes.forEach((cb, i) => {
            cb.addEventListener('change', () => {
                unitInputs[i].disabled = !cb.checked;
                if (!cb.checked) unitInputs[i].value = '';
                updateMasterCheckbox();
                updateMasterUnits();
            });
        });

        unitInputs.forEach((input, i) => {
            input.addEventListener('input', updateMasterUnits);
        });

        function applyFilters() {
            const url = new URL(window.location.href);
            url.searchParams.set('date', datePicker.value);
            url.searchParams.set('session_type', document.getElementById('session_type').value);
            url.searchParams.set('counselor_id', document.getElementById('counselor_id').value);
            url.searchParams.set('peer_id', document.getElementById('peer_id').value);
            url.searchParams.set('house_id', document.getElementById('house_id').value);
            url.searchParams.set('apartment_id', document.getElementById('apartment_id').value);
            url.searchParams.set('level_of_care', document.getElementById('level_of_care').value);
            url.searchParams.set('guest', document.getElementById('guest').value);
            window.location.href = url.toString();
        }

        document.getElementById('filterBtn').addEventListener('click', applyFilters);

        document.getElementById('house_id').addEventListener('change', function () {
            const houseId = this.value;
            const aptSelect = document.getElementById('apartment_id');
            aptSelect.innerHTML = '<option value="all">All</option>';
            aptSelect.disabled = (houseId === 'all' || houseId === 'unhoused');
            if (houseId !== 'all' && houseId !== 'unhoused') {
                fetch(`/houses/${houseId}/apartments`)
                    .then(resp => resp.json())
                    .then(data => {
                        data.forEach(ap => {
                            const opt = document.createElement('option');
                            opt.value = ap.id;
                            opt.textContent = ap.apartment_number;
                            aptSelect.appendChild(opt);
                        });
                    });
            }
        });

        $('#attendanceForm').on('submit', function(e)
        {
            let valid = true;
            const table = $('#clientTable').DataTable();

            // Remove previously appended hidden inputs
            $('#attendanceForm input[type=hidden][name^="attendance"]').remove();

            table.rows({search: 'none'}).every(function() {
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
                }).appendTo('#attendanceForm');

                // Inject units only if present is checked
                if (isChecked) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `${namePrefix}[units]`,
                        value: unitVal
                    }).appendTo('#attendanceForm');
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
