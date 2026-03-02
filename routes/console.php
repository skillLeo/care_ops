<?php

use App\Mail\ClinicalNoteTrackerMail;
use App\Services\ClinicalNoteTrackerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('clinical-notes:send-tracker', function () {
    $trackerService = app(ClinicalNoteTrackerService::class);
    ['trackerRows' => $trackerRows, 'noteTypeOptions' => $noteTypeOptions] = $trackerService->buildTrackerRows();
    $trackerRows = $trackerService->filterRowsWithPending($trackerRows);
    ['unassignedRows' => $unassignedRows, 'counselorGroups' => $counselorGroups] = $trackerService->groupByCounselor($trackerRows);
    $asOfDate = now()->format('m/d/Y');

    foreach ($counselorGroups as $rows) {
        $counselor = $rows->first()['client']->counselor;
        if (! $counselor?->email) {
            continue;
        }
        $assignedRows = $rows->values();
        if ($assignedRows->isEmpty() && $unassignedRows->isEmpty()) {
            continue;
        }
        Mail::to($counselor->email)->send(new ClinicalNoteTrackerMail(
            $counselor->name,
            $assignedRows,
            $unassignedRows,
            $noteTypeOptions,
            $asOfDate,
            $counselor->email
        ));
    }

    if ($unassignedRows->isNotEmpty()) {
        Mail::to('snbllc.org@gmail.com')->send(new ClinicalNoteTrackerMail(
            'Unassigned',
            collect(),
            $unassignedRows,
            $noteTypeOptions,
            $asOfDate,
            null
        ));
    }
})->purpose('Send daily chart compliance summary emails')->dailyAt('08:00');
