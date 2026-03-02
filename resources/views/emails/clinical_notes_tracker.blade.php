<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Chart Compliance Summary</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
        .text-danger { color: #c00; }
        .text-primary { color: #0d6efd; }
        .text-muted { color: #888; }
        h2 { margin-bottom: 4px; }
        h3 { margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Daily Chart Compliance Summary</h2>
    <p>Hi {{ $counselorName }},</p>
    <p>Here are your daily chart compliance summary items as of {{ $asOfDate }}.</p>

    @php
        $cutoff = \Carbon\Carbon::parse($cutoffDate);
        $today = \Carbon\Carbon::today();
        $renderTable = function ($rows, $noteTypeOptions) use ($cutoff, $today) {
            if ($rows->isEmpty()) {
                return '<p class="text-muted">No items available.</p>';
            }
            $html = '<table>';
            $html .= '<thead><tr><th>Client Name</th>';
            foreach ($noteTypeOptions as $noteType) {
                $html .= '<th>' . e($noteType) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                $html .= '<td>' . e(strtoupper($row['client']->last_name ?? '') . ', ' . strtoupper($row['client']->first_name ?? '')) . '</td>';
                foreach ($noteTypeOptions as $noteType) {
                    $dueDate = $row['due_dates']->get($noteType);
                    if ($dueDate === 'Complete') {
                        $html .= '<td><span>Complete</span></td>';
                        continue;
                    }
                    $cellDates = [];
                    if (is_array($dueDate)) {
                        foreach ($dueDate as $date) {
                            $parsed = \Carbon\Carbon::parse($date);
                            if ($parsed->lt($cutoff)) {
                                continue;
                            }
                            $class = '';
                            if ($parsed->lt($today)) {
                                $class = 'text-danger';
                            } elseif ($parsed->lte($today->copy()->addDays(5))) {
                                $class = 'text-primary';
                            }
                            $cellDates[] = '<div class="' . $class . '">' . e($parsed->format('m/d/Y')) . '</div>';
                        }
                    } elseif ($dueDate) {
                        $parsed = \Carbon\Carbon::parse($dueDate);
                        if ($parsed->gte($cutoff)) {
                            $class = '';
                            if ($parsed->lt($today)) {
                                $class = 'text-danger';
                            } elseif ($parsed->lte($today->copy()->addDays(5))) {
                                $class = 'text-primary';
                            }
                            $cellDates[] = '<div class="' . $class . '">' . e($parsed->format('m/d/Y')) . '</div>';
                        }
                    }
                    if (empty($cellDates)) {
                        $html .= '<td><span class="text-muted">—</span></td>';
                    } else {
                        $html .= '<td>' . implode('', $cellDates) . '</td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            return $html;
        };
    @endphp

    <h3>Assigned to You</h3>
    {!! $renderTable($assignedRows, $noteTypeOptions) !!}

    <h3>Unassigned</h3>
    {!! $renderTable($unassignedRows, $noteTypeOptions) !!}
</body>
</html>
