<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance {{ $level }} {{ $date->format('F j, Y') }}</title>
    @php
        $letterheadPath = storage_path('app/private/templates/Dashboard Letterhead Portrait.png');
        $letterheadUrl = 'file://' . str_replace(' ', '%20', $letterheadPath);
    @endphp
    <style>
        @page {
            size: letter;
            margin: 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            background-image: url('{{ $letterheadUrl }}');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: cover;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .meta {
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f3f3f3;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>Attendance — {{ $level }} — {{ $date->format('F j, Y') }}</h1>
    <div class="meta">
        <div>Session Type: {{ ucfirst(str_replace('_', ' ', $sessionType)) }}</div>
        @if ($clientGroup)
            <div>Client Group: {{ $clientGroup->name }}</div>
        @else
            <div>Client Group: All</div>
        @endif
        <div>Present Only: {{ $presentOnly ? 'Yes' : 'No' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Date of Birth</th>
                <th>Level of Care</th>
                <th class="text-center">Present</th>
                <th class="text-center">Units</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $row)
                @php
                    $client = $row['client'];
                    $attendance = $row['attendance'];
                @endphp
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <td>
                        @if ($client->date_of_birth)
                            {{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}
                        @endif
                    </td>
                    <td>{{ $row['level'] }}</td>
                    <td class="text-center">{{ $attendance && $attendance->attended ? 'Yes' : 'No' }}</td>
                    <td class="text-center">{{ $attendance->units ?? '' }}</td>
                    <td>{{ $attendance->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No attendance records for this day.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
