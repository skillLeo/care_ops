@extends('adminlte::page')

@section('title', 'Reactivate - ' . $client->full_name)

@section('content_header')
    <h1>Reactivate Client: {{ $client->full_name }}</h1>
@stop

@section('content')
@include('clients.partials.info-card')
<form method="POST" action="{{ route('clients.reactivate.submit', $client->id) }}">
    @csrf

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h4>Last</h4>
                    <div class="form-group"><label>End Date</label><input class="form-control" readonly value=""></div>
                    <div class="form-group"><label>Level of Care</label><input class="form-control" readonly value="{{ $lastLevelLabel ?? '-' }}"></div>
                    <div class="form-group"><label>Group</label><input class="form-control" readonly value="{{ $currentGroup?->name ?? '-' }}"></div>
                    <div class="form-group"><label>Counselor</label><input class="form-control" readonly value="{{ optional($currentCounselor)->name ?? '-' }}"></div>
                    <div class="form-group"><label>Peer</label><input class="form-control" readonly value="{{ optional($currentPeer)->name ?? '-' }}"></div>
                    <div class="form-group"><label>Peer Group</label><input class="form-control" readonly value="{{ optional($currentPeerGroup)->name ?? '-' }}"></div>
                    @php
                        $currentApartmentOccupancy = $currentApartment ? $currentApartment->clients()->active()->count() : 0;
                    @endphp
                    <div class="form-group"><label>House / Apartment</label><input class="form-control" readonly value="{{ optional(optional($currentApartment)->house)->house_name ?? '-' }} - {{ optional($currentApartment)->apartment_number ?? '-' }} ({{ $currentApartmentOccupancy }}/{{ optional($currentApartment)->capacity ?? 0 }})"></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h4>New</h4>
                    <div class="form-group"><label>Start Date</label><input type="date" name="new_loc_start_date" id="new_loc_start_date" class="form-control" value="{{ old('new_loc_start_date') }}" required></div>
                    <div class="form-group">
                        <label>Level of Care</label>
                        <select name="new_loc" id="new_loc" class="form-control" required>
                            @foreach($levels as $level)
                                <option value="{{ $level->id }}" {{ (string) old('new_loc', $lastLevelId) === (string) $level->id ? 'selected' : '' }}>{{ $level->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="client_group_id">Group</label>
                        <select name="client_group_id" id="client_group_id" class="form-control">
                            <option value="">Select a Group</option>
                            @foreach($clientGroups as $group)
                                <option value="{{ $group->id }}" data-level-of-care-id="{{ $group->level_of_care_id }}" {{ (string) old('client_group_id', optional($currentGroup)->id) === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label for="counselor_id">Counselor</label><select name="counselor_id" id="counselor_id" class="form-control"><option value="">Select a Counselor</option>@foreach($counselors as $counselor)<option value="{{ $counselor->id }}" {{ (string) old('counselor_id', optional($currentCounselor)->id) === (string) $counselor->id ? 'selected' : '' }}>{{ $counselor->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="peer_id">Peer</label><select name="peer_id" id="peer_id" class="form-control"><option value="">Select a Peer</option>@foreach($peers as $peer)<option value="{{ $peer->id }}" {{ (string) old('peer_id', optional($currentPeer)->id) === (string) $peer->id ? 'selected' : '' }}>{{ $peer->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="peer_group_id">Peer Group</label><select name="peer_group_id" id="peer_group_id" class="form-control"><option value="">Select a Peer Group</option>@foreach($peerGroups as $peerGroup)<option value="{{ $peerGroup->id }}" {{ (string) old('peer_group_id', optional($currentPeerGroup)->id) === (string) $peerGroup->id ? 'selected' : '' }}>{{ $peerGroup->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="apartment_id">House / Apartment</label><select name="apartment_id" id="apartment_id" class="form-control"><option value="">Select House / Apartment</option>@foreach($apartments as $apartment)<option value="{{ $apartment->id }}" {{ (string) old('apartment_id', optional($currentApartment)->id) === (string) $apartment->id ? 'selected' : '' }}>{{ optional($apartment->house)->house_name }} - {{ $apartment->apartment_number }} ({{ $apartment->clients()->active()->count() }}/{{ $apartment->capacity ?? 0 }})</option>@endforeach</select></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h4>Authorization</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="auth_option" id="auth_option_new" value="new" {{ old('auth_option', 'new') === 'new' ? 'checked' : '' }}>
                <label class="form-check-label" for="auth_option_new">Start a new authorization</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="auth_option" id="auth_option_existing" value="existing" {{ old('auth_option') === 'existing' ? 'checked' : '' }}>
                <label class="form-check-label" for="auth_option_existing">Reactivate an old authorization</label>
            </div>

            <div id="existing-auth-section" class="mt-3" style="display: none;">
                <p class="mb-2">Select an authorization that matches the chosen level of care.</p>
                <div class="alert alert-warning d-none" id="no-auth-message">No auth found for the chosen level of care, please add a new auth.</div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="auth-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Select</th>
                                <th>Auth #</th>
                                <th>Level of Care</th>
                                <th class="text-center">Start Date</th>
                                <th class="text-center">End Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($authorizations as $authorization)
                                <tr data-loc="{{ $authorization->level_of_care }}" class="auth-row d-none">
                                    <td class="text-center">
                                        <input type="radio" name="authorization_id" value="{{ $authorization->id }}" {{ (string) old('authorization_id') === (string) $authorization->id ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $authorization->auth_number ?? 'Not Provided' }}</td>
                                    <td>{{ $authorization->levelOfCare?->display_name ?? '-' }}</td>
                                    <td class="text-center">{{ $authorization->auth_starting_date ? \Carbon\Carbon::parse($authorization->auth_starting_date)->format('m/d/Y') : 'N/A' }}</td>
                                    <td class="text-center">{{ $authorization->auth_ending_date ? \Carbon\Carbon::parse($authorization->auth_ending_date)->format('m/d/Y') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">Reactivate</button>
    <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const authOptionNew = document.getElementById('auth_option_new');
        const authOptionExisting = document.getElementById('auth_option_existing');
        const existingSection = document.getElementById('existing-auth-section');
        const levelSelect = document.getElementById('new_loc');
        const startDateInput = document.getElementById('new_loc_start_date');
        const authRows = Array.from(document.querySelectorAll('.auth-row'));
        const noAuthMessage = document.getElementById('no-auth-message');
        const lastLevelId = @json($lastLevelId);
        const lastLevelEndDate = @json($lastLevelEndDate);
        const groupSelect = document.getElementById('client_group_id');
        const groupOptions = groupSelect ? Array.from(groupSelect.options) : [];

        function toggleAuthSection() {
            if (authOptionExisting.checked) {
                existingSection.style.display = 'block';
                filterAuthRows();
            } else {
                existingSection.style.display = 'none';
            }
        }

        function parseDate(value) {
            if (!value) return null;
            const parts = value.split('-').map(Number);
            if (parts.length !== 3) return null;
            return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        }

        function applyAuthDefaults() {
            if (!lastLevelId) {
                authOptionExisting.disabled = false;
                authOptionNew.disabled = false;
                toggleAuthSection();
                return;
            }

            const selectedLoc = levelSelect.value;
            const returnDate = parseDate(startDateInput.value);
            const lastLevelEnd = parseDate(lastLevelEndDate);
            const sameLoc = selectedLoc === String(lastLevelId);
            const diffDays = returnDate && lastLevelEnd
                ? Math.floor((returnDate - lastLevelEnd) / (1000 * 60 * 60 * 24))
                : null;

            if (!sameLoc || (diffDays !== null && diffDays >= 30)) {
                authOptionNew.checked = true;
                authOptionExisting.checked = false;
                authOptionExisting.disabled = true;
                authOptionNew.disabled = false;
            } else {
                authOptionExisting.disabled = false;
                authOptionNew.disabled = false;
            }

            toggleAuthSection();
        }

        function filterAuthRows() {
            const selectedLoc = levelSelect.value;
            let visibleCount = 0;

            authRows.forEach((row) => {
                const matches = row.dataset.loc === selectedLoc;
                row.classList.toggle('d-none', !matches);
                if (matches) visibleCount++;
            });

            noAuthMessage.classList.toggle('d-none', visibleCount !== 0);
        }

        function filterGroupOptions() {
            if (!groupSelect || !levelSelect) return;
            const selectedLoc = String(levelSelect.value || '');

            groupOptions.forEach((option) => {
                const optionLoc = option.dataset.levelOfCareId;
                if (!optionLoc) {
                    option.hidden = false;
                    return;
                }

                const shouldHide = optionLoc !== selectedLoc;
                option.hidden = shouldHide;
                if (shouldHide && option.selected) option.selected = false;
            });
        }

        authOptionNew.addEventListener('change', toggleAuthSection);
        authOptionExisting.addEventListener('change', toggleAuthSection);
        levelSelect.addEventListener('change', () => {
            applyAuthDefaults();
            filterAuthRows();
            filterGroupOptions();
        });
        startDateInput.addEventListener('change', applyAuthDefaults);
        startDateInput.addEventListener('input', applyAuthDefaults);

        applyAuthDefaults();
        filterAuthRows();
        filterGroupOptions();
    });
</script>
@stop
