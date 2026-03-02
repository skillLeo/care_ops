@extends('adminlte::page')

@section('title', 'Readmit - ' . $client->full_name)

@section('content_header')
    <h1>Readmit Client: {{ $client->full_name }}</h1>
@stop

@section('content')
@include('clients.partials.info-card')
<form method="POST" action="{{ route('clients.readmit.submit', $client->id) }}">
    @csrf

    <div class="card mb-3">
        <div class="card-body">
            <h4>Previous Level of Cares & Hospitalizations</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Level of Care History</h6>
                    @if ($levelHistory->isNotEmpty())
                        <ul class="list-unstyled mb-0">
                            @foreach ($levelHistory as $history)
                                <li>
                                    {{ $history->levelOfCare?->display_name ?? '-' }}
                                    ({{ \Carbon\Carbon::parse($history->start_date)->format('m/d/Y') }} -
                                    {{ $history->end_date ? \Carbon\Carbon::parse($history->end_date)->format('m/d/Y') : 'Ongoing' }})
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No level of care history.</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Hospitalizations</h6>
                    @if ($hospitalizations->isNotEmpty())
                        <ul class="list-unstyled mb-0">
                            @foreach ($hospitalizations as $hospitalization)
                                <li>
                                    {{ ucfirst($hospitalization->type ?? 'Hospitalization') }}
                                    ({{ \Carbon\Carbon::parse($hospitalization->start_date)->format('m/d/Y') }} -
                                    {{ $hospitalization->end_date ? \Carbon\Carbon::parse($hospitalization->end_date)->format('m/d/Y') : 'Ongoing' }})
                                    @if ($hospitalization->facility)
                                        - {{ $hospitalization->facility->name }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No hospitalization history.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h4>Readmission Level of Care</h4>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Level of Care</label>
                    <select name="new_loc" id="new_loc" class="form-control" required>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Readmission Date</label>
                    <input type="date" name="new_loc_start_date" id="new_loc_start_date" class="form-control" required>
                </div>
                <div class="col-md-4 form-group">
                    <label for="client_group_id">Group</label>
                    <select name="client_group_id" id="client_group_id" class="form-control">
                        <option value="">Select a Group</option>
                        @foreach($clientGroups as $group)
                            <option value="{{ $group->id }}" data-level-of-care-id="{{ $group->level_of_care_id }}" {{ old('client_group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h4>Groups & Care Team</h4>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="counselor_id">Counselor</label>
                    <select name="counselor_id" id="counselor_id" class="form-control">
                        <option value="">Select a Counselor</option>
                        @foreach($counselors as $counselor)
                            <option value="{{ $counselor->id }}" {{ optional($client->counselor)->id == $counselor->id ? 'selected' : '' }}>
                                {{ $counselor->name }} ({{ $counselor->levelOfCare?->display_name ?? '-' }} - {{ $counselor->clients_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="peer_id">Peer</label>
                    <select name="peer_id" id="peer_id" class="form-control">
                        <option value="">Select a Peer</option>
                        @foreach($peers as $peer)
                            <option value="{{ $peer->id }}" {{ optional($client->peer)->id == $peer->id ? 'selected' : '' }}>
                                {{ $peer->name }} ({{ $peer->levelOfCare?->display_name ?? '-' }} - {{ $peer->peer_clients_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="peer_group_id">Peer Group</label>
                    <select name="peer_group_id" id="peer_group_id" class="form-control">
                        <option value="">Select a Peer Group</option>
                        @foreach($peerGroups as $peerGroup)
                            <option value="{{ $peerGroup->id }}" {{ optional($client->peerGroup)->id == $peerGroup->id ? 'selected' : '' }}>
                                {{ $peerGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h4>Authorization</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="auth_option" id="auth_option_new" value="new" checked>
                <label class="form-check-label" for="auth_option_new">Start a new authorization</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="auth_option" id="auth_option_existing" value="existing">
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
                                        <input type="radio" name="authorization_id" value="{{ $authorization->id }}">
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

    <button type="submit" class="btn btn-success">Readmit</button>
    <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@stop

@section('js')
    <script>
        const authOptionNew = document.getElementById('auth_option_new');
        const authOptionExisting = document.getElementById('auth_option_existing');
        const existingSection = document.getElementById('existing-auth-section');
        const levelSelect = document.getElementById('new_loc');
        const startDateInput = document.getElementById('new_loc_start_date');
        const authRows = Array.from(document.querySelectorAll('.auth-row'));
        const noAuthMessage = document.getElementById('no-auth-message');
        const lastLevelId = @json($lastLevelId);
        const lastServiceDate = @json($lastServiceDate);
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
            if (!value) {
                return null;
            }

            const parts = value.split('-').map(Number);
            if (parts.length !== 3) {
                return null;
            }

            return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        }

        function applyAuthDefaults() {
            if (!lastLevelId) {
                authOptionExisting.disabled = false;
                authOptionNew.disabled = false;
                return;
            }

            const selectedLoc = levelSelect.value;
            const returnDate = parseDate(startDateInput.value);
            const lastService = parseDate(lastServiceDate);
            const sameLoc = selectedLoc === String(lastLevelId);
            const diffDays = returnDate && lastService
                ? Math.floor((returnDate - lastService) / (1000 * 60 * 60 * 24))
                : null;

            if (!sameLoc) {
                authOptionNew.checked = true;
                authOptionExisting.checked = false;
                authOptionExisting.disabled = true;
                authOptionNew.disabled = false;
            } else if (diffDays !== null && diffDays >= 30) {
                authOptionNew.checked = true;
                authOptionExisting.checked = false;
                authOptionExisting.disabled = true;
                authOptionNew.disabled = false;
            } else if (sameLoc) {
                authOptionExisting.checked = true;
                authOptionNew.checked = false;
                authOptionExisting.disabled = false;
                authOptionNew.disabled = false;
            }

            toggleAuthSection();
        }

        function filterAuthRows() {
            const selectedLoc = levelSelect.value;
            let visibleCount = 0;

            authRows.forEach(row => {
                const matches = row.dataset.loc === selectedLoc;
                row.classList.toggle('d-none', !matches);

                if (matches) {
                    visibleCount++;
                }
            });

            noAuthMessage.classList.toggle('d-none', visibleCount !== 0);
        }

        function filterGroupOptions() {
            if (!groupSelect || !levelSelect) {
                return;
            }

            const selectedLoc = String(levelSelect.value || '');
            groupOptions.forEach((option) => {
                const optionLoc = option.dataset.levelOfCareId;
                if (!optionLoc) {
                    option.hidden = false;
                    return;
                }

                const shouldHide = optionLoc !== selectedLoc;
                option.hidden = shouldHide;

                if (shouldHide && option.selected) {
                    option.selected = false;
                }
            });
        }

        authOptionNew.addEventListener('change', toggleAuthSection);
        authOptionExisting.addEventListener('change', toggleAuthSection);
        levelSelect.addEventListener('change', () => {
            applyAuthDefaults();
            filterGroupOptions();
        });
        startDateInput.addEventListener('change', applyAuthDefaults);

        document.addEventListener('DOMContentLoaded', () => {
            applyAuthDefaults();
            filterGroupOptions();
        });
    </script>
@stop
