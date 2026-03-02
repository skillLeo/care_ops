@extends('adminlte::page')

@section('title', 'View Client')

@section('content_header')
    <h1>View Client</h1>
@stop

@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <a href="{{ route('clients.index') }}" class="btn btn-secondary">Back to Clients</a>
        @can('client.download_pdf')
            <a href="{{ route('clients.pdf', $client) }}" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        @endcan
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>First Name</label>
            <input type="text" class="form-control" value="{{ $client->first_name }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Last Name</label>
            <input type="text" class="form-control" value="{{ $client->last_name }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Date of Birth</label>
            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Gender</label>
            <input type="text" class="form-control" value="{{ ucfirst($client->gender) }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Email</label>
            <input type="email" class="form-control" value="{{ $client->email }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Phone</label>
            <input type="text" class="form-control" value="{{ $client->phone }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Guest</label>
            <input type="text" class="form-control" value="{{ $client->guest ? 'Yes' : 'No' }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Eligibility</label>
            <input type="text" class="form-control" value="{{ $client->eligibility ? ucwords(str_replace('_', ' ', $client->eligibility)) : '-' }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Redetermination Date</label>
            <input type="text" class="form-control" value="{{ $client->redetermination_date ? \Carbon\Carbon::parse($client->redetermination_date)->format('m/d/Y') : '-' }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Redetermination Date Last Checked</label>
            <input type="text" class="form-control" value="{{ $client->redetermination_last_checked ? \Carbon\Carbon::parse($client->redetermination_last_checked)->format('m/d/Y') : '-' }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Redetermination Remarks</label>
            <input type="text" class="form-control" value="{{ $client->redetermination_remarks ?? '-' }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>House / Apartment</label>
            <input type="text" class="form-control" value="{{ optional(optional($client->getDisplayApartment(now()->toDateString()))->house)->house_name ?? '-' }} - {{ optional($client->getDisplayApartment(now()->toDateString()))->apartment_number ?? '-' }} ({{ optional($client->getDisplayApartment(now()->toDateString()))->clients()->active()->count() ?? 0 }}/{{ optional($client->getDisplayApartment(now()->toDateString()))->capacity ?? 0 }})" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Source</label>
            <input type="text" class="form-control" value="{{ $client->dropbox ? ucfirst($client->dropbox->type) : 'None' }}" readonly>
        </div>
        @if($client->dropbox)
            <div class="col-md-6 form-group d-flex align-items-end">
                @can('dropbox.view')
                    <a href="{{ route('dropboxes.show', $client->dropbox) }}" class="btn btn-outline-info" target="_blank">View Dropbox</a>
                @endcan
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>MRN</label>
            <input type="text" class="form-control" value="{{ $client->mrn }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>SSN</label>
            @can('client.view_ssn')
                <input type="text" class="form-control" value="{{ $client->ssn }}" readonly>
            @else
                <input type="text" class="form-control" value="***" disabled>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Medicaid ID</label>
            @can('client.view_medicaid_id')
                <input type="text" class="form-control" value="{{ $client->medicaid_id }}" readonly>
            @else
                <input type="text" class="form-control" value="***" disabled>
            @endcan
        </div>
        <div class="col-md-6 form-group">
            <label>Carelon ID</label>
            @can('client.view_carelon_id')
                <input type="text" class="form-control" value="{{ $client->carelon_id }}" readonly>
            @else
                <input type="text" class="form-control" value="***" disabled>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Starting Date</label>
            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y') }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Discharge Date</label>
            <input type="text" class="form-control" value="{{ $client->discharge_date ? \Carbon\Carbon::parse($client->discharge_date)->format('m/d/Y') : '' }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Reactivation Date</label>
            <input type="text" class="form-control" value="{{ $client->reactivation_date ? \Carbon\Carbon::parse($client->reactivation_date)->format('m/d/Y') : '' }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Status</label>
            <input type="text" class="form-control" value="{{ ucfirst($client->status) }}" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 form-group">
            <div class="form-group mb-1">

            </div>
            <div class="form-group mb-1">
                <label>Address</label>
                <textarea class="form-control" rows="2" readonly>{{ $client->address }}</textarea>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea class="form-control" rows="2" readonly>{{ $client->notes }}</textarea>
            </div>
            <div class="form-group">
                <label>EVS Data</label>
                <textarea class="form-control" rows="2" readonly>{{ $client->evs_data }}</textarea>
            </div>
        </div>
        <div class="col-md-6 form-group">
            <label>Profile Photo</label><br>
            @if ($client->profile_photo)
                <img src="{{ route('client.photo', $client->id) }}" class="img-thumbnail mt-2" style="max-width: 200px; height: auto;">
            @else
                <p class="form-control-plaintext">No photo available</p>
            @endif
        </div>
    </div>



    <div class="form-group">
        <h4>Level of Care History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">Level of Care</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($client->levelOfCareHistory->sortBy('start_date') as $level)
                    <tr>
                        <td>{{ $level->levelOfCare?->display_name ?? '-' }}</td>
                        <td>{{ $level->start_date ? \Carbon\Carbon::parse($level->start_date)->format('m/d/Y') : '-' }}</td>
                        <td>{{ $level->end_date ? \Carbon\Carbon::parse($level->end_date)->format('m/d/Y') : '-' }}</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted">No level of care history.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h4>Client Group History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">Group</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($client->clientGroupHistory->sortBy('start_date') as $entry)
                <tr>
                    <td >{{ $entry->assignment?->name ?? '-' }}</td>
                    <td>{{ $entry->start_date ? \Carbon\Carbon::parse($entry->start_date)->format('m/d/Y') : '-' }}</td>
                    <td>{{ $entry->end_date ? \Carbon\Carbon::parse($entry->end_date)->format('m/d/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted">No client group history.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h4>Counselor History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">Counselor</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($client->counselorHistory->sortBy('start_date') as $entry)
                <tr>
                    <td>{{ $entry->assignment?->short_name ?? $entry->assignment?->name ?? '-' }}</td>
                    <td>{{ $entry->start_date ? \Carbon\Carbon::parse($entry->start_date)->format('m/d/Y') : '-' }}</td>
                    <td>{{ $entry->end_date ? \Carbon\Carbon::parse($entry->end_date)->format('m/d/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted">No counselor history.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h4>Peer History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">Peer</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($client->peerHistory->sortBy('start_date') as $entry)
                <tr>
                    <td>{{ $entry->assignment?->short_name ?? $entry->assignment?->name ?? '-' }}</td>
                    <td>{{ $entry->start_date ? \Carbon\Carbon::parse($entry->start_date)->format('m/d/Y') : '-' }}</td>
                    <td>{{ $entry->end_date ? \Carbon\Carbon::parse($entry->end_date)->format('m/d/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted">No peer history.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h4>Peer Group History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">Peer Group</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($client->peerGroupHistory->sortBy('start_date') as $entry)
                <tr>
                    <td>{{ $entry->assignment?->name ?? '-' }}</td>
                    <td>{{ $entry->start_date ? \Carbon\Carbon::parse($entry->start_date)->format('m/d/Y') : '-' }}</td>
                    <td>{{ $entry->end_date ? \Carbon\Carbon::parse($entry->end_date)->format('m/d/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted">No peer group history.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <h4>Apartment History</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 40%;">House / Apartment</th>
                    <th style="width: 30%;">Start Date</th>
                    <th style="width: 30%;">End Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($client->apartmentHistory->sortBy('start_date') as $entry)
                <tr>
                    <td>{{ optional($entry->assignment?->house)->house_name ?? '-' }} / {{ $entry->assignment?->apartment_number ?? '-' }} ({{ $entry->assignment?->clients()->active()->count() ?? 0 }}/{{ $entry->assignment?->capacity ?? 0 }})</td>
                    <td>{{ $entry->start_date ? \Carbon\Carbon::parse($entry->start_date)->format('m/d/Y') : '-' }}</td>
                    <td>{{ $entry->end_date ? \Carbon\Carbon::parse($entry->end_date)->format('m/d/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted">No apartment history.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @can('hospitalization.view')
        <div class="form-group">
            <h4>Hospitalization History</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 40%;">Type (Facility)</th>
                        <th style="width: 30%;">Start Date</th>
                        <th style="width: 30%;">End Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->hospitalizations->sortBy('start_date') as $hospitalization)
                        <tr>
                            <td>{{ ucfirst($hospitalization->type ?? 'Hospitalization') }} ({{ $hospitalization->facility?->name ?? '-' }})</td>
                            <td>{{ $hospitalization->start_date ? \Carbon\Carbon::parse($hospitalization->start_date)->format('m/d/Y') : '-' }}</td>
                            <td>{{ $hospitalization->end_date ? \Carbon\Carbon::parse($hospitalization->end_date)->format('m/d/Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">No hospitalization history.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcan
@endsection
