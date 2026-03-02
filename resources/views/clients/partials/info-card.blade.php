<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-3">Client Information</h5>
        <div class="row">
            <div class="col-md-4">
                <p class="mb-1"><strong>Name:</strong> {{ $client->last_name }}, {{ $client->first_name }}</p>
                <p class="mb-1"><strong>MRN:</strong> {{ $client->mrn ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Status:</strong> {{ ucfirst($client->status ?? 'N/A') }}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>DOB:</strong> {{ $client->date_of_birth ? \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') : 'N/A' }}</p>
                <p class="mb-1"><strong>Current LOC:</strong> {{ $client->getCurrentLevelOfCare() ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Start Date:</strong> {{ $client->starting_date ? \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y') : 'N/A' }}</p>
            </div>
            @php
                $today = now()->toDateString();
                $displayCounselor = $client->getDisplayCounselor($today);
                $displayPeer = $client->getDisplayPeer($today);
            @endphp
            <div class="col-md-4">
                <p class="mb-1"><strong>Counselor:</strong> {{ $displayCounselor?->name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Peer:</strong> {{ $displayPeer?->name ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>
