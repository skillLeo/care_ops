{{-- <tbody id="calendar-body"> --}}
    @php
        $firstDay = date('N', strtotime(date('Y-m-01', strtotime($currentMonth))));
        $daysInMonth = date('t', strtotime($currentMonth));
        $today = date('Y-m-d');
        $dateCounter = 1;
        $sessionTypes = ['group', 'peer_group', 'peer_individual'];

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

                            // Get all active level of care records for this date
                            $activeLevels = $client
                                ->levelOfCareHistory()
                                ->where('start_date', '<=', $currentDate)
                                ->where(function ($query) use ($currentDate) {
                                    $query->whereNull('end_date')->orWhere('end_date', '>=', $currentDate);
                                })
                                ->get();

                            // Get all active authorizations for this date
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
                        @endphp
                        <td style="height: 200px; position: relative;">
                            <div
                                @if ($isToday) style="font-weight: bold; background: #252525; border-radius: 50%; width: 24px; height: 24px; line-height: 24px; margin: 0 auto;" @endif>
                                {{ $dateCounter }}
                            </div>
                            <div style="position: absolute; left: 0; right: 0; padding: 0 5px;">
                                <!-- Level of Care -->
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

                                <!-- Authorizations -->
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

                                <!-- Line of Services -->
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

                                <!-- Attendance Units -->
                                <div class="d-flex justify-content-center gap-1">
                                    @foreach ($sessionTypes as $type)
                                        @php
                                            $att = $attendances[$type][$currentDate] ?? null;
                                            $units = $att && $att->attended ? $att->units : '0';
                                            $bgColor =
                                                $att && $att->attended
                                                    ? match ($type) {
                                                        'group' => '#008000',
                                                        'peer_group' => '#008080',
                                                        'peer_individual' => '#2222ab',
                                                        default => 'bg-secondary',
                                                    }
                                                    : 'bg-dark';
                                        @endphp
                                        <span class="small px-2 rounded text-white"
                                            style="background-color:{{ $bgColor }}">{{ $units }}</span>
                                    @endforeach
                                </div>
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
                                        $totalBilled = 0;
                                        $totalPaid = 0;

                                        foreach ($claimLines[$currentDate] ?? collect() as $line) {
                                            $code = $line->service_code;
                                            $billed = $line->billed_amount;
                                            $paid = $line->check_id ? $line->processed_amount : 0;

                                            $billedSummary[$code] = ($billedSummary[$code] ?? 0) + $billed;
                                            $paidSummary[$code] = ($paidSummary[$code] ?? 0) + $paid;

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
                                    <!-- Batch Claim Checkboxes -->
                                    @php
                                        $billingOptions = [
                                            'H0001' => ['label' => 'Asst', 'color' => '#ff9999'],
                                        ];

                                        $hasPHP = $activeLevels->contains(fn($level) => $level->levelOfCare?->level_of_care === 'PHP');
                                        $hasIOP = $activeLevels->contains(fn($level) => $level->levelOfCare?->level_of_care === 'IOP');

                                        if ($hasPHP) {
                                            $billingOptions['H2036-22'] = ['label' => 'Group-22', 'color' => '#3490dc'];
                                            $billingOptions['H2036'] = ['label' => 'Group', 'color' => '#3490dc'];
                                        }

                                        if ($hasIOP) {
                                            $billingOptions['H0015'] = ['label' => 'IOP', 'color' => '#6f42c1'];
                                        }

                                        $billingOptions['H0024'] = ['label' => 'PG', 'color' => '#ffccff'];
                                        $billingOptions['H0038'] = ['label' => 'PI', 'color' => '#d9b3ff'];
                                    @endphp

                                    <div class="mt-1">
                                        @foreach ($billingOptions as $code => $meta)
                                            @php
                                                $checkboxId = "claim-{$currentDate}-{$code}";
                                            @endphp
                                            <div class="form-check form-check-inline" title="{{ $meta['label'] }}">
                                                <input class="form-check-input" type="checkbox"
                                                    id="{{ $checkboxId }}"
                                                    name="claim_days[{{ $currentDate }}][{{ $code }}]"
                                                    value="1" style="background-color: {{ $meta['color'] }};">
                                                <label class="form-check-label small" for="{{ $checkboxId }}">
                                                    {{ $meta['label'] }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>


                                    <!-- Totals with no background -->
                                    {{-- <div class="text-white small mt-1" style="text-align: center;">
                                        <div><strong>Total Billed:</strong> ${{ number_format($totalBilled, 2) }}</div>
                                        <div><strong>Total Paid:</strong> ${{ number_format($totalPaid, 2) }}</div>
                                    </div> --}}
                                @endif

                            </div>
                        </td>
                        @php $dateCounter++; @endphp
                    @endif
                @endfor
            </tr>
        @endif
    @endfor
{{-- </tbody> --}}
