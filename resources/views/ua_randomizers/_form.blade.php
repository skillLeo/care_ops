@include('partials.flash')

<form method="POST" action="{{ $action }}" id="ua-randomizer-form">
    @csrf
    @if(!empty($method))
        @method($method)
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="generated_for_date">Date</label>
                        <input type="date" class="form-control" id="generated_for_date" name="generated_for_date"
                            value="{{ old('generated_for_date', $randomizer?->generated_for_date?->toDateString() ?? now()->toDateString()) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="level_of_care_id">Level of Care</label>
                        <select class="form-control" id="level_of_care_id" name="level_of_care_id">
                            <option value="">All</option>
                            @foreach($levelOfCares as $level)
                                <option value="{{ $level->id }}"
                                    {{ (string) old('level_of_care_id', $randomizer->level_of_care_id ?? '') === (string) $level->id ? 'selected' : '' }}>
                                    {{ $level->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="house_id">House</label>
                        <select class="form-control" id="house_id" name="house_id">
                            <option value="">All</option>
                            @foreach($houses as $house)
                                <option value="{{ $house->id }}"
                                    {{ (string) old('house_id', $randomizer->house_id ?? '') === (string) $house->id ? 'selected' : '' }}>
                                    {{ $house->house_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="percentage">Percentage</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="percentage" name="percentage" min="1" max="100"
                                value="{{ old('percentage', $randomizer->percentage ?? 20) }}">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="total_clients">Total Clients</label>
                        <input type="number" class="form-control" id="total_clients" name="total_clients" readonly
                            value="{{ old('total_clients', $randomizer->total_clients ?? 0) }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="target_count">Target Count</label>
                        <input type="number" class="form-control" id="target_count" name="target_count" min="0"
                            value="{{ old('target_count', $randomizer->target_count ?? 0) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <input type="text" class="form-control" id="remarks" name="remarks"
                            value="{{ old('remarks', $randomizer->remarks ?? '') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-group w-100">
                        <button type="button" class="btn btn-secondary w-100" id="filter-btn">Filter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">Filtered Clients</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary" id="pick-remaining-btn">Pick Remaining Clients</button>
                            <button type="button" class="btn btn-primary" id="randomize-btn">Randomize</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0" id="clients-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th class="text-center"><span title="Times checked in the last 7 days">7d</span></th>
                                    <th class="text-center">Must Include</th>
                                    <th class="text-center">Must Exclude</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-muted text-center">Use Filter to load clients.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Selected Clients</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group" id="selected-clients-list">
                        <li class="list-group-item text-muted text-center">No clients selected yet.</li>
                    </ul>
                    <div class="alert alert-warning mt-3 d-none" role="alert" id="randomizer-warning"></div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">Save</button>
                <a href="{{ route('ua-randomizers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>

    <div id="selected-clients-inputs"></div>
</form>

@section('js')
@parent
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterButton = document.getElementById('filter-btn');
        const randomizeButton = document.getElementById('randomize-btn');
        const pickRemainingButton = document.getElementById('pick-remaining-btn');
        const generatedForDateInput = document.getElementById('generated_for_date');
        const levelOfCareSelect = document.getElementById('level_of_care_id');
        const houseSelect = document.getElementById('house_id');
        const totalClientsInput = document.getElementById('total_clients');
        const percentageInput = document.getElementById('percentage');
        const targetCountInput = document.getElementById('target_count');
        const clientsTableBody = document.querySelector('#clients-table tbody');
        const selectedList = document.getElementById('selected-clients-list');
        const selectedInputsContainer = document.getElementById('selected-clients-inputs');
        const warningBox = document.getElementById('randomizer-warning');

        const initialClients = @json($initialClients ?? []);
        const initialSelected = new Set(@json($initialSelected ?? []));
        const filterEndpoint = "{{ route('ua-randomizers.filter') }}";
        const excludeRandomizerId = @json($randomizer->id ?? null);
        const shouldRecalculateOnLoad = @json($randomizer === null);

        let clients = [];
        let selectedClientIds = new Set(initialSelected);

        const updateTargetCount = () => {
            const total = Number(totalClientsInput.value || 0);
            const percentage = Number(percentageInput.value || 0);
            const calculated = Math.ceil(total * (percentage / 100));
            targetCountInput.value = Number.isFinite(calculated) ? calculated : 0;
        };

        const updateSelectedInputs = () => {
            selectedInputsContainer.innerHTML = '';
            Array.from(selectedClientIds).forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_clients[]';
                input.value = id;
                selectedInputsContainer.appendChild(input);
            });
        };

        const renderSelectedList = () => {
            selectedList.innerHTML = '';
            const selectedClients = clients.filter((client) => selectedClientIds.has(client.id));

            if (!selectedClients.length) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'list-group-item text-muted text-center';
                emptyItem.textContent = 'No clients selected yet.';
                selectedList.appendChild(emptyItem);
                return;
            }

            selectedClients.forEach((client) => {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';

                const nameSpan = document.createElement('span');
                nameSpan.textContent = client.name;

                const buttonGroup = document.createElement('div');
                buttonGroup.className = 'btn-group btn-group-sm';

                const rerollButton = document.createElement('button');
                rerollButton.type = 'button';
                rerollButton.className = 'btn btn-outline-primary';
                rerollButton.title = 'Re-randomize this client';
                rerollButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
                rerollButton.disabled = client.mustInclude;
                rerollButton.addEventListener('click', () => rerollClient(client.id));

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger';
                removeButton.title = 'Remove';
                removeButton.innerHTML = '<i class="fas fa-times"></i>';
                removeButton.addEventListener('click', () => {
                    selectedClientIds.delete(client.id);
                    renderSelectedList();
                    updateSelectedInputs();
                });

                buttonGroup.appendChild(rerollButton);
                buttonGroup.appendChild(removeButton);

                item.appendChild(nameSpan);
                item.appendChild(buttonGroup);

                selectedList.appendChild(item);
            });
        };

        const renderClientsTable = () => {
            clientsTableBody.innerHTML = '';

            if (!clients.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.className = 'text-muted text-center';
                cell.textContent = 'No clients found for the selected filters.';
                row.appendChild(cell);
                clientsTableBody.appendChild(row);
                return;
            }

            clients.forEach((client) => {
                const row = document.createElement('tr');

                const nameCell = document.createElement('td');
                nameCell.textContent = client.name;

                const last7Cell = document.createElement('td');
                last7Cell.className = 'text-center';
                last7Cell.textContent = client.last_7_count ?? 0;

                const includeCell = document.createElement('td');
                includeCell.className = 'text-center';
                const includeCheckbox = document.createElement('input');
                includeCheckbox.type = 'checkbox';
                includeCheckbox.checked = client.mustInclude;
                includeCheckbox.addEventListener('change', () => {
                    client.mustInclude = includeCheckbox.checked;
                    if (client.mustInclude) {
                        client.mustExclude = false;
                        selectedClientIds.add(client.id);
                    }
                    renderClientsTable();
                    renderSelectedList();
                    updateSelectedInputs();
                });
                includeCell.appendChild(includeCheckbox);

                const excludeCell = document.createElement('td');
                excludeCell.className = 'text-center';
                const excludeCheckbox = document.createElement('input');
                excludeCheckbox.type = 'checkbox';
                excludeCheckbox.checked = client.mustExclude;
                excludeCheckbox.addEventListener('change', () => {
                    client.mustExclude = excludeCheckbox.checked;
                    if (client.mustExclude) {
                        client.mustInclude = false;
                        selectedClientIds.delete(client.id);
                    }
                    renderClientsTable();
                    renderSelectedList();
                    updateSelectedInputs();
                });
                excludeCell.appendChild(excludeCheckbox);

                const actionCell = document.createElement('td');
                actionCell.className = 'text-center';
                const actionButton = document.createElement('button');
                actionButton.type = 'button';
                actionButton.className = selectedClientIds.has(client.id) ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-success';
                actionButton.innerHTML = selectedClientIds.has(client.id)
                    ? '<i class="fas fa-minus"></i>'
                    : '<i class="fas fa-plus"></i>';
                actionButton.addEventListener('click', () => {
                    if (selectedClientIds.has(client.id)) {
                        selectedClientIds.delete(client.id);
                    } else {
                        selectedClientIds.add(client.id);
                        client.mustExclude = false;
                    }
                    renderClientsTable();
                    renderSelectedList();
                    updateSelectedInputs();
                });
                actionCell.appendChild(actionButton);

                row.appendChild(nameCell);
                row.appendChild(last7Cell);
                row.appendChild(includeCell);
                row.appendChild(excludeCell);
                row.appendChild(actionCell);
                clientsTableBody.appendChild(row);
            });
        };

        const buildPayload = (entries, shouldRecalculateTarget) => {
            clients = entries.map((client) => {
                const isSelected = selectedClientIds.has(client.id);
                const last7Count = Number(client.last_7_count || 0);
                return {
                    ...client,
                    mustInclude: false,
                    mustExclude: last7Count > 0 && !isSelected,
                };
            });

            totalClientsInput.value = clients.length;
            if (shouldRecalculateTarget) {
                updateTargetCount();
            }
            renderClientsTable();
            renderSelectedList();
            updateSelectedInputs();
        };

        const randomizeClients = () => {
            warningBox.classList.add('d-none');
            warningBox.textContent = '';

            const mustInclude = clients.filter((client) => client.mustInclude).map((client) => client.id);
            const available = clients
                .filter((client) => !client.mustExclude && !mustInclude.includes(client.id))
                .map((client) => client.id);

            const targetCount = Number(targetCountInput.value || 0);
            const selected = new Set(mustInclude);

            if (mustInclude.length < targetCount) {
                const needed = targetCount - mustInclude.length;
                const shuffled = available.sort(() => 0.5 - Math.random());
                const picked = shuffled.slice(0, needed);
                picked.forEach((id) => selected.add(id));

                if (available.length < needed) {
                    warningBox.textContent = `Only ${available.length} eligible clients available. Picked all eligible clients to meet the target of ${targetCount}.`;
                    warningBox.classList.remove('d-none');
                }
            }

            selectedClientIds = selected;
            renderSelectedList();
            renderClientsTable();
            updateSelectedInputs();
        };

        const pickRemainingClients = () => {
            warningBox.classList.add('d-none');
            warningBox.textContent = '';

            const remainingIds = clients
                .filter((client) => !client.mustExclude)
                .map((client) => client.id);

            selectedClientIds = new Set(remainingIds);
            renderSelectedList();
            renderClientsTable();
            updateSelectedInputs();
        };

        const rerollClient = (clientId) => {
            const targetCount = Number(targetCountInput.value || 0);
            if (targetCount <= 0) {
                return;
            }

            const mustIncludeIds = clients.filter((client) => client.mustInclude).map((client) => client.id);
            const available = clients
                .filter((client) => !client.mustExclude && !selectedClientIds.has(client.id) && !mustIncludeIds.includes(client.id))
                .map((client) => client.id);

            if (!available.length) {
                warningBox.textContent = 'No eligible clients available to replace this selection.';
                warningBox.classList.remove('d-none');
                return;
            }

            const replacement = available[Math.floor(Math.random() * available.length)];
            selectedClientIds.delete(clientId);
            selectedClientIds.add(replacement);
            warningBox.classList.add('d-none');
            warningBox.textContent = '';
            renderSelectedList();
            renderClientsTable();
            updateSelectedInputs();
        };

        const fetchClients = () => {
            const payload = {
                generated_for_date: generatedForDateInput.value,
                level_of_care_id: levelOfCareSelect.value || null,
                house_id: houseSelect.value || null,
                exclude_randomizer_id: excludeRandomizerId,
            };

            return fetch(filterEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
                .then((response) => response.json())
                .then((data) => {
                    selectedClientIds = new Set(selectedClientIds);
                    buildPayload(data.clients || [], true);
                })
                .catch(() => {
                    clients = [];
                    renderClientsTable();
                });
        };

        filterButton.addEventListener('click', () => {
            fetchClients();
        });

        randomizeButton.addEventListener('click', () => {
            randomizeClients();
        });

        pickRemainingButton.addEventListener('click', () => {
            pickRemainingClients();
        });

        percentageInput.addEventListener('input', updateTargetCount);

        if (initialClients.length) {
            buildPayload(initialClients, shouldRecalculateOnLoad);
            renderSelectedList();
            updateSelectedInputs();
        }
    });
</script>
@endsection
