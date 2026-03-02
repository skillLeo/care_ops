<?php

namespace App\Http\Controllers;

use App\Models\AuthLineOfService;
use App\Models\Authorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthLineOfServiceController extends Controller
{
    public function index($authId)
    {
        $authorization = Authorization::findOrFail($authId);
        $lines = AuthLineOfService::where('auth_id', $authId)->get();

        return view('authorizations.line_of_services.index', compact('lines', 'authorization'));
    }

    public function create($authId)
    {
        $authorization = Authorization::with('lineOfServices')->findOrFail($authId);

        // Get existing line of services count
        $existingServices = $authorization->lineOfServices()->count();

        // Determine type (Initial if no previous services, else Concurrent)
        $type = $existingServices > 0 ? 'concurrent' : 'initial';

        // Get last diagnosis code if exists
        $lastDiagnosis = $authorization->lineOfServices()->latest()->value('diagnosis_code') ?? '';

        return view('authorizations.line_of_services.create', compact('authorization', 'type', 'lastDiagnosis'));
    }

    public function store(Request $request)
    {

        // Validate form input
        $validated = $request->validate([
            'auth_id' => 'required|exists:authorizations,id',
            'auth_number' => 'nullable|string',
            'type' => 'required|in:initial,concurrent,pause',
            'submission_date' => 'required|date',
            'starting_date' => 'required|date',
            'ending_date' => 'nullable|date',
            'units' => 'required|integer',
            'status' => 'required|in:approved,denied,in-process',
            'diagnosis_code' => 'required|string',
            'remarks' => 'nullable|string',
            'attachment.*' => 'file|mimes:jpg,png,pdf,doc,docx|max:2048', // Accept multiple files
        ]);
        // Ensure correct "Initial" or "Concurrent" selection
        $authorization = Authorization::with('lineOfServices')->findOrFail($validated['auth_id']);
        // $existingServices = $authorization->lineOfServices()->count();
        // $validated['type'] = $existingServices > 0 ? 'concurrent' : 'initial';

        // Auto-fill units based on LOC (if not changed manually)
        // if ($authorization->loc === "PHP" && !$request->filled('units')) {
        //     $validated['units'] = 10;
        // } elseif ($authorization->loc === "IOP" && !$request->filled('units')) {
        //     $validated['units'] = 35;
        // }

        // Use last diagnosis code if no input is provided
        // if (!$request->filled('diagnosis_code')) {
        //     $lastDiagnosis = $authorization->lineOfServices()->latest()->value('diagnosis_code');
        //     $validated['diagnosis_code'] = $lastDiagnosis ?? '';
        // }

        // Handle File Uploads
        $attachments = [];
        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $originalName = $file->getClientOriginalName();
                $timestamp = now()->timestamp;
                $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
                $file->storeAs('attachments', $uniqueFileName);
                $attachments[] = $uniqueFileName;
            }
        }

        // Store attachments as JSON
        $validated['attachments'] = json_encode($attachments);
        // dd($validated);
        // Create the new Line of Service
        AuthLineOfService::create($validated);


        if ($authorization->auth_number != $validated['auth_number']) {
            $authorization->update(['auth_number' => $validated['auth_number']]);
        }

        return redirect()->route('authorizations.lines', $validated['auth_id'])
                        ->with('success', 'Line of Service created successfully.');
    }


    public function show($id)
    {
        $authLineOfService = AuthLineOfService::findOrFail($id);
        return view('authorizations.line_of_services.show', compact('authLineOfService'));
    }

    public function edit($id)
    {
        $authLineOfService = AuthLineOfService::findOrFail($id);
        return view('authorizations.line_of_services.edit', compact('authLineOfService'));
    }

    public function update(Request $request, AuthLineOfService $authLineOfService)
    {
        // Validate request
        $validated = $request->validate([
            'type' => 'required|in:initial,concurrent,pause',
            'submission_date' => 'required|date',
            'units' => 'required|integer',
            'starting_date' => 'required|date',
            'status' => 'required|in:approved,denied,in-process',
            'ending_date' => 'nullable|date',
            'diagnosis_code' => 'required|string',
            'remarks' => 'nullable|string',
            'attachment.*' => 'file|mimes:jpg,png,pdf,doc,docx|max:2048',
        ]);

        // Get existing attachments (if any)
        $existingAttachments = json_decode($authLineOfService->attachments, true) ?? [];

        // Handle new file uploads
        if ($request->hasFile('attachment'))
        {
            foreach ($request->file('attachment') as $file)
            {
                $originalName = $file->getClientOriginalName();
                $timestamp = now()->timestamp;
                $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
                $file->storeAs('attachments', $uniqueFileName);
                $existingAttachments[] = $uniqueFileName; // Append new attachments on top
            }
        }

        // Store updated attachments as JSON
        $validated['attachments'] = json_encode($existingAttachments);

        // Update the Line of Service
        $authLineOfService->update($validated);

        return redirect()->route('authorizations.lines', $authLineOfService->authorization->id)
                        ->with('success', 'Line of Service updated successfully.');
    }


    public function destroy(AuthLineOfService $authLineOfService)
    {
        $authorizationId = $authLineOfService->auth_id;
        $authLineOfService->delete();
        return redirect()
            ->route('authorizations.lines', ['auth' => $authorizationId])
            ->with('success', 'Line of Service deleted successfully.');
    }

    public function downloadAttachment($filename)
    {
        $path = 'attachments/' . $filename;

        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File not found.');
        }

        $fullPath = Storage::disk('private')->path($path);
        return response()->download($fullPath);

    }
}
