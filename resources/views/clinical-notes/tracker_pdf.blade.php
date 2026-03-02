<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Chart Compliance Summary</title>
    <style>
        @php
            $letterheadPath = storage_path('app/private/templates/Dashboard Letterhead Landscape.png');
            $letterheadData = file_exists($letterheadPath)
                ? base64_encode(file_get_contents($letterheadPath))
                : '';
            $letterheadUrl = $letterheadData ? "data:image/png;base64,{$letterheadData}" : '';
        @endphp

        @page {
            size: letter landscape;
            margin: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 36px;
            background-image: url('{{ $letterheadUrl }}');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: cover;
        }

        h2 { margin-bottom: 6px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; vertical-align: top; }
        th { background: #f4f4f4; }
        .text-danger { color: #c00; }
        .text-primary { color: #0056b3; }
        .text-muted { color: #888; }
        .section-title { margin-top: 18px; }
    </style>
</head>
<body>
    @php
        $assignedRows = $assignedRows ?? $trackerRows ?? collect();
        $unassignedRows = $unassignedRows ?? collect();
        $asOfDate = $asOfDate ?? now()->format('m/d/Y');
        $cutoffDate = \Carbon\Carbon::parse(\App\Services\ClinicalNoteTrackerService::CUTOFF_DATE);
        $today = \Carbon\Carbon::today();
        $dueSoonDate = $today->copy()->addDays(5);
        $showCounselorColumn = empty($counselorName);

        $renderRows = function ($rows, bool $includeCounselorColumn) use ($noteTypeOptions, $cutoffDate, $today, $dueSoonDate) {
            if ($rows->isEmpty()) {
                return '<tr><td colspan="' . ($noteTypeOptions->count() + ($includeCounselorColumn ? 2 : 1)) . '"><span class="text-muted">—</span></td></tr>';
            }

            $html = '';
            foreach ($rows as $row) {
                $hasActionableDate = false;
                $rowHtml = '<tr>';
                $clientName = strtoupper($row['client']->last_name ?? '') . ', ' . strtoupper($row['client']->first_name ?? '');
                $rowHtml .= '<td>' . e($clientName) . '</td>';
                if ($includeCounselorColumn) {
                    $rowHtml .= '<td>' . e($row['client']->counselor?->name ?? 'Unassigned') . '</td>';
                }
                foreach ($noteTypeOptions as $noteType) {
                    $dueDate = $row['due_dates']->get($noteType);
                    $cellDates = [];

                    if ($dueDate === 'Complete') {
                        $rowHtml .= '<td><span>Complete</span></td>';
                        continue;
                    }

                    if (is_array($dueDate)) {
                        foreach ($dueDate as $date) {
                            $parsed = \Carbon\Carbon::parse($date);
                            if ($parsed->lt($cutoffDate)) {
                                continue;
                            }
                            if ($parsed->lt($today)) {
                                $cellDates[] = '<div class="text-danger">' . e($parsed->format('m/d/Y')) . '</div>';
                                $hasActionableDate = true;
                                continue;
                            }
                            if ($parsed->lte($dueSoonDate)) {
                                $cellDates[] = '<div class="text-primary">' . e($parsed->format('m/d/Y')) . '</div>';
                                $hasActionableDate = true;
                            }
                        }
                    } elseif ($dueDate) {
                        $parsed = \Carbon\Carbon::parse($dueDate);
                        if ($parsed->gte($cutoffDate)) {
                            if ($parsed->lt($today)) {
                                $cellDates[] = '<div class="text-danger">' . e($parsed->format('m/d/Y')) . '</div>';
                                $hasActionableDate = true;
                            } elseif ($parsed->lte($dueSoonDate)) {
                                $cellDates[] = '<div class="text-primary">' . e($parsed->format('m/d/Y')) . '</div>';
                                $hasActionableDate = true;
                            }
                        }
                    }

                    if (empty($cellDates)) {
                        $rowHtml .= '<td><span class="text-muted">—</span></td>';
                    } else {
                        $rowHtml .= '<td>' . implode('', $cellDates) . '</td>';
                    }
                }
                $rowHtml .= '</tr>';

                if ($hasActionableDate) {
                    $html .= $rowHtml;
                }
            }

            if ($html === '') {
                return '<tr><td colspan="' . ($noteTypeOptions->count() + ($includeCounselorColumn ? 2 : 1)) . '"><span class="text-muted">—</span></td></tr>';
            }

            return $html;
        };
    @endphp

    <h2>Daily Chart Compliance Summary</h2>
    <div class="meta">
        <div><strong>As of:</strong> {{ $asOfDate }}</div>
        @if (! empty($counselorName))
            <div><strong>Counselor:</strong> {{ $counselorName }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                @if ($showCounselorColumn)
                    <th>Counselor</th>
                @endif
                @foreach ($noteTypeOptions as $noteType)
                    <th>{{ $noteType }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {!! $renderRows($assignedRows, $showCounselorColumn) !!}
        </tbody>
    </table>

    @if ($unassignedRows->isNotEmpty())
        <h3 class="section-title">Clients with no counselor</h3>
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    @foreach ($noteTypeOptions as $noteType)
                        <th>{{ $noteType }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {!! $renderRows($unassignedRows, false) !!}
            </tbody>
        </table>
    @endif
</body>
</html>
