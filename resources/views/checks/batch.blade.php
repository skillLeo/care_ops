@extends('adminlte::page')

@section('title', 'Batch Check Import')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Batch Import: Check #{{ $check->check_number }}</h1>
        <a href="{{ route('checks.show', $check) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Check
        </a>
    </div>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-header"><strong>Upload Statement</strong></div>
        <div class="card-body">
            <form id="scan-form" class="d-flex flex-wrap align-items-end">
                <div class="form-group mb-0 mr-3">
                    <label for="attachment">Choose Check PDF</label>
                    <select id="attachment" name="attachment" class="form-control">
                        <option value="">-- Select attachment --</option>
                        @foreach ($check->attachments ?? [] as $file)
                            <option value="{{ $file }}">{{ $file }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        <a id="attachment-download" href="#" target="_blank" rel="noopener">Download selected attachment</a>
                    </small>
                </div>
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-file-upload"></i> Scan Statement
                </button>
                <div id="scan-status" class="text-muted small"></div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Totals</strong></div>
        <div class="card-body row">
            <div class="col-md-4">
                <strong>PDF Total Paid</strong>
                <div id="pdf-total-paid">$0.00</div>
            </div>
            <div class="col-md-4">
                <strong>PDF Total Billed</strong>
                <div id="pdf-total-billed">$0.00</div>
            </div>
            <div class="col-md-4">
                <strong>Selected Claims Total</strong>
                <div id="system-total-paid">$0.00</div>
            </div>
        </div>
    </div>

    <div id="batch-results"></div>

    <div class="mt-3 d-flex justify-content-end">
        <button type="button" id="commit-button" class="btn btn-success" disabled>
            <i class="fas fa-save"></i> Apply Updates
        </button>
    </div>
@stop

@section('css')
    <style>
        .batch-status-full {
            border-left: 5px solid #28a745;
        }
        .batch-status-partial,
        .batch-status-none {
            border-left: 5px solid #dc3545;
        }
        .batch-line-matched {
            background-color: #e7f7ec;
        }
    </style>
@stop

@section('js')
<script>
const scanForm = document.getElementById('scan-form');
const scanStatus = document.getElementById('scan-status');
const resultsContainer = document.getElementById('batch-results');
const commitButton = document.getElementById('commit-button');
const attachmentSelect = document.getElementById('attachment');
const attachmentDownload = document.getElementById('attachment-download');

const scanUrl = "{{ route('checks.batch.scan', $check) }}";
const commitUrl = "{{ route('checks.batch.commit', $check) }}";
const checkShowUrl = "{{ route('checks.show', $check) }}";
const resolveClientUrl = "{{ route('checks.batch.resolve-client', $check) }}";
const csrfToken = "{{ csrf_token() }}";
const attachmentBaseUrl = "{{ route('checks.attachments.download', [$check, 'filename']) }}";

let batchData = null;
let groupState = [];

const formatCurrency = (value) => `$${Number(value || 0).toFixed(2)}`;

const updateAttachmentLink = () => {
    const value = attachmentSelect.value;
    if (!value) {
        attachmentDownload.href = '#';
        attachmentDownload.classList.add('text-muted');
        return;
    }
    attachmentDownload.href = attachmentBaseUrl.replace('filename', encodeURIComponent(value));
    attachmentDownload.classList.remove('text-muted');
};

const normalizeDate = (value) => {
    if (!value) {
        return '';
    }
    const parts = value.split('/');
    if (parts.length !== 3) {
        return value;
    }
    return `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
};

const formatServiceCode = (code, modifier) => {
    const normalized = (code || '').toUpperCase().trim();
    const normalizedModifier = (modifier || '').toUpperCase().trim();
    if (normalizedModifier && normalized && !normalized.includes('-')) {
        return `${normalized}-${normalizedModifier}`;
    }
    return normalized;
};

const lineMatches = (pdfLine, claimLine) => {
    if (!claimLine) {
        return false;
    }
    const pdfDate = normalizeDate(pdfLine.service_date);
    const claimDate = normalizeDate(claimLine.service_date);
    if (pdfDate && claimDate && pdfDate !== claimDate) {
        return false;
    }
    const pdfCode = formatServiceCode(pdfLine.service_code, pdfLine.modifier);
    const claimCode = formatServiceCode(claimLine.service_code, null);
    if (pdfCode && claimCode && pdfCode !== claimCode) {
        return false;
    }
    const pdfUnits = parseFloat(pdfLine.units || 0);
    const claimUnits = parseFloat(claimLine.units || 0);
    if (pdfUnits && Math.abs(pdfUnits - claimUnits) > 0.01) {
        return false;
    }
    const pdfBilled = parseFloat(pdfLine.billed || 0);
    const claimBilled = parseFloat(claimLine.billed_amount || 0);
    if (pdfBilled && Math.abs(pdfBilled - claimBilled) > 0.01) {
        return false;
    }
    return true;
};

const parseDateValue = (value) => {
    if (!value) {
        return null;
    }
    const parsed = new Date(normalizeDate(value));
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const buildClaimSummary = (claim) => {
    const dates = (claim.lines || [])
        .map((line) => parseDateValue(line.service_date))
        .filter(Boolean)
        .sort((a, b) => a - b);

    const totalBilled = (claim.lines || []).reduce((sum, line) => (
        sum + parseFloat(line.billed_amount || 0)
    ), 0);

    return {
        totalBilled,
        minDate: dates[0] || null,
        maxDate: dates[dates.length - 1] || null,
    };
};

const formatDateRange = (summary) => {
    if (!summary?.minDate || !summary?.maxDate) {
        return 'N/A';
    }
    const format = (date) => date.toLocaleDateString('en-US');
    return `${format(summary.minDate)} - ${format(summary.maxDate)}`;
};

const pdfSummary = (pdfLines) => {
    const dates = (pdfLines || [])
        .map((line) => parseDateValue(line.service_date))
        .filter(Boolean)
        .sort((a, b) => a - b);

    const totalBilled = (pdfLines || []).reduce((sum, line) => (
        sum + parseFloat(line.billed || 0)
    ), 0);

    return {
        totalBilled,
        minDate: dates[0] || null,
        maxDate: dates[dates.length - 1] || null,
    };
};

const autoMatchLinesForGroup = (pdfLines, claim) => {
    const lines = claim?.lines || [];
    const used = new Set();
    const selections = {};

    pdfLines.forEach((pdfLine, index) => {
        for (const line of lines) {
            if (used.has(line.id)) {
                continue;
            }
            if (lineMatches(pdfLine, line)) {
                selections[index] = line.id;
                used.add(line.id);
                break;
            }
        }
    });

    return selections;
};

const buildTotals = (rows) => {
    const safeRows = rows.filter((row) => row && typeof row === 'object');
    const totals = {
        billed: 0,
        paid: 0,
        lines: safeRows.length,
    };

    safeRows.forEach((row) => {
        totals.billed += parseFloat(row.billed || 0);
        totals.paid += parseFloat(row.paid || 0);
    });

    return totals;
};

const buildGroupsFromRows = (rows) => {
    const grouped = {};

    const safeRows = rows.filter((row) => row && typeof row === 'object');

    safeRows.forEach((row) => {
        const claimKey = (row.claim_number || '').trim().toUpperCase();
        const nameKey = (row.name || '').trim().toUpperCase();
        const mrnKey = (row.mrn || '').trim().toUpperCase();
        let key = `${claimKey}|${nameKey}|${mrnKey}`;
        if (!claimKey && (nameKey || mrnKey)) {
            key = `unknown|${nameKey}|${mrnKey}`;
        }
        if (!claimKey && !nameKey && !mrnKey) {
            key = 'unknown';
        }
        if (!grouped[key]) {
            grouped[key] = [];
        }
        grouped[key].push(row);
    });

    return Object.entries(grouped).map(([groupKey, lines]) => ({
        claim_number: lines.find((line) => (line?.claim_number || '').trim())?.claim_number || groupKey,
        name: lines[0]?.name || '',
        mrn: lines[0]?.mrn || '',
        pdf_lines: lines.filter((line) => line && typeof line === 'object'),
        pdf_totals: lines.reduce((totals, line) => {
            if (!line || typeof line !== 'object') {
                return totals;
            }
            totals.billed += parseFloat(line.billed || 0);
            totals.paid += parseFloat(line.paid || 0);
            return totals;
        }, { billed: 0, paid: 0 }),
        client: null,
        claims: [],
    }));
};

const hydrateGroupClaims = async (group) => {
    if (!group?.name && !group?.mrn) {
        return;
    }
    const params = new URLSearchParams();
    if (group.name) {
        params.set('name', group.name);
    }
    if (group.mrn) {
        params.set('mrn', group.mrn);
    }
    const response = await fetch(`${resolveClientUrl}?${params.toString()}`);
    const data = await response.json();
    group.client = data.client || null;
    group.claims = (data.claims || []).map((claim) => ({
        ...claim,
        client_name: data.client?.name || '',
    }));
};

const autoSelectClaimForGroup = (group) => {
    if (!group?.claims?.length || !group.pdf_lines?.length) {
        return { selectedClaimId: null, lineSelections: {} };
    }

    const pdfInfo = pdfSummary(group.pdf_lines);

    for (const claim of group.claims) {
        const claimInfo = buildClaimSummary(claim);
        if (pdfInfo.minDate && claimInfo.minDate && pdfInfo.minDate < claimInfo.minDate) {
            continue;
        }
        if (pdfInfo.maxDate && claimInfo.maxDate && pdfInfo.maxDate > claimInfo.maxDate) {
            continue;
        }
        if (Math.abs(pdfInfo.totalBilled - claimInfo.totalBilled) > 0.01) {
            continue;
        }

        const selections = autoMatchLinesForGroup(group.pdf_lines, claim);
        if (Object.keys(selections).length === group.pdf_lines.length) {
            return { selectedClaimId: claim.id, lineSelections: selections };
        }
    }

    return { selectedClaimId: null, lineSelections: {} };
};

const updateTotals = () => {
    if (!batchData?.totals) {
        return;
    }
    const pdfPaid = batchData.totals?.paid || 0;
    const pdfBilled = batchData.totals?.billed || 0;

    document.getElementById('pdf-total-paid').textContent = formatCurrency(pdfPaid);
    document.getElementById('pdf-total-billed').textContent = formatCurrency(pdfBilled);

    let systemTotal = 0;
    batchData.groups.forEach((group, groupIndex) => {
        const state = groupState[groupIndex] || { lineSelections: {} };
        const safeLines = (group.pdf_lines || []).filter((line) => line && typeof line === 'object');
        if (!state) {
            return;
        }
        safeLines.forEach((line, lineIndex) => {
            if (state.lineSelections[lineIndex]) {
                systemTotal += parseFloat(line.paid || 0);
            }
        });
    });
    document.getElementById('system-total-paid').textContent = formatCurrency(systemTotal);
};

const calculateGroupStatus = (groupIndex) => {
    const group = batchData.groups[groupIndex];
    const selections = groupState[groupIndex]?.lineSelections || {};
    const matchedCount = Object.keys(selections).length;
    const safeLines = (group?.pdf_lines || []).filter((line) => line && typeof line === 'object');

    if (matchedCount === 0) {
        return 'none';
    }
    if (matchedCount === safeLines.length) {
        return 'full';
    }
    return 'partial';
};

const buildClaimLabel = (claim) => {
    const summary = buildClaimSummary(claim);
    const clientName = claim.client_name || '';
    return `${clientName ? clientName + ' - ' : ''}${formatDateRange(summary)} | $${summary.totalBilled.toFixed(2)} (${claim.status})`;
};

const renderGroups = () => {
    resultsContainer.innerHTML = '';
    commitButton.disabled = !batchData?.groups?.length;

    (batchData?.groups || []).forEach((group, index) => {
        const state = groupState[index] || { selectedClaimId: null, selectedClientId: null, lineSelections: {} };
        const claim = group.claims.find((item) => item.id === state.selectedClaimId);
        const statusValue = calculateGroupStatus(index);
        const statusClass = `batch-status-${statusValue}`;
        const collapseId = `group-${index}`;
        const unmatchedLines = (claim?.lines || []).filter((line) => (
            !Object.values(state.lineSelections).includes(line.id)
        ));
        const systemTotals = (claim?.lines || []).reduce((totals, line) => {
            if (Object.values(state.lineSelections).includes(line.id)) {
                totals.billed += parseFloat(line.billed_amount || 0);
                totals.processed += parseFloat(line.processed_amount || 0);
            }
            return totals;
        }, { billed: 0, processed: 0 });

        const card = document.createElement('div');
        card.className = `card mb-3 ${statusClass}`;

        card.innerHTML = `
            <div class="card-header">
                <button class="btn btn-link text-left w-100 collapsed" type="button" data-toggle="collapse" data-target="#${collapseId}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${group.name || 'Unknown Client'}</strong>
                            <span class="text-muted">MRN: ${group.mrn || 'N/A'} | Claim#: ${group.claim_number || 'N/A'}</span>
                        </div>
                        <span class="badge badge-${statusValue === 'full' ? 'success' : 'danger'} text-uppercase">
                            ${statusValue}
                        </span>
                    </div>
                </button>
            </div>
            <div id="${collapseId}" class="collapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>System Client</label>
                                        <select class="form-control form-control-sm client-input" data-group-index="${index}">
                                            <option value="">-- Select client --</option>
                                            ${group.client ? `
                                                <option value="${group.client.id}" ${state.selectedClientId === group.client.id ? 'selected' : ''}>
                                                    ${group.client.name} (${group.client.mrn})
                                                </option>
                                            ` : ''}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>System Claim</label>
                                        <select class="form-control form-control-sm claim-input" data-group-index="${index}">
                                            <option value="">-- Select a claim --</option>
                                            ${group.claims.map((item) => `
                                                <option value="${item.id}" ${claim?.id === item.id ? 'selected' : ''}>
                                                    ${buildClaimLabel(item)}
                                                </option>
                                            `).join('')}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th colspan="6">PDF Lines of Service</th>
                                                </tr>
                                                <tr>
                                                    <th>Service Date</th>
                                                    <th>Service Code</th>
                                                    <th>Modifier</th>
                                                    <th>Units</th>
                                                    <th>Billed</th>
                                                    <th>Paid</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${(group.pdf_lines || []).filter((line) => line && typeof line === 'object').map((line) => `
                                                    <tr>
                                                        <td>${line.service_date || ''}</td>
                                                        <td>${line.service_code || ''}</td>
                                                        <td>${line.modifier || ''}</td>
                                                        <td>${line.units || ''}</td>
                                                        <td>${formatCurrency(line.billed)}</td>
                                                        <td>${formatCurrency(line.paid)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th colspan="4">Matched System Lines</th>
                                                </tr>
                                                <tr>
                                                    <th>Service Date</th>
                                                    <th>Service Code</th>
                                                    <th>Units</th>
                                                    <th>Billed</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${(group.pdf_lines || []).filter((line) => line && typeof line === 'object').map((line, lineIndex) => {
                                                    const selectedLineId = state.lineSelections[lineIndex];
                                                    const selectedLabel = (claim?.lines || []).find((claimLine) => claimLine.id === selectedLineId);
                                                    return `
                                                        <tr>
                                                            <td>${selectedLabel?.service_date || ''}</td>
                                                            <td>${selectedLabel?.service_code || ''}</td>
                                                            <td>${selectedLabel?.units || ''}</td>
                                                            <td>${selectedLabel ? formatCurrency(selectedLabel.billed_amount) : ''}</td>
                                                        </tr>
                                                    `;
                                                }).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="text-uppercase text-muted mb-2">PDF Totals</h6>
                                        <div class="d-flex justify-content-between">
                                            <span>Billed</span>
                                            <strong>${formatCurrency(group.pdf_totals?.billed)}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Paid</span>
                                            <strong>${formatCurrency(group.pdf_totals?.paid)}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="text-uppercase text-muted mb-2">System Totals</h6>
                                        <div class="d-flex justify-content-between">
                                            <span>Billed</span>
                                            <strong>${formatCurrency(systemTotals.billed)}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Processed</span>
                                            <strong>${formatCurrency(systemTotals.processed)}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th colspan="4">Unmatched System Lines</th>
                                        </tr>
                                        <tr>
                                            <th>Service Date</th>
                                            <th>Service Code</th>
                                            <th>Billed</th>
                                            <th>Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${unmatchedLines.map((claimLine) => `
                                            <tr>
                                                <td>${claimLine.service_date || ''}</td>
                                                <td>${claimLine.service_code || ''}</td>
                                                <td>${formatCurrency(claimLine.billed_amount)}</td>
                                                <td>${formatCurrency(claimLine.processed_amount)}</td>
                                            </tr>
                                        `).join('')}
                                        ${unmatchedLines.length ? '' : '<tr><td colspan="4" class="text-muted">No unmatched lines.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        resultsContainer.appendChild(card);
    });

    attachListeners();
    updateTotals();
};

const attachListeners = () => {
    document.querySelectorAll('.client-input').forEach((input) => {
        input.addEventListener('change', (event) => {
            const groupIndex = Number(event.target.dataset.groupIndex);
            groupState[groupIndex].selectedClientId = event.target.value
                ? Number(event.target.value)
                : null;
        });
    });

    document.querySelectorAll('.claim-input').forEach((input) => {
        input.addEventListener('change', (event) => {
            const groupIndex = Number(event.target.dataset.groupIndex);
            const selectedId = event.target.value ? Number(event.target.value) : null;
            groupState[groupIndex].selectedClaimId = selectedId;
            const claim = batchData.groups[groupIndex].claims.find((item) => item.id === selectedId);
            groupState[groupIndex].lineSelections = autoMatchLinesForGroup(batchData.groups[groupIndex].pdf_lines, claim);
            renderGroups();
        });
    });

};

scanForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(scanForm);
    scanStatus.textContent = 'Scanning...';

    try {
        const response = await fetch(scanUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });

        const text = await response.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (error) {
            throw new Error('Scan failed. The server returned an invalid response.');
        }
        if (!response.ok) {
            const message = data.details ? `${data.message} ${data.details}` : data.message;
            throw new Error(message || 'Scan failed');
        }

        const rows = Array.isArray(data.rows)
            ? data.rows
            : (Array.isArray(data.raw_rows) ? data.raw_rows : []);
        batchData = {
            rows,
            raw_rows: rows,
            groups: buildGroupsFromRows(rows),
            totals: buildTotals(rows),
        };

        scanStatus.textContent = 'Matching claims...';
        await Promise.all(batchData.groups.map((group) => hydrateGroupClaims(group)));

        groupState = batchData.groups.map((group) => {
            const selection = autoSelectClaimForGroup(group);
            return {
                ...selection,
                selectedClientId: group.client?.id || null,
            };
        });

        if (batchData.groups.length) {
            renderGroups();
        } else {
            resultsContainer.innerHTML = '';
            commitButton.disabled = true;
        }
        const rowCount = rows.length;
        scanStatus.textContent = `Loaded ${rowCount} lines.`;
    } catch (error) {
        scanStatus.textContent = error.message;
    }
});

attachmentSelect.addEventListener('change', updateAttachmentLink);
updateAttachmentLink();

commitButton.addEventListener('click', async () => {
    if (!batchData) {
        return;
    }

    commitButton.disabled = true;
    const payload = {
        groups: batchData.groups.map((group, groupIndex) => ({
            claim_id: groupState[groupIndex].selectedClaimId,
            lines: group.pdf_lines.map((line, lineIndex) => ({
                line_id: groupState[groupIndex].lineSelections[lineIndex] || null,
                paid: line.paid,
                billed: line.billed,
            })),
        })),
    };

    try {
        const response = await fetch(commitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Update failed');
        }
        scanStatus.textContent = data.message || 'Updates applied.';
        window.location.href = checkShowUrl;
    } catch (error) {
        scanStatus.textContent = error.message;
    } finally {
        commitButton.disabled = false;
    }
});
</script>
@stop
