<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Submission</title>
    <style>
        @include('partials.pdf-letterhead-css')

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        h1 { font-size: 18px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f2f2f2; text-align: left; }
        .meta { margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Clinical Submission</h1>

    <div class="meta">
        <div><strong>Submission Date:</strong> {{ optional($submission->submission_date)->format('m/d/Y') }}</div>
        <div><strong>Attendance Type:</strong> {{ $submission->type }}</div>
        <div><strong>Submission Notes:</strong> {{ $submission->remarks }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Client Name</th>
                <th>Note Type</th>
                <th>Service Code</th>
                <th>Service Date</th>
                <th>Time Start</th>
                <th>Time End</th>
                <th>Units</th>
                <th>Individual Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($noteRows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ strtoupper($row->client->last_name ?? '') }}, {{ strtoupper($row->client->first_name ?? '') }}</td>
                    <td>{{ $row->note_type ?? '—' }}</td>
                    <td>{{ $row->serviceCode?->service_code ?? '—' }}</td>
                    <td>{{ optional($row->service_date)->format('m/d/Y') }}</td>
                    <td>
                        @if ($row->time_start)
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->time_start)->format('h:i A') }}
                        @endif
                    </td>
                    <td>
                        @if ($row->time_end)
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->time_end)->format('h:i A') }}
                        @endif
                    </td>
                    <td>{{ $row->units }}</td>
                    <td>{{ $row->remarks }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
