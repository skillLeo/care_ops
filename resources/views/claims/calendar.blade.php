@extends('adminlte::page')

@section('title', 'Claims Calendar - ' . $client->last_name . ', ' . $client->first_name)

@section('content_header')
    <h1>Claim Submission Calendar for {{ $client->last_name }}, {{ $client->first_name }}</h1>
@stop

@section('content')
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <div class="mr-auto">
                <h5 class="mb-1">Automation Shortcuts</h5>
                <p class="mb-0 text-muted">Send client details to the local Flask service for automation.</p>
            </div>
            <button type="button" class="btn btn-outline-primary" onclick="sendEligibilityRequest()">
                Submit Eligibility
            </button>
            <button type="button" class="btn btn-outline-success" onclick="sendClaimRequest()">
                Submit Claim
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('claims.store') }}">
        @csrf

        <div class="card">
            <div class="card-header"><strong>Claim Details</strong></div>
            <div class="card-body row">
                <input type="hidden" name="client_id" class="form-control" value="{{ $client->id }}">
                <div class="form-group col-md-3">
                    <label>Client</label>
                    <input type="text" name="client_name" class="form-control"
                        value="{{ $client->last_name }}, {{ $client->first_name }}" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label>Carelon ID</label>
                    @can('client.view_carelon_id')
                        <input type="text" name="client_carelon_id" class="form-control" value="{{ $client->carelon_id }}"
                            readonly>
                    @else
                        <input type="text" name="client_carelon_id" class="form-control" value="Restricted" readonly>
                    @endcan
                </div>
                <div class="form-group col-md-3">
                    <label>Client DOB:</label>
                    <input type="text" name="client_carelon_id" class="form-control"
                        value="{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label>Client MRN:</label>
                    <input type="text" name="client_carelon_id" class="form-control" value="{{ $client->mrn }}"
                        readonly>
                </div>
                <div class="form-group col-md-3">
                    <label>Submission Date</label>
                    <input type="date" name="submission_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Claim Number</label>
                    <input type="text" name="claim_number" class="form-control" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Carelon Claim Number</label>
                    <input type="text" name="carelon_claim_number" class="form-control">
                </div>
                <div class="form-group col-md-3">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="1"></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Line of Services</strong>
                <button type="button" class="btn btn-sm btn-success" onclick="addLine()">+ Add Line</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="lines-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Service Date</th>
                            <th>Service Code</th>
                            <th>Diagnosis Code</th>
                            <th>Units</th>
                            <th>Billed ($)</th>
                            <th>Processed ($)</th>
                            <th>Denied ($)</th>
                            <th>Check</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Totals:</strong></td>
                            <td><input type="text" id="total-billed" class="form-control form-control-sm" readonly></td>
                            <td><input type="text" id="total-processed" class="form-control form-control-sm" readonly>
                            </td>
                            <td><input type="text" id="total-denied" class="form-control form-control-sm" readonly></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Submit Claim
            </button>
            <a href="{{ route('claims.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
    <hr>
    <!-- Month Selector + Attendance Legend in One Line -->
    <div class="form-group d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary" onclick="changeMonth(-1)">&#8592;</button>
            <input type="month" name="month" id="month" class="form-control w-auto mx-2"
                value="{{ $currentMonth }}" required>
            <button type="button" class="btn btn-primary" onclick="changeMonth(1)">&#8594;</button>
        </div>

        <div class="d-flex align-items-center mt-2 mt-md-0 ml-md-4">
            <strong class="mr-2">Attendance Legend:</strong>
            <span class="badge text-white mx-1" style="background-color: #008000">Group</span>
            <span class="badge text-white mx-1" style="background-color: #008080">Peer Group</span>
            <span class="badge text-white mx-1" style="background-color: #2222ab">Peer Individual</span>
        </div>

        <div class="d-flex align-items-center mt-2 mt-md-0 ml-md-4">
            <strong class="mr-2">Billing Legend:</strong>
            <span class="badge text-white mx-1" style="background-color: #ff9999">H0001</span>
            <span class="badge text-white mx-1" style="background-color: #6f42c1">H0015</span>
            <span class="badge text-white mx-1" style="background-color: #3490dc">H2036-22</span>
            <span class="badge text-white mx-1" style="background-color: #3490dc">H2036</span>
            <span class="badge text-white mx-1" style="background-color: #008080">H0024</span>
            <span class="badge text-white mx-1" style="background-color: #2222ab">H0038</span>
        </div>

    </div>


    <!-- Calendar -->
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
            <tbody id="calendar-body" data-url="{{ route('clients.calendar.body', [$client->id]) }}">
                @include('claims.calendar_body', [
                    'claimLines' => $claimLines,
                    'attendances' => $attendances,
                    'currentMonth' => $currentMonth,
                    'levelOfCares' => $client->levelOfCareHistory,
                    'checks' => $checks,
                ])
            </tbody>


        </table>
    </div>



    <!-- Level of Care Legend -->
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
        .btn-outline-secondary {
            font-size: 20px;
            padding: 5px 15px;
            border-radius: 5px;
        }

        #calendar-body td {
            height: 150px;
            vertical-align: top;
            font-size: 12px;
        }
    </style>
