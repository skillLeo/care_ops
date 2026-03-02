<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\{Apartment, Client, LevelOfCare, User};
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{ 
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->roles()->where('name', 'admin')->exists();

        if (! $isAdmin) {
            return view('home', ['isAdmin' => false]);
        }

        $today = now()->toDateString();
        $activeClients = Client::whereNull('discharge_date')
            ->whereDoesntHave('hospitalizations', function ($query) use ($today) {
                $query->where('start_date', '<=', $today)
                    ->where(function ($hospitalQuery) use ($today) {
                        $hospitalQuery->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    });
            })
            ->with(['authorizations.lineOfServices', 'consents'])
            ->get();
        $totalActiveClients = $activeClients->count();
        $guestClients = $activeClients->where('guest', true)->count();
        $housedClients = $activeClients->filter(fn (Client $client) => $client->getApartmentOnDate($today) !== null)->count();

        $levelOfCares = LevelOfCare::orderBy('display_name')->get();
        $activeClientLevels = $activeClients
            ->map(fn ($client) => $client->getCurrentLevelOfCare())
            ->filter();
        $activeLevelsCount = $activeClientLevels
            ->groupBy(fn ($level) => $level)
            ->map->count();

        $apartments = Apartment::with(['clients' => function ($query) {
            $query->whereNull('discharge_date');
        }])->get();

        $bedTypes = [
            'couples' => 'Couples',
            'single_male' => 'Single Male',
            'single_female' => 'Single Female',
            'mixed' => 'Mixed',
        ];

        $emptyBedsByType = collect($bedTypes)->mapWithKeys(function ($label, $type) use ($apartments) {
            $typeApartments = $apartments->where('type', $type);
            $capacity = $typeApartments->sum('capacity');
            $occupied = $typeApartments->sum(fn ($apartment) => $apartment->clients->count());

            return [$type => max($capacity - $occupied, 0)];
        });

        $lastSevenStart = Carbon::today()->subDays(6);
        $lastSevenIntakes = Client::whereDate('starting_date', '>=', $lastSevenStart)->count();
        $lastSevenDischarges = Client::whereDate('discharge_date', '>=', $lastSevenStart)->count();

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekIntakes = Client::whereDate('starting_date', '>=', $weekStart)->count();
        $weekDischarges = Client::whereDate('discharge_date', '>=', $weekStart)->count();

        $recentClientPhotos = Client::whereNotNull('profile_photo')
            ->orderByDesc('created_at')
            ->take(120)
            ->get()
            ->filter(function (Client $client) {
                return $client->profile_photo
                    && Storage::disk('local')->exists($client->profile_photo);
            })
            ->values();

        $authorizationStatusCounts = collect([
            'due' => 0,
            'pending' => 0,
            'approved' => 0,
            'denied' => 0,
            'na' => 0,
        ]);

        $photoWithCount = 0;
        $consentWithCount = 0;
        $diagnosisCounts = [];

        $eligibilityDueCount = 0;
        $lastCheckedOverdueCount = 0;
        $notEligibleCount = $activeClients->where('eligibility', 'not_eligible')->count();
        $eligibilityDueThreshold = Carbon::today()->addMonthsNoOverflow(2);
        $lastCheckedThreshold = Carbon::today()->subMonth();

        $activeClients->each(function (Client $client) use (
            $today,
            $authorizationStatusCounts,
            &$photoWithCount,
            &$consentWithCount,
            &$diagnosisCounts,
            $eligibilityDueThreshold,
            $lastCheckedThreshold,
            &$eligibilityDueCount,
            &$lastCheckedOverdueCount
        ) {
            if ($client->profile_photo && Storage::disk('local')->exists($client->profile_photo)) {
                $photoWithCount++;
            }

            if ($client->consents->isNotEmpty()) {
                $consentWithCount++;
            }

            if ($client->redetermination_date) {
                $redeterminationDate = Carbon::parse($client->redetermination_date);
                if ($redeterminationDate->lessThanOrEqualTo($eligibilityDueThreshold)) {
                    $eligibilityDueCount++;
                }
            }

            if ($client->redetermination_last_checked) {
                $lastChecked = Carbon::parse($client->redetermination_last_checked);
                if ($lastChecked->lessThan($lastCheckedThreshold)) {
                    $lastCheckedOverdueCount++;
                }
            }

            $todayLineOfService = $client->authorizations
                ->flatMap(fn ($authorization) => $authorization->lineOfServices)
                ->filter(function ($line) use ($today) {
                    return $line->starting_date <= $today
                        && (! $line->ending_date || $line->ending_date >= $today);
                })
                ->sortByDesc('starting_date')
                ->first();

            $statusKey = strtolower(trim($todayLineOfService->status ?? ''));
            $statusKey = match ($statusKey) {
                'due' => 'due',
                'pending' => 'pending',
                'approved' => 'approved',
                'denied' => 'denied',
                default => 'na',
            };

            $authorizationStatusCounts[$statusKey] = $authorizationStatusCounts[$statusKey] + 1;

            $diagnosisCode = $todayLineOfService?->diagnosis_code;
            if ($diagnosisCode) {
                $codes = collect(explode(',', $diagnosisCode))
                    ->map(fn ($code) => trim($code))
                    ->filter()
                    ->unique();

                foreach ($codes as $code) {
                    $diagnosisCounts[$code] = ($diagnosisCounts[$code] ?? 0) + 1;
                }
            }
        });

        $photoStats = [
            'with' => $photoWithCount,
            'without' => max($totalActiveClients - $photoWithCount, 0),
        ];

        $consentStats = [
            'with' => $consentWithCount,
            'without' => max($totalActiveClients - $consentWithCount, 0),
        ];

        $eligibilityTrackerCounts = [
            'all' => $totalActiveClients,
            'not_eligible' => $notEligibleCount,
            'eligibility_due' => $eligibilityDueCount,
            'last_checked_overdue' => $lastCheckedOverdueCount,
        ];

        $topDiagnosisCounts = collect($diagnosisCounts)
            ->sortDesc()
            ->take(10);
        $diagnosisChartLabels = $topDiagnosisCounts->keys()->values();
        $diagnosisChartCounts = $topDiagnosisCounts->values();

        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->orderBy('name')->get();

        $activeClientSummary = $activeClients->map(function ($client) {
            return [
                'counselor_id' => optional($client->counselor)->id,
                'level_of_care' => $client->getCurrentLevelOfCare(),
            ];
        });

        $counselorCaseloads = $counselors->map(function ($counselor) use ($activeClientSummary, $levelOfCares) {
            $clients = $activeClientSummary->where('counselor_id', $counselor->id);
            $levelCounts = $levelOfCares->mapWithKeys(function ($level) use ($clients) {
                $levelKey = $level->level_of_care;

                return [$levelKey => $clients->where('level_of_care', $levelKey)->count()];
            });

            return [
                'counselor' => $counselor,
                'total' => $clients->count(),
                'levels' => $levelCounts,
            ];
        });

        return view('home', compact(
            'isAdmin',
            'totalActiveClients',
            'guestClients',
            'housedClients',
            'levelOfCares',
            'activeLevelsCount',
            'emptyBedsByType',
            'bedTypes',
            'lastSevenIntakes',
            'lastSevenDischarges',
            'weekIntakes',
            'weekDischarges',
            'recentClientPhotos',
            'counselorCaseloads',
            'authorizationStatusCounts',
            'photoStats',
            'consentStats',
            'eligibilityTrackerCounts',
            'diagnosisChartLabels',
            'diagnosisChartCounts'
        ));
    }
}
