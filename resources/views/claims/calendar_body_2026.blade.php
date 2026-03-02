@php
    $firstDay = date('N', strtotime(date('Y-m-01', strtotime($currentMonth))));
    $daysInMonth = date('t', strtotime($currentMonth));
    $today = date('Y-m-d');
    $dateCounter = 1;
    $palette = ['#ff9999', '#6f42c1', '#3490dc', '#008080', '#2222ab', '#28a745', '#fd7e14', '#20c997'];
@endphp

@for ($i = 0; $i < 6; $i++)
    @if ($dateCounter <= $daysInMonth)
        <tr>
            @for ($j = 1; $j <= 7; $j++)
                @if (($i == 0 && $j < $firstDay) || $dateCounter > $daysInMonth)
                    <td></td>
                @else
                    @php
                        $currentDate =
                            date('Y-m', strtotime($currentMonth)) .
                            '-' .
                            str_pad($dateCounter, 2, '0', STR_PAD_LEFT);
                        $isToday = $currentDate == $today;

                        $activeLevels = $client
                            ->levelOfCareHistory()
                            ->where('start_date', '<=', $currentDate)
                            ->where(function ($query) use ($currentDate) {
                                $query->whereNull('end_date')->orWhere('end_date', '>=', $currentDate);
                            })
                            ->get();

                        $activeAuths = $client
                            ->authorizations()
                            ->whereHas('lineOfServices', function ($query) use ($currentDate) {
                                $query
                                    ->where('starting_date', '<=', $currentDate)
                                    ->where(function ($q) use ($currentDate) {
                                        $q->whereNull('ending_date')->orWhere('ending_date', '>=', $currentDate);
                                    });
                            })
                            ->with([
                                'lineOfServices' => function ($query) use ($currentDate) {
                                    $query
                                        ->where('starting_date', '<=', $currentDate)
                                        ->where(function ($q) use ($currentDate) {
                                            $q->whereNull('ending_date')->orWhere(
                                                'ending_date',
                                                '>=',
                                                $currentDate,
                                            );
                                        });
                                },
                            ])
                            ->get();

                        $dailyAttendance = $attendance2026[$currentDate] ?? [];
                        $activeHospitalization = null;
                        if (isset($hospitalizations)) {
                            $activeHospitalization = $hospitalizations->first(function ($hospitalization) use ($currentDate) {
                                $startDate = \Carbon\Carbon::parse($hospitalization->start_date)->toDateString();
                                $isAfterStart = $startDate <= $currentDate;
                                $endDate = $hospitalization->end_date ? \Carbon\Carbon::parse($hospitalization->end_date)->toDateString() : null;

                                return $isAfterStart && (! $endDate || $endDate >= $currentDate);
                            });
                        }
                    @endphp
                    <td style="height: 200px; position: relative;">
                        <div
                            @if ($isToday) style="font-weight: bold; background: #252525; border-radius: 50%; width: 24px; height: 24px; line-height: 24px; margin: 0 auto;" @endif>
                            {{ $dateCounter }}
                        </div>
                        <div style="position: absolute; left: 0; right: 0; padding: 0 5px;">
                            @if ($activeLevels->count())
                                <div style="margin-bottom: 3px;">
                                    @foreach ($activeLevels as $level)
                                        @php
                                            $levelCode = $level->levelOfCare?->level_of_care;
                                            $color = match ($levelCode) {
                                                'PHP' => '#3490dc',
                                                'IOP' => '#6f42c1',
                                                default => '#6c757d',
                                            };
                                        @endphp
                                        <span
                                            style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                            {{ $level->levelOfCare?->display_name ?? '-' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($activeHospitalization)
                                <div style="margin-bottom: 3px;">
                                    <span
                                        title="{{ ucfirst($activeHospitalization->type ?? 'Hospital') }}"
                                        style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: #dc3545;">
                                        Hospital / Detox
                                    </span>
                                </div>
                            @endif

                            @if ($activeAuths->count())
                                <div style="margin-bottom: 3px;">
                                    @foreach ($activeAuths as $auth)
                                        @php
                                            $authCode = $auth->levelOfCare?->level_of_care;
                                            $color = match ($authCode) {
                                                'PHP' => '#3490dc',
                                                'IOP' => '#6f42c1',
                                                default => '#6c757d',
                                            };
                                        @endphp
                                        <span
                                            style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                            {{ $auth->levelOfCare?->display_name ?? '-' }} Auth
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($activeAuths->count())
                                <div style="margin-bottom: 3px;">
                                    @foreach ($activeAuths as $auth)
                                        @foreach ($auth->lineOfServices as $los)
                                            @php
                                                $authCode = $los->authorization->levelOfCare?->level_of_care;
                                                $color = match ($authCode) {
                                                    'PHP' => '#3490dc',
                                                    'IOP' => '#6f42c1',
                                                    default => '#6c757d',
                                                };
                                            @endphp
                                            <span
                                                style="font-size: 10px; padding: 2px 5px; border-radius: 3px; white-space: nowrap; color: white; background-color: {{ $color }};">
                                                {{ ucfirst($los->type) }} - {{ ucfirst($los->status) }}
                                            </span>
                                        @endforeach
                                    @endforeach
                                </div>
                            @endif

                            @if ($dailyAttendance)
                                <div class="d-flex flex-wrap justify-content-center gap-1 mt-1">
                                    @foreach ($dailyAttendance as $code => $details)
                                        @php
                                            $bg = $palette[abs(crc32($code)) % count($palette)];
                                            $label = $details['label'] ?? null;
                                            $units = $details['units'] ?? 0;
                                        @endphp
                                        <span class="small px-1 rounded text-white" style="background-color:{{ $bg }}"
                                            title="{{ $label ? $label . ' (' . $code . ')' : $code }}">
                                            {{ $code }}: {{ $units }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if (strtotime($currentDate) <= strtotime(now()->toDateString()))
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
                                    $deniedSummary = [];
                                    $totalBilled = 0;
                                    $totalPaid = 0;

                                    foreach ($claimLines[$currentDate] ?? collect() as $line) {
                                        $code = $line->service_code;
                                        $billed = $line->billed_amount;
                                        $paid = $line->check_id ? $line->processed_amount : 0;
                                        $denied = $line->denied_amount ?? 0;

                                        $billedSummary[$code] = ($billedSummary[$code] ?? 0) + $billed;
                                        $paidSummary[$code] = ($paidSummary[$code] ?? 0) + $paid;
                                        $deniedSummary[$code] = ($deniedSummary[$code] ?? 0) + $denied;

                                        $totalBilled += $billed;
                                        $totalPaid += $paid;
                                    }
                                @endphp

                                <div class="d-flex justify-content-center gap-1 mt-1">
                                    @foreach ($billedSummary as $code => $amount)
                                        @php $bg = $colors[$code] ?? '#666'; @endphp
                                        <span class="small px-1 rounded text-white"
                                            style="background-color:{{ $bg }}">
                                            ${{ number_format($amount, 2) }}
                                        </span>
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-center gap-1">
                                    @foreach ($paidSummary as $code => $amount)
                                        @php $bg = $colors[$code] ?? '#666'; @endphp
                                        <span class="small px-1 rounded text-white"
                                            style="background-color:{{ $bg }}">
                                            ${{ number_format($amount, 2) }}
                                        </span>
                                    @endforeach
                                </div>

                                @if ($dailyAttendance)
                                    <div class="mt-1">
                                        @foreach ($dailyAttendance as $code => $details)
                                            @php
                                                $checkboxId = "claim-{$currentDate}-{$code}";
                                                $bg = $palette[abs(crc32($code)) % count($palette)];
                                                $processed = $processedClaims[$currentDate][$code] ?? false;
                                                $label = $details['label'] ?? null;
                                                $units = $details['units'] ?? 0;
                                                $price = $details['price'] ?? 0;
                                                $billableAmount = $units * $price;
                                                $billedAmount = $billedSummary[$code] ?? 0;
                                                $deniedAmount = $deniedSummary[$code] ?? 0;
                                                $isBilled = $billedAmount > 0;
                                                $isDenied = $deniedAmount > 0;
                                                $canBill = $billableAmount > 0;
                                                $labelColor = match (true) {
                                                    ! $isBilled => '#28a745',
                                                    $isDenied => '#ffc107',
                                                    default => '#6c757d',
                                                };
                                                if ($activeHospitalization) {
                                                    $labelColor = '#8b0000';
                                                }
                                            @endphp
                                            @if ($canBill && ! $processed)
                                                <div class="form-check form-check-inline"
                                                    title="{{ $label ?? $code }}">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="{{ $checkboxId }}"
                                                        name="claim_days[{{ $currentDate }}][{{ $code }}]"
                                                        value="1" style="background-color: {{ $bg }};">
                                                    <label class="form-check-label small" for="{{ $checkboxId }}"
                                                        style="color: {{ $labelColor }}; font-weight: 700;">
                                                        {{ $code }}
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    </td>
                    @php $dateCounter++; @endphp
                @endif
            @endfor
        </tr>
    @endif
@endfor
