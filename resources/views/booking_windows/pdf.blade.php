<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Booking Window {{ $bookingWindow->id }}</title>
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
    <h1>Booking Window</h1>
    <div class="meta">
        <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($bookingWindow->date)->format('m/d/Y') }}</div>
        <div><strong>Time:</strong> {{ \Carbon\Carbon::parse($bookingWindow->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($bookingWindow->end_time)->format('g:i A') }}</div>
        <div><strong>Slot Length:</strong> {{ $bookingWindow->slot_length_minutes }} minutes</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Slot</th>
                <th>Status</th>
                <th>Booked By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookingWindow->slots as $slot)
                <tr>
                    <td>{{ $slot->starts_at->format('g:i A') }} - {{ $slot->ends_at->format('g:i A') }}</td>
                    <td>{{ $slot->booked_by ? 'Booked' : 'Available' }}</td>
                    <td>{{ $slot->bookedBy?->name ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No slots available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
