@extends('adminlte::page')

@section('title', 'Notes for ' . $client->last_name . ' , ' . $client->first_name)

@section('content_header')
    <h1>Notes for {{ $client->last_name }}, {{ $client->first_name }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('notes.store', $client) }}">
        @csrf
        <input type="hidden" name="session_type" value="{{ $sessionType }}">

        <div class="d-flex mb-3 align-items-center">
            <a href="{{ route('notes.show', ['client' => $client->id, 'month' => \Carbon\Carbon::create($year, $month)->subMonth()->month, 'year' => \Carbon\Carbon::create($year, $month)->subMonth()->year, 'session_type' => $sessionType]) }}"
               class="btn btn-primary">←</a>

            <h4 class="mx-3 my-0">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</h4>

            <a href="{{ route('notes.show', ['client' => $client->id, 'month' => \Carbon\Carbon::create($year, $month)->addMonth()->month, 'year' => \Carbon\Carbon::create($year, $month)->addMonth()->year, 'session_type' => $sessionType]) }}"
               class="btn btn-primary">→</a>

            <select name="session_type" class="form-control w-25 mx-3"
                    onchange="window.location.href=this.options[this.selectedIndex].getAttribute('data-url')">
                @foreach(['group', 'peer_individual', 'peer_group'] as $type)
                    <option data-url="{{ route('notes.show', ['client' => $client->id, 'month' => $month, 'year' => $year, 'session_type' => $type]) }}"
                            {{ $sessionType === $type ? 'selected' : '' }}>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead>
                    <tr>
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $start = \Carbon\Carbon::create($year, $month, 1);
                        $end = $start->copy()->endOfMonth();
                        $current = $start->copy()->startOfWeek();
                        $today = \Carbon\Carbon::today();
                    @endphp

                    @while ($current <= $end->copy()->endOfWeek())
                        <tr>
                            @for ($i = 0; $i < 7; $i++)
                                @php $dateStr = $current->format('Y-m-d'); @endphp
                                <td style="height: 100px;">
                                    @if ($current->month == $month)
                                    {{ $current->day }}

                                    @php
                                        $entry = $notes[$dateStr] ?? null;
                                        $attendance = $attendances[$dateStr] ?? null;
                                    @endphp

                                    <div class="small text-muted">Attendance: {{ $attendance->units ?? 0 }}</div>

                                    <input type="number"
                                        name="notes[{{ $dateStr }}][units]"
                                        class="form-control form-control-sm w-50 mx-auto text-center"
                                        min="0"
                                        value="{{ old("notes.$dateStr.units", $entry->units ?? 0) }}"
                                        {{ $current->gt($today) ? 'hidden' : '' }}>

                                    <input type="hidden"
                                        name="notes[{{ $dateStr }}][id]"
                                        value="{{ $entry->id ?? '' }}">
                                @endif

                                </td>
                                @php $current->addDay(); @endphp
                            @endfor
                        </tr>
                    @endwhile
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>
@stop
