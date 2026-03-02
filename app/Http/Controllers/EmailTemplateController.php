<?php

namespace App\Http\Controllers;

use App\Mail\ClinicalNoteTrackerMail;
use App\Models\EmailTemplate;
use App\Services\ClinicalNoteTrackerService;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();

        return view('email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        $placeholders = array_merge(
            EmailTemplateService::PLACEHOLDERS,
            EmailTemplateService::DROPBOX_PLACEHOLDERS
        );

        return view('email-templates.edit', compact('emailTemplate', 'placeholders'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
            'to' => ['nullable', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
        ]);

        $emailTemplate->update($validated);

        return redirect()
            ->route('email-templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function sendClinicalNoteTrackerTest(ClinicalNoteTrackerService $trackerService)
    {
        ['trackerRows' => $trackerRows, 'noteTypeOptions' => $noteTypeOptions] = $trackerService->buildTrackerRows();
        $trackerRows = $trackerService->filterRowsWithPending($trackerRows);
        ['unassignedRows' => $unassignedRows, 'counselorGroups' => $counselorGroups] = $trackerService->groupByCounselor($trackerRows);
        $asOfDate = now()->format('m/d/Y');

        foreach ($counselorGroups as $rows) {
            $counselor = $rows->first()['client']->counselor;
            if (! $counselor) {
                continue;
            }
            $assignedRows = $rows->values();
            if ($assignedRows->isEmpty() && $unassignedRows->isEmpty()) {
                continue;
            }

            Mail::to('fawzan@snbllc.org')->send(new ClinicalNoteTrackerMail(
                $counselor->name,
                $assignedRows,
                $unassignedRows,
                $noteTypeOptions,
                $asOfDate,
                $counselor->email
            ));
        }

        if ($unassignedRows->isNotEmpty()) {
            Mail::to('fawzan@snbllc.org')->send(new ClinicalNoteTrackerMail(
                'Unassigned',
                collect(),
                $unassignedRows,
                $noteTypeOptions,
                $asOfDate
            ));
        }

        return redirect()
            ->route('email-templates.index')
            ->with('success', 'Daily compliance summary test emails sent to fawzan@snbllc.org.');
    }

    public function sendClinicalNoteTrackerNow(ClinicalNoteTrackerService $trackerService)
    {
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

        return redirect()
            ->back()
            ->with('success', 'Daily compliance summary emails sent manually.');
    }
}
