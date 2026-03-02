@extends('adminlte::page')

@section('title', 'Edit Client')

@section('content_header')
    <h1>Edit Client</h1>
@stop

@section('content')
    <a href="{{ route('clients.index') }}" class="btn btn-secondary mb-3">Back to Clients</a>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @php
        $selectedDropbox = old('dropbox_id', $client->dropbox_id);
    @endphp
    <form action="{{ route('clients.update', $client->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" class="form-control" value="{{ $client->first_name }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" class="form-control" value="{{ $client->last_name }}" required>
            </div>
        </div>


        <div class="row">
            <div class="col-md-6 form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="{{ $client->date_of_birth }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="gender">Gender</label>
                <select name="gender" id="gender" class="form-control" required>
                    <option value="male" {{ $client->gender == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $client->gender == 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="dropbox_id">Dropbox Source</label>
                <select name="dropbox_id" id="dropbox_id" class="form-control">
                    <option value="" {{ $selectedDropbox ? '' : 'selected' }}>None</option>
                    @foreach($availableDropboxes as $dropboxOption)
                        <option value="{{ $dropboxOption->id }}" {{ (string) $selectedDropbox === (string) $dropboxOption->id ? 'selected' : '' }}>
                            {{ strtoupper($dropboxOption->last_name) }}, {{ strtoupper($dropboxOption->first_name) }} -
                            {{ optional(optional($dropboxOption->created_at)->timezone(config('app.timezone')))->format('m/d/Y h:i A') }}
                            ({{ ucfirst($dropboxOption->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if($client->dropbox)
                <div class="col-md-6 form-group d-flex align-items-end">
                    <a href="{{ route('dropboxes.show', $client->dropbox) }}" class="btn btn-outline-info" target="_blank">View Dropbox</a>
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ $client->email }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" class="form-control" value="{{ $client->phone }}" pattern="\d{3}-\d{3}-\d{4}" placeholder="123-456-7890">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="guest">Guest</label>
                <select name="guest" id="guest" class="form-control" required>
                    <option value="0" {{ !$client->guest ? 'selected' : '' }}>No</option>
                    <option value="1" {{ $client->guest ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="eligibility">Eligibility</label>
                <select name="eligibility" id="eligibility" class="form-control" required>
                    @php $eligibilityValue = old('eligibility', $client->eligibility); @endphp
                    <option value="" {{ $eligibilityValue ? '' : 'selected' }}>Select eligibility</option>
                    <option value="eligible" {{ $eligibilityValue === 'eligible' ? 'selected' : '' }}>Eligible</option>
                    <option value="not_eligible" {{ $eligibilityValue === 'not_eligible' ? 'selected' : '' }}>Not Eligible</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="redetermination_date">Redetermination Date</label>
                <input type="date" name="redetermination_date" id="redetermination_date" class="form-control" value="{{ old('redetermination_date', $client->redetermination_date) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="redetermination_last_checked">Redetermination Date Last Checked</label>
                <input type="date" name="redetermination_last_checked" id="redetermination_last_checked" class="form-control" value="{{ old('redetermination_last_checked', $client->redetermination_last_checked) }}" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="redetermination_remarks">Redetermination Remarks</label>
                <input type="text" name="redetermination_remarks" id="redetermination_remarks" class="form-control" value="{{ old('redetermination_remarks', $client->redetermination_remarks) }}">
            </div>

            <div class="col-md-6 form-group">
                <label for="apartment_id">House / Apartment</label>
                <select name="apartment_id" id="apartment_id" class="form-control">
                    <option value="">Select House / Apartment</option>
                    @foreach($houses as $house)
                        @foreach($house->apartments as $apt)
                            <option value="{{ $apt->id }}" {{ $apt->id == optional($client->apartment)->id ? 'selected' : '' }}>
                                {{ $house->house_name }} - {{ $apt->apartment_number }} ({{ $apt->clients()->active()->count() }}/{{ $apt->capacity ?? 0 }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="mrn">MRN</label>
                <input type="number" name="mrn" id="mrn" class="form-control" value="{{ $client->mrn }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="ssn">SSN</label>
                @can('client.view_ssn')
                    <input type="text" name="ssn" id="ssn" class="form-control" value="{{ $client->ssn }}" required>
                @else
                    <input type="text" id="ssn" class="form-control" value="***" disabled>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="medicaid_id">Medicaid ID</label>
                @can('client.view_medicaid_id')
                    <input type="text" name="medicaid_id" id="medicaid_id" class="form-control" value="{{ $client->medicaid_id }}" required>
                @else
                    <input type="text" id="medicaid_id" class="form-control" value="***" disabled>
                @endcan
            </div>
            <div class="col-md-6 form-group">
                <label for="carelon_id">Carelon ID</label>
                @can('client.view_carelon_id')
                    <input type="text" name="carelon_id" id="carelon_id" class="form-control" value="{{ $client->carelon_id }}" required>
                @else
                    <input type="text" id="carelon_id" class="form-control" value="***" disabled>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="starting_date">Starting Date</label>
                <input type="date" name="starting_date" id="starting_date" class="form-control" value="{{ $client->starting_date }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="discharge_date">Discharge Date</label>
                <input type="date" name="discharge_date" id="discharge_date" class="form-control" value="{{ $client->discharge_date }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="reactivation_date">Reactivation Date</label>
                <input type="date" name="reactivation_date" id="reactivation_date" class="form-control" value="{{ $client->reactivation_date }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="active" {{ $client->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $client->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" class="form-control">{{ $client->address }}</textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control">{{ $client->notes }}</textarea>
                </div>
                <div class="form-group">
                    <label for="evs_data">EVS Data</label>
                    <textarea name="evs_data" id="evs_data" class="form-control">{{ $client->evs_data }}</textarea>
                </div>


            </div>
            <div class="col-md-6 form-group">
                <label for="profile_photo">Profile Photo</label>
                <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">

                <label for="camera_select">Choose Camera</label>
                <select id="camera_select" class="form-control"></select>

                <button type="button" class="btn btn-primary mt-2" onclick="startWebcam()">Use Webcam</button>
                <button type="button" class="btn btn-danger mt-2" onclick="stopWebcam()">Stop Webcam</button>
                <video id="webcam" style="width: 320px; height: auto;" autoplay hidden></video>
                <canvas id="canvas" hidden></canvas>
                <button type="button" class="btn btn-success mt-2" id="capture-btn" hidden onclick="capturePhoto()">Capture Photo</button>

                <div id="crop-container" style="display: none;">
                    <img id="crop-preview" style="max-width: 320px; height: auto;">
                    <button type="button" class="btn btn-warning mt-2" onclick="cropImage()">Crop & Save</button>
                </div>

                <img id="preview"
                    src="{{ old('profile_photo') ? asset('storage/' . old('profile_photo')) : ($client->profile_photo ? route('client.photo', $client->id) : '') }}"
                    class="img-thumbnail mt-2"
                    style="max-width: 200px; height: auto; display: {{ old('profile_photo') || $client->profile_photo ? 'block' : 'none' }};">

                <input type="hidden" name="captured_photo" id="captured_photo">
            </div>

        </div>


        <div class="form-group">
            <h4>Level of Care History</h4>
            <p>Do NOT use this for transitioning between levels of care. Use the "Transition" button on the clients index page instead.</p>
            <table class="table table-bordered" id="levelOfCareTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Level of Care</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->levelOfCareHistory->sortBy('start_date') as $level)
                        <tr>
                            <td>
                                <select name="level_of_care[]" class="form-control">
                                    @foreach($levelOfCares as $loc)
                                        <option value="{{ $loc->id }}" {{ $level->level_of_care == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="date" name="start_date[]" class="form-control" value="{{ \Carbon\Carbon::parse($level->start_date)->format('Y-m-d') }}">
                            </td>
                            <td>
                                <input type="date" name="end_date[]" class="form-control" value="{{ $level->end_date ? \Carbon\Carbon::parse($level->end_date)->format('Y-m-d') : '' }}">
                            </td>
                            <td>
                                @auth
                                    @if (in_array(auth()->user()->email, ['fawzan@snbllc.org', 'abdullah@snbll.org'], true))
                                        <button type="button" class="btn btn-success btn-sm insert-row">Insert Above</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-row">Delete</button>
                                    @endif
                                @endauth

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @auth
                @if (in_array(auth()->user()->email, ['fawzan@snbllc.org', 'abdullah@snbll.org'], true))
                    <button type="button" class="btn btn-primary btn-sm" id="addRow">Add New Row</button>
                @endif
            @endauth

        </div>



        <div class="form-group">
            <h4>Client Group History</h4>
            <table class="table table-bordered history-table" id="clientGroupHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Group</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->clientGroupHistory->sortBy('start_date') as $entry)
                    <tr>
                        <td>
                            <select name="client_group_history[]" class="form-control">
                                <option value="">Select a Group</option>
                                @foreach($clientGroups as $group)
                                    <option value="{{ $group->id }}" {{ $entry->client_group_id == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="client_group_start_date[]" class="form-control" value="{{ optional($entry->start_date)->format('Y-m-d') ?? $entry->start_date }}"></td>
                        <td><input type="date" name="client_group_end_date[]" class="form-control" value="{{ optional($entry->end_date)->format('Y-m-d') ?? $entry->end_date }}"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-primary btn-sm add-history-row" data-target-table="clientGroupHistoryTable">Add New Row</button>
        </div>

        <div class="form-group">
            <h4>Peer Group History</h4>
            <table class="table table-bordered history-table" id="peerGroupHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Peer Group</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->peerGroupHistory->sortBy('start_date') as $entry)
                    <tr>
                        <td>
                            <select name="peer_group_history[]" class="form-control">
                                <option value="">Select a Peer Group</option>
                                @foreach($peerGroups as $peerGroup)
                                    <option value="{{ $peerGroup->id }}" {{ $entry->peer_group_id == $peerGroup->id ? 'selected' : '' }}>
                                        {{ $peerGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="peer_group_start_date[]" class="form-control" value="{{ optional($entry->start_date)->format('Y-m-d') ?? $entry->start_date }}"></td>
                        <td><input type="date" name="peer_group_end_date[]" class="form-control" value="{{ optional($entry->end_date)->format('Y-m-d') ?? $entry->end_date }}"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-primary btn-sm add-history-row" data-target-table="peerGroupHistoryTable">Add New Row</button>
        </div>

        <div class="form-group">
            <h4>Counselor History</h4>
            <table class="table table-bordered history-table" id="counselorHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Counselor</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->counselorHistory->sortBy('start_date') as $entry)
                    <tr>
                        <td>
                            <select name="counselor_history[]" class="form-control">
                                <option value="">Select a Counselor</option>
                                @foreach($counselors as $counselor)
                                    <option value="{{ $counselor->id }}" {{ $entry->counselor_id == $counselor->id ? 'selected' : '' }}>
                                        {{ $counselor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="counselor_start_date[]" class="form-control" value="{{ optional($entry->start_date)->format('Y-m-d') ?? $entry->start_date }}"></td>
                        <td><input type="date" name="counselor_end_date[]" class="form-control" value="{{ optional($entry->end_date)->format('Y-m-d') ?? $entry->end_date }}"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-primary btn-sm add-history-row" data-target-table="counselorHistoryTable">Add New Row</button>
        </div>

        <div class="form-group">
            <h4>Peer History</h4>
            <table class="table table-bordered history-table" id="peerHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Peer</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->peerHistory->sortBy('start_date') as $entry)
                    <tr>
                        <td>
                            <select name="peer_history[]" class="form-control">
                                <option value="">Select a Peer</option>
                                @foreach($peers as $peer)
                                    <option value="{{ $peer->id }}" {{ $entry->peer_id == $peer->id ? 'selected' : '' }}>
                                        {{ $peer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="peer_start_date[]" class="form-control" value="{{ optional($entry->start_date)->format('Y-m-d') ?? $entry->start_date }}"></td>
                        <td><input type="date" name="peer_end_date[]" class="form-control" value="{{ optional($entry->end_date)->format('Y-m-d') ?? $entry->end_date }}"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-primary btn-sm add-history-row" data-target-table="peerHistoryTable">Add New Row</button>
        </div>

        <div class="form-group">
            <h4>Apartment History</h4>
            <table class="table table-bordered history-table" id="apartmentHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">House / Apartment</th>
                        <th style="width: 25%;">Start Date</th>
                        <th style="width: 25%;">End Date</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->apartmentHistory->sortBy('start_date') as $entry)
                    <tr>
                        <td>
                            <select name="apartment_history[]" class="form-control">
                                <option value="">Select an Apartment</option>
                                @foreach($houses as $house)
                                    @foreach($house->apartments as $apt)
                                        <option value="{{ $apt->id }}" {{ $entry->apartment_id == $apt->id ? 'selected' : '' }}>
                                            {{ $house->house_name }} / {{ $apt->apartment_number }} ({{ $apt->clients()->active()->count() }}/{{ $apt->capacity ?? 0 }})
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="apartment_start_date[]" class="form-control" value="{{ optional($entry->start_date)->format('Y-m-d') ?? $entry->start_date }}"></td>
                        <td><input type="date" name="apartment_end_date[]" class="form-control" value="{{ optional($entry->end_date)->format('Y-m-d') ?? $entry->end_date }}"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-primary btn-sm add-history-row" data-target-table="apartmentHistoryTable">Add New Row</button>
        </div>


        @can('hospitalization.view')
            <div class="form-group">
                <h4>Hospitalization History</h4>
                @can('hospitalization.edit')
                    <table class="table table-bordered" id="hospitalizationTable">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Facility</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Mode of Transport</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->hospitalizations->sortBy('start_date') as $hospitalization)
                                <tr>
                                    <td>
                                        <select name="hospitalization_type[]" class="form-control">
                                            <option value="detox" {{ $hospitalization->type === 'detox' ? 'selected' : '' }}>Detox</option>
                                            <option value="hospitalization" {{ $hospitalization->type === 'hospitalization' ? 'selected' : '' }}>Hospitalization</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="hospitalization_facility_id[]" class="form-control">
                                            <option value="">Select Facility</option>
                                            @foreach ($hospitalizationFacilities as $facility)
                                                <option value="{{ $facility->id }}" {{ $hospitalization->hospitalization_facility_id == $facility->id ? 'selected' : '' }}>
                                                    {{ $facility->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="date" name="hospitalization_start_date[]" class="form-control" value="{{ $hospitalization->start_date ? \Carbon\Carbon::parse($hospitalization->start_date)->format('Y-m-d') : '' }}">
                                    </td>
                                    <td>
                                        <input type="date" name="hospitalization_end_date[]" class="form-control" value="{{ $hospitalization->end_date ? \Carbon\Carbon::parse($hospitalization->end_date)->format('Y-m-d') : '' }}">
                                    </td>
                                    <td>
                                        <input type="text" name="hospitalization_mode_of_transport[]" class="form-control" value="{{ $hospitalization->mode_of_transport }}">
                                    </td>
                                    <td>
                                        <input type="text" name="hospitalization_remarks[]" class="form-control" value="{{ $hospitalization->remarks }}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm insert-hospitalization-row">Insert Above</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-hospitalization-row">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">No hospitalization history.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="addHospitalizationRow">Add New Row</button>
                @else
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Facility</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->hospitalizations->sortBy('start_date') as $hospitalization)
                                <tr>
                                    <td>{{ ucfirst($hospitalization->type ?? 'Hospitalization') }}</td>
                                    <td>{{ $hospitalization->facility?->name ?? '-' }}</td>
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
                @endcan
            </div>
        @endcan

        <button type="submit" class="btn btn-success mt-3">Update Client</button>
    </form>
@endsection


@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.querySelector('#levelOfCareTable tbody');
        const hospitalizationTable = document.querySelector('#hospitalizationTable tbody');
        const addHospitalizationRowButton = document.getElementById('addHospitalizationRow');

        document.querySelector('#addRow').addEventListener('click', function () {
            addNewRow();
        });

        table.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-row')) {
                event.target.closest('tr').remove();
            }
            if (event.target.classList.contains('insert-row')) {
                const row = event.target.closest('tr');
                row.insertAdjacentHTML('beforebegin', getRowHtml());
            }
        });

        if (hospitalizationTable) {
            hospitalizationTable.addEventListener('click', function (event) {
                if (event.target.classList.contains('delete-hospitalization-row')) {
                    event.target.closest('tr').remove();
                }
                if (event.target.classList.contains('insert-hospitalization-row')) {
                    const row = event.target.closest('tr');
                    row.insertAdjacentHTML('beforebegin', getHospitalizationRowHtml());
                }
            });
        }

        function addNewRow() {
            table.insertAdjacentHTML('beforeend', getRowHtml());
        }

        function addNewHospitalizationRow() {
            if (!hospitalizationTable) {
                return;
            }

            hospitalizationTable.insertAdjacentHTML('beforeend', getHospitalizationRowHtml());
        }

        function getRowHtml() {
            return `
                <tr>
                    <td>
                        <select name="level_of_care[]" class="form-control">
                            @foreach($levelOfCares as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->display_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="date" name="start_date[]" class="form-control">
                    </td>
                    <td>
                        <input type="date" name="end_date[]" class="form-control">
                    </td>
                    <td>
                        <button type="button" class="btn btn-success btn-sm insert-row">Insert Above</button>
                        <button type="button" class="btn btn-danger btn-sm delete-row">Delete</button>
                    </td>
                </tr>
            `;
        }

        function getHospitalizationRowHtml() {
            return `
                <tr>
                    <td>
                        <select name="hospitalization_type[]" class="form-control">
                            <option value="detox">Detox</option>
                            <option value="hospitalization">Hospitalization</option>
                        </select>
                    </td>
                    <td>
                        <select name="hospitalization_facility_id[]" class="form-control">
                            <option value="">Select Facility</option>
                            @foreach ($hospitalizationFacilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="date" name="hospitalization_start_date[]" class="form-control">
                    </td>
                    <td>
                        <input type="date" name="hospitalization_end_date[]" class="form-control">
                    </td>
                    <td>
                        <input type="text" name="hospitalization_mode_of_transport[]" class="form-control">
                    </td>
                    <td>
                        <input type="text" name="hospitalization_remarks[]" class="form-control">
                    </td>
                    <td>
                        <button type="button" class="btn btn-success btn-sm insert-hospitalization-row">Insert Above</button>
                        <button type="button" class="btn btn-danger btn-sm delete-hospitalization-row">Delete</button>
                    </td>
                </tr>
            `;
        }

        if (addHospitalizationRowButton) {
            addHospitalizationRowButton.addEventListener('click', function () {
                addNewHospitalizationRow();
            });
        }
    });


    document.querySelectorAll('.history-table tbody').forEach((tbody) => {
        tbody.addEventListener('click', (event) => {
            if (event.target.classList.contains('delete-history-row')) {
                event.target.closest('tr')?.remove();
            }
            if (event.target.classList.contains('insert-history-row')) {
                const row = event.target.closest('tr');
                if (row) {
                    row.insertAdjacentHTML('beforebegin', row.outerHTML);
                }
            }
        });
    });

    function getEmptyHistoryRowHtml(tableId) {
        switch (tableId) {
            case 'clientGroupHistoryTable':
                return `
                    <tr>
                        <td>
                            <select name="client_group_history[]" class="form-control">
                                <option value="">Select a Group</option>
                                @foreach($clientGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="client_group_start_date[]" class="form-control"></td>
                        <td><input type="date" name="client_group_end_date[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                `;
            case 'peerGroupHistoryTable':
                return `
                    <tr>
                        <td>
                            <select name="peer_group_history[]" class="form-control">
                                <option value="">Select a Peer Group</option>
                                @foreach($peerGroups as $peerGroup)
                                    <option value="{{ $peerGroup->id }}">{{ $peerGroup->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="peer_group_start_date[]" class="form-control"></td>
                        <td><input type="date" name="peer_group_end_date[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                `;
            case 'counselorHistoryTable':
                return `
                    <tr>
                        <td>
                            <select name="counselor_history[]" class="form-control">
                                <option value="">Select a Counselor</option>
                                @foreach($counselors as $counselor)
                                    <option value="{{ $counselor->id }}">{{ $counselor->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="counselor_start_date[]" class="form-control"></td>
                        <td><input type="date" name="counselor_end_date[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                `;
            case 'peerHistoryTable':
                return `
                    <tr>
                        <td>
                            <select name="peer_history[]" class="form-control">
                                <option value="">Select a Peer</option>
                                @foreach($peers as $peer)
                                    <option value="{{ $peer->id }}">{{ $peer->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="peer_start_date[]" class="form-control"></td>
                        <td><input type="date" name="peer_end_date[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                `;
            case 'apartmentHistoryTable':
                return `
                    <tr>
                        <td>
                            <select name="apartment_history[]" class="form-control">
                                <option value="">Select House / Apartment</option>
                                @foreach($houses as $house)
                                    @foreach($house->apartments as $apt)
                                        <option value="{{ $apt->id }}">{{ $house->house_name }} - {{ $apt->apartment_number }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </td>
                        <td><input type="date" name="apartment_start_date[]" class="form-control"></td>
                        <td><input type="date" name="apartment_end_date[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-success btn-sm insert-history-row">Insert Above</button> <button type="button" class="btn btn-danger btn-sm delete-history-row">Delete</button></td>
                    </tr>
                `;
            default:
                return '';
        }
    }

    document.querySelectorAll('.add-history-row').forEach((button) => {
        button.addEventListener('click', () => {
            const tableId = button.dataset.targetTable;
            const table = document.getElementById(tableId);
            const tbody = table?.querySelector('tbody');
            const lastRow = tbody?.querySelector('tr:last-child');

            if (!tbody) {
                return;
            }

            if (lastRow) {
                tbody.insertAdjacentHTML('beforeend', lastRow.outerHTML);
                return;
            }

            const emptyRowHtml = getEmptyHistoryRowHtml(tableId);
            if (emptyRowHtml) {
                tbody.insertAdjacentHTML('beforeend', emptyRowHtml);
            }
        });
    });

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<script>
    let selectedDeviceId = null;
    let cropper = null;

    navigator.mediaDevices.enumerateDevices().then(devices => {
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        const select = document.getElementById('camera_select');
        videoDevices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${select.length + 1}`;
            select.appendChild(option);
        });
        selectedDeviceId = videoDevices[0]?.deviceId || null;
    });

    function startWebcam() {
        const webcam = document.getElementById('webcam');
        const captureBtn = document.getElementById('capture-btn');
        const selectedCamera = document.getElementById('camera_select').value;

        navigator.mediaDevices.getUserMedia({ video: { deviceId: selectedCamera ? { exact: selectedCamera } : undefined } })
            .then(stream => {
                webcam.srcObject = stream;
                webcam.hidden = false;
                captureBtn.hidden = false;
            })
            .catch(error => console.error("Error accessing webcam:", error));
    }

    function stopWebcam() {
        const webcam = document.getElementById('webcam');
        if (webcam.srcObject) {
            let tracks = webcam.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            webcam.srcObject = null;
        }
        webcam.hidden = true;
    }

    function capturePhoto() {
        const webcam = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const cropContainer = document.getElementById('crop-container');
        const cropPreview = document.getElementById('crop-preview');

        canvas.width = webcam.videoWidth;
        canvas.height = webcam.videoHeight;
        context.drawImage(webcam, 0, 0, canvas.width, canvas.height);
        const imageData = canvas.toDataURL('image/png');

        cropPreview.src = imageData;
        cropContainer.style.display = 'block';
        if (cropper) {
            cropper.destroy();
        }
        cropper = new Cropper(cropPreview, {});

        // Stop webcam after capture
        stopWebcam();
        document.getElementById('capture-btn').hidden = true;
    }

    function cropImage() {
        if (cropper) {
            const croppedCanvas = cropper.getCroppedCanvas();
            const imageData = croppedCanvas.toDataURL('image/png');

            document.getElementById('preview').src = imageData;
            document.getElementById('preview').style.display = 'block';
            document.getElementById('captured_photo').value = imageData;
            document.getElementById('crop-container').style.display = 'none';

            // Reset cropper for new captures
            cropper.destroy();
            cropper = null;
        }


    }

    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = x[1] + (x[2] ? '-' + x[2] : '') + (x[3] ? '-' + x[3] : '');
    });
</script>
@endsection
