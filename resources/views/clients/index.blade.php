@extends('adminlte::page')

@section('title', 'Clients')

@section('content_header')
    <h1>Clients</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
        <form method="GET" action="{{ route('clients.index') }}" class="form-inline">
            <label for="status" class="mr-2">Show</label>
            <select name="status" id="status" class="form-control mr-2">
                <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
            </select>
            <button type="submit" class="btn btn-secondary">Apply</button>
        </form>
    </div>
    <table id="clientsTable" class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <!--<th>Last Name</th>-->
                <th>MRN</th>
                <th>Guest</th>
                <th>Status</th>
                <th>Current Level of Care</th>
                <th>Level of Cares</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ strtoupper($client->last_name) }}, {{ strtoupper($client->first_name) }}</td>
                    <!--<td></td>-->
                    <td>{{ $client->mrn }}</td>
                    <td>{{ $client->guest ? 'Yes' : 'No' }}</td>
                    <td>{{ ucfirst($client->status) }}</td>
                    <td>{{ $client->getCurrentLevelOfCare() ?? 'N/A' }}</td>
                    <td>
                        <div class="text-uppercase text-muted small font-weight-bold mb-1">Level of Cares</div>
                        @if($client->levelOfCareHistory->isNotEmpty())
                            @foreach($client->levelOfCareHistory->sortBy('start_date') as $level)
                                {{ $level->levelOfCare?->display_name ?? '-' }}
                                ({{ \Carbon\Carbon::parse($level->start_date)->format('m/d/Y') }} -
                                {{ $level->end_date ? \Carbon\Carbon::parse($level->end_date)->format('m/d/Y') : 'Ongoing' }})
                                <br>
                            @endforeach
                        @else
                            <span class="text-muted">No level of care history</span>
                        @endif
                        @can('hospitalization.view')
                            <div class="text-uppercase text-muted small font-weight-bold mt-2 mb-1">Hospitalizations</div>
                            @if($client->hospitalizations->isNotEmpty())
                                @foreach($client->hospitalizations->sortBy('start_date') as $hospitalization)
                                    {{ ucfirst($hospitalization->type ?? 'Hospitalization') }}
                                    ({{ \Carbon\Carbon::parse($hospitalization->start_date)->format('m/d/Y') }} -
                                    {{ $hospitalization->end_date ? \Carbon\Carbon::parse($hospitalization->end_date)->format('m/d/Y') : 'Ongoing' }})
                                    @if ($hospitalization->facility)
                                        - {{ $hospitalization->facility->name }}
                                    @endif
                                    <br>
                                @endforeach
                            @else
                                <span class="text-muted">No hospitalization history</span>
                            @endif
                        @endcan
                    </td>
                    <td>
                        @php
                            $hasDischargeDate = !is_null($client->discharge_date);
                            $activeHospitalization = $client->hospitalizations->first(function ($hospitalization) {
                                $today = \Carbon\Carbon::today();

                                return \Carbon\Carbon::parse($hospitalization->start_date)->lte($today)
                                    && (
                                        is_null($hospitalization->end_date)
                                        || \Carbon\Carbon::parse($hospitalization->end_date)->gte($today)
                                    );
                            });
                            $canReadmit = $activeHospitalization
                                && in_array($activeHospitalization->type, ['hospitalization', 'detox'], true);
                        @endphp
                        <div class="dropdown">
                            <button class="btn btn-secondary btn-sm" type="button" id="actionsDropdown{{ $client->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bars"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="actionsDropdown{{ $client->id }}">
                                <h6 class="dropdown-header text-primary">Client Info</h6>
                                @can('client.view')
                                    <a class="dropdown-item" href="{{ route('clients.show', $client->id) }}">View</a>
                                @endcan
                                @can('client.edit')
                                    <a class="dropdown-item" href="{{ route('clients.edit', $client->id) }}">Edit</a>
                                @endcan
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header text-warning">Care Changes</h6>
                                @can('client.discharge')
                                    @unless($hasDischargeDate)
                                        <a class="dropdown-item" href="{{ route('clients.discharge.form', $client->id) }}">Discharge</a>
                                    @endunless
                                @endcan
                                @can('client.transition')
                                    @unless($hasDischargeDate)
                                        <a class="dropdown-item" href="{{ route('clients.transition', $client->id) }}">Transition</a>
                                    @endunless
                                @endcan
                                @can('client.reactivate')
                                    @if($hasDischargeDate)
                                        <a class="dropdown-item" href="{{ route('clients.reactivate', $client->id) }}">Reactivate</a>
                                    @endif
                                @endcan
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header text-danger">Hospitalization</h6>
                                @can('hospitalization.create')
                                    @unless($activeHospitalization)
                                        <a class="dropdown-item" href="{{ route('clients.hospitalize.form', $client->id) }}">Hospitalize</a>
                                    @endunless
                                @endcan
                                @can('hospitalization.readmit')
                                    @if($canReadmit)
                                        <a class="dropdown-item" href="{{ route('clients.readmit', $client->id) }}">Readmit</a>
                                    @endif
                                @endcan
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header text-info">Other Actions</h6>
                                @if(!auth()->user()->can('client.view_calendar'))
                                    @can('attendance.view')
                                        <a class="dropdown-item" href="{{ route('attendances.show', $client->id) }}">Attendance</a>
                                    @endcan
                                    @can('note.view')
                                        <a class="dropdown-item" href="{{ route('notes.show', $client->id) }}">Notes</a>
                                    @endcan
                                    <a class="dropdown-item" href="{{ route('tasks.index', ['client_id' => $client->id]) }}">Tasks</a>
                                @else
                                    @can('client.view_calendar')
                                        <a class="dropdown-item" href="{{ route('clients.calendar', $client->id) }}">Calendar</a>
                                        <a class="dropdown-item" href="{{ route('clients.calendar2026', $client->id) }}">Calendar (2026)</a>
                                    @endcan
                                    @can('attendance.view')
                                        <a class="dropdown-item" href="{{ route('attendances.show', $client->id) }}">Attendance</a>
                                    @endcan
                                    @can('note.view')
                                        <a class="dropdown-item" href="{{ route('notes.show', $client->id) }}">Notes</a>
                                    @endcan
                                    <a class="dropdown-item" href="{{ route('tasks.index', ['client_id' => $client->id]) }}">Tasks</a>
                                    @can('claim.view_calendar')
                                        <a class="dropdown-item" href="{{ route('claims.calendar', $client->id) }}">Submit Claims</a>
                                        <a class="dropdown-item" href="{{ route('claims.calendar2026', $client->id) }}">Submit Claims (2026)</a>
                                    @endcan
                                @endif
                                @if(auth()->user()->canDeleteRecords())
                                    @can('client.delete')
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="dropdown-item text-danger">Delete</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('#clientsTable').DataTable({
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    title: 'Clients'
                }
            ]
        });
    });
</script>
@stop
