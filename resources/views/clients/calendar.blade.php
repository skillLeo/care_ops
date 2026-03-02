@extends('adminlte::page')

@section('title', 'Calendar - ' . $client->last_name . ', ' . $client->first_name)

@section('content_header')
    <h1>Calendar for {{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</h1>
@stop

@section('content')
    <!-- Month Selector + Attendance Legend in One Line -->
    <div class="form-group d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary" onclick="changeMonth(-1)">&#8592;</button>
            <input type="month" name="month" id="month" class="form-control w-auto mx-2" value="{{ $currentMonth }}" required>
            <button type="button" class="btn btn-primary" onclick="changeMonth(1)">&#8594;</button>
        </div>

        <div class="d-flex align-items-center mt-2 mt-md-0 ml-md-4">
            <strong class="mr-2">Attendance Legend:</strong>
            <span class="badge text-white mx-1" style="background-color: #008000">Group</span>
            <span class="badge text-white mx-1" style="background-color: #008080">Peer Group</span>
            <span class="badge text-white mx-1" style="background-color: #2222ab">Peer Individual</span>
            <span class="badge text-white mx-1" style="background-color: #dc3545">Hospitalization</span>
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
            <tbody id="calendar-body">
                @php
                    $firstDay = date('N', strtotime(date('Y-m-01', strtotime($currentMonth))));
                    $daysInMonth = date('t', strtotime($currentMonth));
                    $today = date('Y-m-d');
                    $dateCounter = 1;
                    $sessionTypes = ['group', 'peer_group', 'peer_individual'];

                @endphp

                @for($i = 0; $i < 6; $i++)
                    @if($dateCounter <= $daysInMonth)
                        <tr>
                            @for($j = 1; $j <= 7; $j++)
                                @if(($i == 0 && $j < $firstDay) || $dateCounter > $daysInMonth)
                                    <td></td>
                                @else
                                    @php
                                        $currentDate = date('Y-m', strtotime($currentMonth)) . '-' . str_pad($dateCounter, 2, '0', STR_PAD_LEFT);
                                        $isToday = $currentDate == $today;

                                        // Get all active level of care records for this date
                                        $activeLevels = $client->levelOfCareHistory()
                                            ->where('start_date', '<=', $currentDate)
                                            ->where(function($query) use ($currentDate) {
                                                $query->whereNull('end_date')
                                                    ->orWhere('end_date', '>=', $currentDate);
                                            })
                                            ->get();

                                        // Get all active authorizations for this date
                                        $activeAuths = $client->authorizations()
                                            ->whereHas('lineOfServices', function($query) use ($currentDate) {
                                                $query->where('starting_date', '<=', $currentDate)
                                                    ->where(function($q) use ($currentDate) {
                                                        $q->whereNull('ending_date')
                                                            ->orWhere('ending_date', '>=', $currentDate);
                                                    });
                                            })
                                            ->with(['lineOfServices' => function($query) use ($currentDate) {
                                                $query->where('starting_date', '<=', $currentDate)
                                                    ->where(function($q) use ($currentDate) {
                                                        $q->whereNull('ending_date')
                                                            ->orWhere('ending_date', '>=', $currentDate);
                                                    });
                                            }])
                                            ->get();

                                        $activeHospitalization = $hospitalizations->first(function ($hospitalization) use ($currentDate) {
                                            $startDate = \Carbon\Carbon::parse($hospitalization->start_date)->toDateString();
                                            $endDate = $hospitalization->end_date
                                                ? \Carbon\Carbon::parse($hospitalization->end_date)->toDateString()
                                                : null;

                                            return $startDate <= $currentDate && (! $endDate || $endDate >= $currentDate);
                                        });
                                    @endphp
                                    <td style="position: relative;">
                                        <div @if($isToday) style="font-weight: bold; background: #252525; border-radius: 50%; width: 24px; height: 24px; line-height: 24px; margin: 0 auto;" @endif>
                                            {{ $dateCounter }}
                                        </div>
                                        <div style="position: absolute; left: 0; right: 0; padding: 0 5px;">
                                            <!-- Level of Care -->
                                            @if($activeLevels->count())
                                                <div style="margin-bottom: 3px;">
                                                    @foreach($activeLevels as $level)
                                                        @php
                                                            $levelCode = $level->levelOfCare?->level_of_care;
                                                            $color = match($levelCode) {
                                                                'PHP' => '#3490dc',
                                                                'IOP' => '#6f42c1',
                                                                default => '#6c757d'
                                                            };
                                                        @endphp
                                                        <span style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                                            {{ $level->levelOfCare?->display_name ?? '-' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Hospitalizations -->
                                            @if($activeHospitalization)
                                                <div style="margin-bottom: 3px;">
                                                    <span style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: #dc3545;">
                                                        Hospitalization
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Authorizations -->
                                            @if($activeAuths->count())
                                                <div style="margin-bottom: 3px;">
                                                    @foreach($activeAuths as $auth)
                                                        @php
                                                            $authCode = $auth->levelOfCare?->level_of_care;
                                                            $color = match($authCode) {
                                                                'PHP' => '#3490dc',
                                                                'IOP' => '#6f42c1',
                                                                default => '#6c757d'
                                                            };
                                                        @endphp
                                                        <span style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                                            {{ $auth->levelOfCare?->display_name ?? '-' }} Auth
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Line of Services -->
                                            @if($activeAuths->count())
                                                <div style="margin-bottom: 3px;">
                                                    @foreach($activeAuths as $auth)
                                                        @foreach($auth->lineOfServices as $los)
                                                            @php
                                                                $authCode = $los->authorization->levelOfCare?->level_of_care;
                                                                $color = match($authCode) {
                                                                    'PHP' => '#3490dc',
                                                                    'IOP' => '#6f42c1',
                                                                    default => '#6c757d'
                                                                };
                                                            @endphp
                                                            <span style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                                                {{ ucfirst($los->type) }} - {{ ucfirst($los->status) }}
                                                            </span>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Attendance Units -->
                                            <div class="d-flex justify-content-center gap-1">
                                                <span style="font-size: 12px; line-height: 1; display: inline-block; margin: 0; padding: 0;">Attn:</span>
                                                @foreach ($sessionTypes as $type)
                                                    @php
                                                        $att = $attendances[$type][$currentDate] ?? null;
                                                        $units = $att && $att->attended ? $att->units : '0';
                                                        $bgColor = $att && $att->attended
                                                            ? match($type) {
                                                                'group' => '#008000',
                                                                'peer_group' => '#008080',
                                                                'peer_individual' => '#2222ab',
                                                                default => 'bg-secondary'
                                                            }
                                                            : 'bg-dark';
                                                    @endphp
                                                    <span class="small px-2 rounded text-white" style="background-color:{{ $bgColor }}">{{ $units }}</span>
                                                @endforeach
                                            </div>
                                            <div class="d-flex justify-content-center gap-1">
                                                <span style="font-size: 12px; line-height: 1; display: inline-block; margin: 0; padding: 0;">Note:</span>
                                                @foreach ($sessionTypes as $type)
                                                    @php
                                                        $nt = $notes[$type][$currentDate] ?? null;
                                                        $units = $nt ? $nt->units : '0';
                                                        $bgColor = $nt
                                                            ? match($type) {
                                                                'group' => '#008000',
                                                                'peer_group' => '#008080',
                                                                'peer_individual' => '#2222ab',
                                                                default => 'bg-secondary'
                                                            }
                                                            : 'bg-dark';
                                                    @endphp
                                                    <span class="small px-2 rounded text-white" style="background-color:{{ $bgColor }}">{{ $units }}</span>
                                                @endforeach
                                            </div>
                                            @if (isset($claimLines[$currentDate]))
                                                @php
                                                    $colors = [
                                                        'H0001' => '#ff9999',
                                                        'H0015' => '#6f42c1',
                                                        'H2036-22' => '#3490dc',
                                                        'H2036' => '#3490dc',
                                                        'H0024' => '#008080',
                                                        'H0038' => '#2222ab',
                                                    ];

                                                    $billedSummary = [];
                                                    $paidSummary = [];
                                                    $totalBilled = 0;
                                                    $totalPaid = 0;
                                                    $totalDenied = 0;

                                                    foreach ($claimLines[$currentDate] as $line) {
                                                        $code = $line->service_code;
                                                        $billed = $line->billed_amount;
                                                        $paid = $line->check_id ? $line->processed_amount : 0;
                                                        $denied = $line->check_id ? $line->denied_amount : 0;

                                                        $billedSummary[$code] = ($billedSummary[$code] ?? 0) + $billed;
                                                        $paidSummary[$code] = ($paidSummary[$code] ?? 0) + $paid;

                                                        $totalBilled += $billed;
                                                        $totalPaid += $paid;
                                                        $totalDenied += $denied;
                                                    }
                                                @endphp

                                                <div class="d-flex justify-content-center gap-1 mt-1">
                                                    @foreach($billedSummary as $code => $amount)
                                                        @php $bg = $colors[$code] ?? '#666'; @endphp
                                                        <span class="small px-1 rounded text-white" style="background-color:{{ $bg }}">
                                                            ${{ number_format($amount, 2) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                                <div class="d-flex justify-content-center gap-1">
                                                    @foreach($paidSummary as $code => $amount)
                                                        @php $bg = $colors[$code] ?? '#666'; @endphp
                                                        <span class="small px-1 rounded text-white" style="background-color:{{ $bg }}">
                                                            ${{ number_format($amount, 2) }}
                                                        </span>
                                                    @endforeach
                                                </div>

                                                <!-- Totals with no background -->
                                                <div class="text-dark small mt-1" style="text-align: center;">
                                                    <div><strong>Total Billed:</strong> ${{ number_format($totalBilled, 2) }}</div>
                                                    <div><strong>Total Paid:</strong> ${{ number_format($totalPaid, 2) }}</div>
                                                    <div><strong>Total Denied:</strong> ${{ number_format($totalDenied, 2) }}</div>
                                                </div>
                                            @endif

                                        </div>
                                    </td>
                                    @php $dateCounter++; @endphp
                                @endif
                            @endfor
                        </tr>
                    @endif
                @endfor
            </tbody>
        </table>
    </div>



    <!-- Level of Care Legend -->
    <div class="mt-4">
        <h5>Level of Care History:</h5>
        @foreach($levelOfCares as $loc)
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
        height: 210px;
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
        window.location.href = `{{ route('clients.calendar', $client->id) }}?month=${newMonth}`;
    }

    document.getElementById('month').addEventListener('change', function() {
        window.location.href = `{{ route('clients.calendar', $client->id) }}?month=${this.value}`;
    });
</script>
@stop
