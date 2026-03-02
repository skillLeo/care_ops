@extends('adminlte::page')

@section('title', 'Attendance for ' . $client->last_name . ' , ' . $client->first_name)

@section('content_header')
    <h1>Attendance for {{ $client->last_name }}, {{ $client->first_name }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('attendances.store', $client) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="session_type" value="{{ $sessionType }}">

        <div class="d-flex mb-3 align-items-center">
            <a href="{{ route('attendances.show', ['client' => $client->id, 'month' => \Carbon\Carbon::create($year, $month, 1)->subMonth()->month, 'year' => \Carbon\Carbon::create($year, $month, 1)->subMonth()->year, 'session_type' => $sessionType]) }}"
               class="btn btn-primary">←</a>

            <h4 class="mx-3 my-0">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</h4>

            <a href="{{ route('attendances.show', ['client' => $client->id, 'month' => \Carbon\Carbon::create($year, $month, 1)->addMonth()->month, 'year' => \Carbon\Carbon::create($year, $month, 1)->addMonth()->year, 'session_type' => $sessionType]) }}"
               class="btn btn-primary">→</a>

            <select name="session_type" class="form-control w-25 mx-3"
                    onchange="window.location.href=this.options[this.selectedIndex].getAttribute('data-url')">
                @foreach(['group', 'peer_individual', 'peer_group'] as $type)
                    <option data-url="{{ route('attendances.show', ['client' => $client->id, 'month' => $month, 'year' => $year, 'session_type' => $type]) }}"
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

                                        @php $entry = $attendances[$dateStr] ?? null; @endphp

                                        <input type="number"
                                               name="attendance[{{ $dateStr }}][units]"
                                               class="form-control form-control-sm w-50 mx-auto text-center"
                                               min="0"
                                               value="{{ old("attendance.$dateStr.units", $entry->units ?? 0) }}"
                                               {{ $current->gt($today) ? 'hidden' : '' }}>

                                        <input type="hidden"
                                               name="attendance[{{ $dateStr }}][id]"
                                               value="{{ $entry->id ?? '' }}">

                                        @if ($entry && ! empty($entry->attachments))
                                            <div class="mt-1 d-flex flex-column gap-1">
                                                @foreach ($entry->attachments as $file)
                                                    <a href="{{ route('attendances.attachments.download', [$entry, $file]) }}" class="badge bg-info text-white text-wrap">Attachment {{ $loop->iteration }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                @php $current->addDay(); @endphp
                            @endfor
                        </tr>
                    @endwhile
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <label for="attendance_attachments" class="form-label">Attachments</label>
            <input type="file" name="attachments[]" id="attendance_attachments" class="form-control" multiple>
        </div>

        <button type="submit" class="btn btn-success mt-3">Submit</button>
    </form>
@stop