@stop

@section('js')

    <script>
        const flaskBaseUrl = 'http://127.0.0.1:5000';
        const clientProfile = {
            last_name: @json($client->last_name),
            first_name: @json($client->first_name),
            carelon_id: @json(auth()->user()->can('client.view_carelon_id') ? $client->carelon_id : null),
            dob: '{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}',
            mrn: @json($client->mrn),
        };

        function formatDateToMMDDYYYY(value) {
            if (!value) return '';
            const [year, month, day] = value.split('-');
            return `${month}/${day}/${year}`;
        }

        async function sendFlaskRequest(endpoint, payload, label) {
            try {
                const response = await fetch(`${flaskBaseUrl}${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    const responseText = await response.text();
                    throw new Error(`${label} request failed with status ${response.status}: ${responseText}`);
                }

                alert(`${label} request sent successfully.`);
            } catch (error) {
                console.error(error);
                alert(`Unable to send ${label} request. Check console for details.`);
            }
        }

        function sendEligibilityRequest() {
            const payload = {
                last_name: clientProfile.last_name,
                first_name: clientProfile.first_name,
                carelon_id: clientProfile.carelon_id,
                dob: clientProfile.dob,
            };

            sendFlaskRequest('/submit-eligibility', payload, 'Eligibility');
        }

        function sendClaimRequest() {
            const services = Array.from(document.querySelectorAll('#lines-table tbody tr')).map((row) => {
                const serviceDate = row.querySelector('input[name$="[service_date]"]')?.value || '';
                const billedAmount = row.querySelector('input[name$="[billed_amount]"]')?.value || '';
                return {
                    service_date: formatDateToMMDDYYYY(serviceDate),
                    service_code: row.querySelector('input[name$="[service_code]"]')?.value || '',
                    diagnosis_code: row.querySelector('input[name$="[diagnosis_code]"]')?.value || '',
                    amount: billedAmount ? parseFloat(billedAmount) : '',
                    qty: row.querySelector('input[name$="[units]"]')?.value || '',
                };
            }).filter(service => service.service_date || service.service_code || service.diagnosis_code || service.amount || service.qty);

            const payload = {
                last_name: clientProfile.last_name,
                first_name: clientProfile.first_name,
                carelon_id: clientProfile.carelon_id,
                dob: clientProfile.dob,
                mrn: clientProfile.mrn,
                services,
            };

            sendFlaskRequest('/submit-claim', payload, 'Claim');
        }

        let lineIndex = 0;
        const attendance = @json($attendances); // Must be accessible globally
        console.log(attendance);
        function getUnits(code, date) {
            const type = BILLING_META[code]?.unitType;
            if (type === 'fixed') return 1;
            if (attendance[type] && attendance[type][date] && attendance[type][date].attended) {
                return attendance[type][date].units;
            }
            return 0;
        }

        const authData = [];
        const locData = [];

        @foreach ($client->authorizations as $auth)
            @foreach ($auth->lineOfServices as $los)
                authData.push({
                    auth_start: "{{ $auth->starting_date }}",
                    los_start: "{{ $los->starting_date }}",
                    los_end: "{{ $los->ending_date ?? '' }}",
                    diagnosis_code: "{{ $los->diagnosis_code }}",
                    loc: "{{ $auth->levelOfCare?->level_of_care ?? '' }}",
                    service_date: "{{ $los->starting_date }}",
                });
            @endforeach
        @endforeach

        @foreach ($client->levelOfCareHistory as $loc)
            locData.push({
                loc: "{{ $loc->levelOfCare?->level_of_care ?? '' }}",
                start: "{{ $loc->start_date }}",
                end: "{{ $loc->end_date ?? '' }}",
            });
        @endforeach

        function resolveDiagnosisCodeFinal(date) {
            // STEP 1: Get level of care(s) for that date
            const activeLOCs = locData.filter(loc => {
                return loc.start <= date && (!loc.end || loc.end >= date);
            });

            if (activeLOCs.length !== 1) return ''; // rule: multiple or none → leave blank

            const level = activeLOCs[0].loc;

            // STEP 2: Get all auths active on this day for that level of care
            const activeAuths = authData.filter(item => {
                return item.loc === level &&
                    item.auth_start <= date &&
                    item.los_start <= date &&
                    (!item.los_end || item.los_end >= date);
            });

            const uniqueAuthStarts = [...new Set(activeAuths.map(a => a.auth_start))];

            if (uniqueAuthStarts.length !== 1) return ''; // rule: multiple auths for same LOC → blank

            // STEP 3: Get LOS entries for that auth + day
            const authStart = uniqueAuthStarts[0];
            const losForDay = activeAuths.filter(a =>
                a.auth_start === authStart &&
                a.los_start <= date &&
                (!a.los_end || a.los_end >= date)
            );

            if (losForDay.length === 0) return '';

            const uniqueDx = [...new Set(losForDay.map(a => a.diagnosis_code).filter(x => x))];

            if (uniqueDx.length === 1) return uniqueDx[0];

            return ''; // multiple diagnosis codes or mismatch
        }




        const BILLING_META = {
            'H0001': {
                label: 'Assessment',
                rate: 220.65,
                unitType: 'fixed'
            },
            'H2036-22': {
                label: 'Group PHP-22',
                rate: 326.27,
                unitType: 'group'
            },
            'H2036': {
                label: 'Group PHP',
                rate: 201.99,
                unitType: 'group'
            },
            'H0015': {
                label: 'Group IOP',
                rate: 194.23,
                unitType: 'group'
            },
            'H0024': {
                label: 'Peer Group',
                rate: 5.22,
                unitType: 'peer_group'
            },
            'H0038': {
                label: 'Peer Ind',
                rate: 18.77,
                unitType: 'peer_individual'
            },
        };

        function generateRowId(date, code) {
            return `row-${date.replaceAll('-', '')}-${code}`;
        }

        function generateCheckboxId(date, code) {
            return `claim-${date}-${code}`;
        }

        let currentMonth = document.getElementById('month').value;

        // Store state in memory (or consider sessionStorage if you want)
        const checkedDays = {};
        const claimLines = [];

        document.querySelectorAll('input[type="checkbox"][name^="claim_days"]').forEach(cb => {
            const date = cb.name.match(/claim_days\[(.*?)\]/)[1];
            const code = cb.name.match(/\[(.*?)\]$/)[1];
            if (cb.checked) {
                if (!checkedDays[date]) checkedDays[date] = [];
                checkedDays[date].push(code);
            }
        });

        function reloadCalendar(month) {
            const tbody = document.getElementById('calendar-body');
            const url = tbody.getAttribute('data-url');
            const params = new URLSearchParams({
                month
            });

            fetch(`${url}?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    tbody.innerHTML = html;

                    // Restore checkboxes and rows
                    Object.entries(checkedDays).forEach(([date, codes]) => {
                        codes.forEach(code => {
                            const checkboxId = `claim-${date}-${code}`;
                            const cb = document.getElementById(checkboxId);
                            if (cb) {
                                cb.checked = true;
                                addLineFromCheckbox(date, code);
                            }
                        });
                    });

                    bindCheckboxEvents();
                });
        }

        function changeMonth(offset) {
            let [year, month] = currentMonth.split('-').map(Number);
            month += offset;
            if (month === 0) {
                month = 12;
                year -= 1;
            } else if (month === 13) {
                month = 1;
                year += 1;
            }
            currentMonth = `${year}-${month.toString().padStart(2, '0')}`;
            document.getElementById('month').value = currentMonth;
            reloadCalendar(currentMonth);
        }

        document.getElementById('month').addEventListener('change', function() {
            currentMonth = this.value;
            reloadCalendar(currentMonth);
        });

        function bindCheckboxEvents() {
            document.querySelectorAll('input[type="checkbox"][name^="claim_days"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    const parts = this.name.match(/claim_days\[(.*?)\]\[(.*?)\]/);
                    if (!parts) return;
                    const date = parts[1];
                    const code = parts[2];

                    if (this.checked) {
                        addLineFromCheckbox(date, code);
                    } else {
                        removeLineFromCheckbox(date, code);
                    }
                });
            });
        }

        function addLineFromCheckbox(date, code) {
            const rate = BILLING_META[code]?.rate || 0;
            const units = getUnits(code, date);
            if (units <= 0) return;

            const billed = (rate * units).toFixed(2);
            const rowId = generateRowId(date, code);
            const diagnosis = resolveDiagnosisCodeFinal(date);

            if (document.getElementById(rowId)) return;

            const tbody = document.querySelector('#lines-table tbody');
            const tr = document.createElement('tr');
            tr.id = rowId;

            const checks = @json($checks);

            tr.innerHTML = `
        <td class="serial-number"></td>
        <td><input type="date" name="lines[${lineIndex}][service_date]" class="form-control form-control-sm" value="${date}" required></td>
        <td><input type="text" name="lines[${lineIndex}][service_code]" class="form-control form-control-sm" value="${code}" placeholder="Error" required></td>
        <td><input type="text" name="lines[${lineIndex}][diagnosis_code]" class="form-control form-control-sm" value="${diagnosis}" placeholder="Error" required></td>
        <td><input type="number" name="lines[${lineIndex}][units]" class="form-control form-control-sm" value="${units}" required></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][billed_amount]" class="form-control form-control-sm" value="${billed}" required></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][processed_amount]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.01" name="lines[${lineIndex}][denied_amount]" class="form-control form-control-sm"></td>
        <td>
            <select name="lines[${lineIndex}][check_id]" class="form-control form-control-sm">
                <option value="">--</option>
                ${checks.map(c => `<option value="${c.id}">${c.check_number} (${c.payment_date})</option>`).join('')}
            </select>
        </td>
        <td><input type="text" name="lines[${lineIndex}][remarks]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); document.getElementById('${generateCheckboxId(date, code)}').checked = false; updateTotals(); updateSerialNumbers();">×</button></td>
    `;

            tbody.appendChild(tr);
            lineIndex++; // important to increment so future rows don't conflict

            updateTotals();
            updateSerialNumbers();
        }

        function removeLineFromCheckbox(date, code) {
            const rowId = generateRowId(date, code);
            const row = document.getElementById(rowId);
            if (row) {
                row.remove();
                updateTotals();
                updateSerialNumbers();
            }
        }

        function addLine() {
            const checks = @json($checks);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="serial-number"></td>
                <td><input type="date" name="lines[${lineIndex}][service_date]" class="form-control form-control-sm" required></td>
                <td><input type="text" name="lines[${lineIndex}][service_code]" class="form-control form-control-sm" required></td>
                <td><input type="text" name="lines[${lineIndex}][diagnosis_code]" class="form-control form-control-sm"></td>
                <td><input type="number" name="lines[${lineIndex}][units]" class="form-control form-control-sm"></td>
                <td><input type="number" step="0.01" name="lines[${lineIndex}][billed_amount]" class="form-control form-control-sm" required></td>
                <td><input type="number" step="0.01" name="lines[${lineIndex}][processed_amount]" class="form-control form-control-sm"></td>
                <td><input type="number" step="0.01" name="lines[${lineIndex}][denied_amount]" class="form-control form-control-sm"></td>
                <td>
                    <select name="lines[${lineIndex}][check_id]" class="form-control form-control-sm">
                        <option value="">--</option>
                        ${checks.map(c => `<option value="${c.id}">${c.check_number} (${c.payment_date})</option>`).join('')}
                    </select>
                </td>
                <td><input type="text" name="lines[${lineIndex}][remarks]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">×</button></td>
            `;
            document.getElementById('lines-body').appendChild(row);
            lineIndex++;

            updateTotals();
            updateSerialNumbers();

        }


        window.addEventListener('DOMContentLoaded', bindCheckboxEvents);

        function updateTotals() {
            let billed = 0,
                processed = 0,
                denied = 0;

            document.querySelectorAll('input[name^="lines"][name$="[billed_amount]"]').forEach(input => {
                billed += parseFloat(input.value) || 0;
            });
            document.querySelectorAll('input[name^="lines"][name$="[processed_amount]"]').forEach(input => {
                processed += parseFloat(input.value) || 0;
            });
            document.querySelectorAll('input[name^="lines"][name$="[denied_amount]"]').forEach(input => {
                denied += parseFloat(input.value) || 0;
            });

            document.getElementById('total-billed').value = billed.toFixed(2);
            document.getElementById('total-processed').value = processed.toFixed(2);
            document.getElementById('total-denied').value = denied.toFixed(2);
        }

        document.addEventListener('input', function(e) {
            if (e.target.name?.includes('[billed_amount]') ||
                e.target.name?.includes('[processed_amount]') ||
                e.target.name?.includes('[denied_amount]')) {
                updateTotals();
                updateSerialNumbers();
            }
        });

        function updateSerialNumbers() {
    const rows = document.querySelectorAll('#lines-body tr');
    rows.forEach((tr, index) => {
        const serialCell = tr.querySelector('.serial-number');
        if (serialCell) serialCell.textContent = index + 1;
    });
}

    </script>
@stop
