<?php

namespace App\Http\Controllers;

use App\Models\Check;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CheckController extends Controller
{

    public function index()
    {
        $checks = Check::withCount('lines')->get();
        return view('checks.index', compact('checks'));
    }

    public function show(Check $check)
    {
        $check->load(['lines.claim']);

        // Replace $check->lines with a custom sorted version
        $check->setRelation('lines', $check->lines
            ->sortBy(function ($line) {
                $claim = $line->claim;
                return [
                    strtoupper($claim->client->last_name ?? ''),
                    optional($claim->submission_date)->timestamp ?? 0,
                    optional($line->service_date)->timestamp ?? 0,
                ];
            })
        );

        return view('checks.show', compact('check'));
    }


    public function create()
    {
        return view('checks.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'check_number' => 'required|unique:checks',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:2048',
        ]);

        $attachments = $this->storeAttachments($request);

        Check::create([
            'check_number' => $request->input('check_number'),
            'payment_date' => $request->input('payment_date'),
            'remarks' => $request->input('remarks'),
            'attachments' => $attachments ?: null,
        ]);

        return redirect()->route('checks.index')->with('success', 'Check created successfully.');
    }

    public function edit(Check $check)
    {
        return view('checks.edit', compact('check'));
    }

    public function update(Request $request, Check $check)
    {
        $request->validate([
            'check_number' => 'required|unique:checks,check_number,' . $check->id,
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:2048',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        $existingAttachments = $check->attachments ?? [];
        $removeAttachments = $request->input('remove_attachments', []);
        if (! is_array($removeAttachments)) {
            $removeAttachments = [];
        }

        $remainingAttachments = array_values(array_diff($existingAttachments, $removeAttachments));

        foreach ($removeAttachments as $filename) {
            Storage::delete('attachments/checks/' . $filename);
        }

        $attachments = $this->storeAttachments($request, $remainingAttachments);

        $check->update([
            'check_number' => $request->input('check_number'),
            'payment_date' => $request->input('payment_date'),
            'remarks' => $request->input('remarks'),
            'attachments' => $attachments ?: null,
        ]);

        return redirect()->route('checks.index')->with('success', 'Check updated successfully.');
    }

    public function toggle(Check $check)
    {
        $check->update(['open' => !$check->open]);

        return redirect()->back()->with('success', $check->open ? 'Check opened.' : 'Check closed.');
    }

    public function destroy(Check $check)
    {
        $check->lines()->update(['check_id' => null]);
        $check->delete();

        return redirect()->route('checks.index')->with('success', 'Check deleted successfully.');
    }

    public function downloadAttachment(Check $check, string $filename)
    {
        $attachments = $check->attachments ?? [];
        if (! in_array($filename, $attachments, true)) {
            abort(404, 'File not found.');
        }

        $path = 'attachments/checks/' . $filename;
        if (! Storage::exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($path, $filename);
    }

    protected function storeAttachments(Request $request, array $existing = []): array
    {
        $attachments = $existing;

        if (! $request->hasFile('attachments')) {
            return $attachments;
        }

        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $timestamp = now()->timestamp;
            $uniqueFileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $file->getClientOriginalExtension();
            $file->storeAs('attachments/checks', $uniqueFileName);
            $attachments[] = $uniqueFileName;
        }

        return $attachments;
    }

}
