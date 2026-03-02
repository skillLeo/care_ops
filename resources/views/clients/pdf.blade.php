<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Profile</title>
    <style>
        @include('partials.pdf-letterhead-css')

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 6px;
        }

        h2 {
            font-size: 14px;
            margin: 16px 0 6px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }

        .meta {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .meta-left,
        .meta-right {
            display: table-cell;
            vertical-align: top;
        }

        .meta-right {
            text-align: right;
        }

        .meta-right img {
            height: 120px;
            width: auto;
            border: 1px solid #ddd;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .grid td {
            padding: 4px 6px;
            border: 1px solid #e2e2e2;
            vertical-align: top;
        }

        .label {
            color: #555;
            font-weight: bold;
            width: 25%;
        }

        .section {
            margin-bottom: 12px;
        }

        table.history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.history th,
        table.history td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        table.history th {
            background: #f3f3f3;
        }
    </style>
</head>
<body>
    <div class="meta">
        <div class="meta-left">
            {{-- <h1>{{ $client->last_name }}, {{ $client->first_name }}</h1> --}}
            <h1>S&B Behavioral Health Care LLC</h1>
            <div>Client Profile</div>
        </div>
        <div class="meta-right">
            @if ($profilePhotoPath)
                <img src="file://{{ $profilePhotoPath }}" alt="Profile photo">
            @endif
        </div>
    </div>

    <h2>Personal Information</h2>
    <table class="grid section">
        <tr>
            <td class="label">First Name</td>
            <td>{{ $client->first_name }}</td>
            <td class="label">Last Name</td>
            <td>{{ $client->last_name }}</td>
        </tr>
        <tr>
            <td class="label">Date of Birth</td>
            <td>{{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}</td>
            <td class="label">Gender</td>
            <td>{{ ucfirst($client->gender) }}</td>
        </tr>
        <tr>
            <td class="label">MRN</td>
            <td>{{ $client->mrn }}</td>
            <td class="label"></td>
            <td></td>
        </tr>
    </table>

    <h2>Redetermination Details</h2>
    <table class="grid section">
        <tr>
            <td class="label">Eligibility</td>
            <td>{{ $client->eligibility ? ucwords(str_replace('_', ' ', $client->eligibility)) : '-' }}</td>
            <td class="label">Redetermination Date</td>
            <td>{{ $client->redetermination_date ? \Carbon\Carbon::parse($client->redetermination_date)->format('m/d/Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Last Checked Date</td>
            <td>{{ $client->redetermination_last_checked ? \Carbon\Carbon::parse($client->redetermination_last_checked)->format('m/d/Y') : '-' }}</td>
            <td class="label">Redetermination Remarks</td>
            <td>{{ $client->redetermination_remarks ?? '-' }}</td>
        </tr>
    </table>

    <h2>Contact Information</h2>
    <table class="grid section">
        <tr>
            <td class="label">Email</td>
            <td>{{ $client->email }}</td>
            <td class="label">Phone</td>
            <td>{{ $client->phone }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td colspan="3">{{ $client->address }}</td>
        </tr>
    </table>

    <h2>Program Details</h2>
    <table class="grid section">
        <tr>
            <td class="label">Starting Date</td>
            <td>{{ $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y') : '' }}</td>
            <td class="label">Discharge Date</td>
            <td>{{ $client->discharge_date ? \Carbon\Carbon::parse($client->discharge_date)->format('m/d/Y') : '' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ ucfirst($client->status) }}</td>
            <td class="label">Counselor</td>
            <td>{{ optional($client->counselor)->short_name ?? optional($client->counselor)->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Peer</td>
            <td>{{ optional($client->peer)->short_name ?? optional($client->peer)->name ?? 'N/A' }}</td>
            <td class="label"></td>
            <td></td>
        </tr>
    </table>

    <h2>Identifiers</h2>
    <table class="grid section">
        <tr>
            <td class="label">SSN</td>
            <td>
                @can('client.view_ssn')
                    {{ $client->ssn ? ('***-**-' . substr($client->ssn, -4)) : '' }}
                @else
                    Restricted
                @endcan
            </td>
            <td class="label">Medicaid ID</td>
            <td>
                @can('client.view_medicaid_id')
                    {{ $client->medicaid_id }}
                @else
                    Restricted
                @endcan
            </td>
        </tr>
        <tr>
            <td class="label">Carelon ID</td>
            <td>
                @can('client.view_carelon_id')
                    {{ $client->carelon_id }}
                @else
                    Restricted
                @endcan
            </td>
            <td class="label"></td>
            <td></td>
        </tr>
    </table>

    <h2>Notes</h2>
    <table class="grid section">
        <tr>
            <td class="label">Notes</td>
            <td>{{ $client->notes }}</td>
        </tr>
    </table>

    <h2>Level of Care History</h2>
    <table class="history">
        <thead>
            <tr>
                <th>Level of Care</th>
                <th>Start Date</th>
                <th>End Date</th>
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
                    <td colspan="3">No level of care history available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
