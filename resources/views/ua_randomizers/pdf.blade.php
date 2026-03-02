<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>UA List {{ $randomizer->generated_for_date->format('m/d/Y') }}</title>
    <style>
        @include('partials.pdf-letterhead-css')

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }
        h1 { font-size: 18px; margin-bottom: 8px; }
        .meta { margin-bottom: 16px; }
        .meta div { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>UA List</h1>
    <div class="meta">
        <div><strong>Date:</strong> {{ $randomizer->generated_for_date->format('m/d/Y') }}</div>
        <div><strong>Level of Care:</strong> {{ $randomizer->levelOfCare?->display_name ?? 'All' }}</div>
        <div><strong>House:</strong> {{ $randomizer->house?->house_name ?? 'All' }}</div>
        <div><strong>Remarks:</strong> {{ $randomizer->remarks ?? '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Clients</th>
            </tr>
        </thead>
        <tbody>
            @forelse($randomizer->clients as $client)
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                </tr>
            @empty
                <tr>
                    <td>No clients selected.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
