<?php

namespace App\Http\Controllers;

use App\Models\ClientHospitalization;
use Carbon\Carbon;

class HospitalizationController extends Controller
{
    public function tracker()
    {
        $hospitalizations = ClientHospitalization::with([
            'client',
            'facility',
            'client.levelOfCareHistory.levelOfCare',
            'client.authorizations.levelOfCare',
            'client.authorizations.lineOfServices',
        ])
            ->whereNull('end_date')
            ->orderByDesc('start_date')
            ->get()
            ->map(function (ClientHospitalization $hospitalization) {
                $client = $hospitalization->client;
                $lastLevel = $client->levelOfCareHistory()
                    ->orderByDesc('start_date')
                    ->first();

                $lastAuth = null;
                if ($lastLevel) {
                    $lastAuth = $client->authorizations()
                        ->where('level_of_care', $lastLevel->level_of_care)
                        ->orderByDesc('auth_ending_date')
                        ->orderByDesc('auth_starting_date')
                        ->first();
                }

                $lastServiceDate = null;
                if ($lastAuth) {
                    $lastLine = $lastAuth->lineOfServices()
                        ->orderByDesc('ending_date')
                        ->orderByDesc('starting_date')
                        ->first();
                    $lastServiceDate = $lastLine?->ending_date
                        ?? $lastAuth->auth_ending_date
                        ?? $lastAuth->auth_starting_date;
                }

                return [
                    'hospitalization' => $hospitalization,
                    'client' => $client,
                    'last_level' => $lastLevel,
                    'last_auth' => $lastAuth,
                    'last_service_date' => $lastServiceDate,
                ];
            });

        $today = Carbon::now()->toDateString();

        return view('hospitalizations.tracker', compact('hospitalizations', 'today'));
    }
}
