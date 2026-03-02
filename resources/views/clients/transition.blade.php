@extends('adminlte::page')

@section('title', 'Transition - ' . $client->full_name)

@section('content_header')
    <h1>Transition Level of Care: {{ $client->full_name }}</h1>
@stop

@section('content')
@include('clients.partials.info-card')
<form method="POST" action="{{ route('clients.transition.submit', $client->id) }}">
    @csrf

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h4>Current</h4>
                    @if($activeLOC)
                        <input type="hidden" name="active_loc_id" value="{{ $activeLOC->id }}">
                    @endif
                    <div class="form-group"><label>End Date</label><input type="date" name="end_loc_date" id="end_loc_date" class="form-control" readonly></div>
                    <div class="form-group"><label>Level of Care</label><input class="form-control" readonly value="{{ $activeLOC?->levelOfCare?->display_name ?? '-' }}"></div>
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
                    <div class="form-group"><label>Start Date</label><input type="date" name="new_start_date" id="new_start_date" class="form-control" required></div>
                    <div class="form-group">
                        <label>Level of Care</label>
                        <select name="new_loc" id="new_loc" class="form-control" required>
                            @foreach($levelOfCares as $level)
                                <option value="{{ $level->id }}" {{ $activeLOC && $activeLOC->level_of_care == $level->id ? 'selected' : '' }}>{{ $level->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="client_group_id">Group</label>
                        <select name="client_group_id" id="client_group_id" class="form-control" required>
                            <option value="">Select a Group</option>
                            @foreach($clientGroups as $group)
                                <option value="{{ $group->id }}" data-level-of-care-id="{{ $group->level_of_care_id }}" {{ optional($currentGroup)->id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label for="counselor_id">Counselor</label><select name="counselor_id" id="counselor_id" class="form-control"><option value="">Select a Counselor</option>@foreach($counselors as $counselor)<option value="{{ $counselor->id }}" {{ optional($currentCounselor)->id == $counselor->id ? 'selected' : '' }}>{{ $counselor->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="peer_id">Peer</label><select name="peer_id" id="peer_id" class="form-control"><option value="">Select a Peer</option>@foreach($peers as $peer)<option value="{{ $peer->id }}" {{ optional($currentPeer)->id == $peer->id ? 'selected' : '' }}>{{ $peer->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="peer_group_id">Peer Group</label><select name="peer_group_id" id="peer_group_id" class="form-control"><option value="">Select a Peer Group</option>@foreach($peerGroups as $peerGroup)<option value="{{ $peerGroup->id }}" {{ optional($currentPeerGroup)->id == $peerGroup->id ? 'selected' : '' }}>{{ $peerGroup->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="apartment_id">House / Apartment</label><select name="apartment_id" id="apartment_id" class="form-control"><option value="">Select House / Apartment</option>@foreach($apartments as $apartment)<option value="{{ $apartment->id }}" {{ optional($currentApartment)->id == $apartment->id ? 'selected' : '' }}>{{ optional($apartment->house)->house_name }} - {{ $apartment->apartment_number }} ({{ $apartment->clients()->active()->count() }}/{{ $apartment->capacity ?? 0 }})</option>@endforeach</select></div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">Submit Transition</button>
    <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const levelSelect = document.getElementById('new_loc');
        const groupSelect = document.getElementById('client_group_id');
        const groupOptions = groupSelect ? Array.from(groupSelect.options) : [];
        const filterGroupOptions = () => {
            if (!levelSelect || !groupSelect) return;
            const selectedLoc = String(levelSelect.value || '');
            groupOptions.forEach((option) => {
                const optionLoc = option.dataset.levelOfCareId;
                if (!optionLoc) return option.hidden = false;
                const shouldHide = optionLoc !== selectedLoc;
                option.hidden = shouldHide;
                if (shouldHide && option.selected) option.selected = false;
            });
        };
        const endDateInput = document.getElementById('end_loc_date');
        const startDateInput = document.getElementById('new_start_date');

        const formatDateLocal = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const previousDate = (dateValue) => {
            const [year, month, day] = String(dateValue).split('-').map(Number);
            const date = new Date(year, (month || 1) - 1, day || 1);
            date.setDate(date.getDate() - 1);
            return formatDateLocal(date);
        };

        const syncEndDateFromStartDate = () => {
            if (!endDateInput || !startDateInput) return;

            if (!startDateInput.value) {
                endDateInput.value = '';
                startDateInput.setCustomValidity('');
                return;
            }

            endDateInput.value = previousDate(startDateInput.value);
            startDateInput.setCustomValidity('');
        };

        levelSelect.addEventListener('change', filterGroupOptions);
        startDateInput?.addEventListener('change', syncEndDateFromStartDate);
        startDateInput?.addEventListener('input', syncEndDateFromStartDate);

        filterGroupOptions();
        syncEndDateFromStartDate();
    });
</script>
@stop
