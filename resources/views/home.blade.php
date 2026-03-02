@extends('adminlte::page')

@section('title', 'Home')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    @php
        // ✅ prevent "undefined variable" errors (blade + js)
        $isAdmin = $isAdmin ?? false;

        $diagnosisChartLabels = $diagnosisChartLabels ?? collect();
        $diagnosisChartCounts = $diagnosisChartCounts ?? collect();

        $authorizationStatusCounts = $authorizationStatusCounts ?? collect();

        $photoStats = $photoStats ?? ['with' => 0, 'without' => 0];
        $consentStats = $consentStats ?? ['with' => 0, 'without' => 0];

        $eligibilityTrackerCounts = $eligibilityTrackerCounts ?? [
            'all' => 0,
            'not_eligible' => 0,
            'eligibility_due' => 0,
            'last_checked_overdue' => 0,
        ];

        $levelOfCares = $levelOfCares ?? collect();
        $activeLevelsCount = $activeLevelsCount ?? collect();

        $bedTypes = $bedTypes ?? [];
        $emptyBedsByType = $emptyBedsByType ?? collect();

        $lastSevenIntakes = $lastSevenIntakes ?? 0;
        $lastSevenDischarges = $lastSevenDischarges ?? 0;
        $weekIntakes = $weekIntakes ?? 0;
        $weekDischarges = $weekDischarges ?? 0;

        $recentClientPhotos = $recentClientPhotos ?? collect();

        $counselorCaseloads = $counselorCaseloads ?? [];
        $totalActiveClients = $totalActiveClients ?? 0;
        $guestClients = $guestClients ?? 0;
        $housedClients = $housedClients ?? 0;
    @endphp

    @if (! $isAdmin)
        <p class="text-muted mb-0">Dashboard</p>
    @else
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalActiveClients }}</h3>
                    <p>Total Active Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $guestClients }}</h3>
                    <p>Guest Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $housedClients }}</h3>
                    <p>Housed Clients</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Authorization Information (Today)</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $authorizationStatusCounts->get('due', 0) }}</h4>
                            <small class="text-muted">Due</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $authorizationStatusCounts->get('pending', 0) }}</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $authorizationStatusCounts->get('approved', 0) }}</h4>
                            <small class="text-muted">Approved</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $authorizationStatusCounts->get('denied', 0) }}</h4>
                            <small class="text-muted">Denied</small>
                        </div>
                        <div class="col-12">
                            <h4 class="mb-1">{{ $authorizationStatusCounts->get('na', 0) }}</h4>
                            <small class="text-muted">N/A</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Client Documentation</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $photoStats['with'] }}</h4>
                            <small class="text-muted">Photos</small>
                            <div class="text-muted small">{{ $photoStats['without'] }} without</div>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $consentStats['with'] }}</h4>
                            <small class="text-muted">Consents</small>
                            <div class="text-muted small">{{ $consentStats['without'] }} without</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Eligibility Tracker Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $eligibilityTrackerCounts['all'] }}</h4>
                            <small class="text-muted">Show All</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $eligibilityTrackerCounts['not_eligible'] }}</h4>
                            <small class="text-muted">Not Eligible</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $eligibilityTrackerCounts['eligibility_due'] }}</h4>
                            <small class="text-muted">Eligibility Due</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-1">{{ $eligibilityTrackerCounts['last_checked_overdue'] }}</h4>
                            <small class="text-muted">Last Checked Overdue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Top Diagnosis Codes (Today)</h3>
                </div>
                <div class="card-body">
                    @if ($diagnosisChartLabels->isEmpty())
                        <p class="text-muted mb-0">No diagnosis data available for today.</p>
                    @else
                        <canvas id="diagnosisChart" height="260"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Clients by Level of Care</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($levelOfCares as $levelOfCare)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold">{{ $levelOfCare->display_name ?? $levelOfCare->level_of_care }}</span>
                                        @php $count = $activeLevelsCount->get($levelOfCare->level_of_care, 0); @endphp
                                        <span class="badge badge-primary badge-pill">
                                            {{ $count }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center mt-2">
                                        @php
                                            $percent = $totalActiveClients > 0
                                                ? round(($count / $totalActiveClients) * 100)
                                                : 0;
                                        @endphp
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted ml-2">{{ $percent }}%</small>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No levels of care configured.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Empty Beds by Category (All Houses)</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        @foreach ($bedTypes as $type => $label)
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="border rounded py-3 h-100">
                                    <h4 class="mb-1">{{ $emptyBedsByType->get($type, 0) }}</h4>
                                    <small class="text-muted">{{ $label }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Intakes vs Discharges (Last 7 Days)</h3>
                </div>
                <div class="card-body d-flex justify-content-around text-center">
                    <div>
                        <h4 class="mb-1">{{ $lastSevenIntakes }}</h4>
                        <small class="text-muted">Intakes</small>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ $lastSevenDischarges }}</h4>
                        <small class="text-muted">Discharges</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Intakes vs Discharges (Week to Date)</h3>
                </div>
                <div class="card-body d-flex justify-content-around text-center">
                    <div>
                        <h4 class="mb-1">{{ $weekIntakes }}</h4>
                        <small class="text-muted">Intakes</small>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ $weekDischarges }}</h4>
                        <small class="text-muted">Discharges</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title">Client Photos</h3>
        </div>
        <div class="card-body">
            @if ($recentClientPhotos->isEmpty())
                <p class="text-muted mb-0">No client photos uploaded yet.</p>
            @else
                <div class="client-photo-carousel">
                    <button class="client-photo-control client-photo-control-prev" type="button" aria-label="Previous">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <div class="client-photo-track-wrapper">
                        <div class="client-photo-track">
                            @foreach ($recentClientPhotos as $client)
                                <div class="client-photo-item text-center">
                                    <img class="img-thumbnail client-photo-thumbnail"
                                        src="{{ route('client.photo', $client->id) }}"
                                        alt="{{ $client->first_name }} {{ $client->last_name }}"
                                        loading="lazy"
                                        decoding="async">
                                    <div class="small mt-2">
                                        {{ $client->first_name }} {{ $client->last_name }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button class="client-photo-control client-photo-control-next" type="button" aria-label="Next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Caseload by Counselor</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Counselor</th>
                            <th>Total</th>
                            @foreach ($levelOfCares as $levelOfCare)
                                <th>{{ $levelOfCare->display_name ?? $levelOfCare->level_of_care }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($counselorCaseloads as $caseload)
                            <tr>
                                <td>{{ $caseload['counselor']->name }}</td>
                                <td>{{ $caseload['total'] }}</td>
                                @foreach ($levelOfCares as $levelOfCare)
                                    <td>{{ $caseload['levels']->get($levelOfCare->level_of_care, 0) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + $levelOfCares->count() }}" class="text-center text-muted">
                                    No counselors found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
    <style>
        .client-photo-carousel {
            position: relative;
            display: flex;
            align-items: center;
        }
        .client-photo-track-wrapper {
            overflow: hidden;
            flex: 1;
        }
        .client-photo-track {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 16px;
        }
        .client-photo-item {
            flex: 0 0 auto;
        }
        .client-photo-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            padding: 0;
            width: 40px;
            height: 40px;
            cursor: pointer;
        }
        .client-photo-control-prev {
            left: -24px;
        }
        .client-photo-control-next {
            right: -24px;
        }
        .client-photo-control .carousel-control-prev-icon,
        .client-photo-control .carousel-control-next-icon {
            filter: invert(1) grayscale(100%);
        }
        .client-photo-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const carousel = document.querySelector('.client-photo-carousel');
            if (!carousel) {
                return;
            }

            const track = carousel.querySelector('.client-photo-track');
            const items = track ? track.querySelectorAll('.client-photo-item') : [];
            const prevButton = carousel.querySelector('.client-photo-control-prev');
            const nextButton = carousel.querySelector('.client-photo-control-next');

            if (!track || items.length === 0) {
                return;
            }

            const getStepWidth = () => {
                const sample = items[0];
                const styles = window.getComputedStyle(sample);
                const marginRight = parseFloat(styles.marginRight) || 0;
                const marginLeft = parseFloat(styles.marginLeft) || 0;
                const width = sample.getBoundingClientRect().width;
                return width + marginLeft + marginRight;
            };

            const scrollByItems = (direction) => {
                const step = getStepWidth() * 3;
                track.parentElement.scrollBy({
                    left: step * direction,
                    behavior: 'smooth',
                });
            };

            prevButton?.addEventListener('click', () => scrollByItems(-1));
            nextButton?.addEventListener('click', () => scrollByItems(1));
        });

        document.addEventListener('DOMContentLoaded', () => {
            const diagnosisCanvas = document.getElementById('diagnosisChart');
            if (!diagnosisCanvas) {
                return;
            }

            // ✅ safe defaults so blade never crashes
            const labels = @json(($diagnosisChartLabels ?? collect())->values());
            const dataPoints = @json(($diagnosisChartCounts ?? collect())->values());

            if (!labels.length) {
                return;
            }

            new Chart(diagnosisCanvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Clients',
                        data: dataPoints,
                        backgroundColor: '#17a2b8',
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        });
    </script>
@stop