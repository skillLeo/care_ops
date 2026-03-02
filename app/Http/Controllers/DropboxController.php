<?php

namespace App\Http\Controllers;

use App\Mail\DropboxApprovalMail;
use App\Models\Dropbox;
use App\Services\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class DropboxController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->has('status') ? $request->query('status') : 'pending';

        $dropboxes = Dropbox::with('client')
            ->when($status && $status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('dropboxes.index', [
            'dropboxes' => $dropboxes,
            'activeStatus' => $status,
        ]);
    }

    public function show(Dropbox $dropbox)
    {
        $dropbox->load('client');

        return view('dropboxes.show', compact('dropbox'));
    }

    public function updateStatus(Request $request, Dropbox $dropbox): RedirectResponse
    {
        if ($dropbox->status === 'created') {
            return redirect()->route('dropboxes.show', $dropbox)->with('error', 'This Dropbox submission has already been used to create a client.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'denied'])],
            'remarks' => ['required', 'string'],
        ]);

        $dropbox->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'],
            'status_updated_at' => now(),
        ]);

        if ($validated['status'] === 'approved') {
            $this->notifyApproval($dropbox);
        }

        return redirect()
            ->route('dropboxes.show', $dropbox)
            ->with('success', 'Dropbox status updated successfully.');
    }

    public function destroy(Dropbox $dropbox): RedirectResponse
    {
        if ($dropbox->client) {
            return redirect()
                ->route('dropboxes.show', $dropbox)
                ->with('error', 'This Dropbox submission is linked to a client and cannot be deleted.');
        }

        $paths = array_filter([
            $dropbox->biopsychosocial_path,
            $dropbox->discharge_summary_path,
            $dropbox->roi_document_path,
        ]);

        $urinePaths = $dropbox->urine_history_paths ?? [];
        $paths = array_merge($paths, $urinePaths);

        foreach ($paths as $path) {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        $dropbox->delete();

        return redirect()
            ->route('dropboxes.index')
            ->with('success', 'Dropbox deleted successfully.');
    }

    public function download(Dropbox $dropbox, string $type, ?int $index = null)
    {
        $path = match ($type) {
            'biopsychosocial' => $dropbox->biopsychosocial_path,
            'discharge'       => $dropbox->discharge_summary_path,
            'urine'           => $this->resolveUrinePath($dropbox, $index),
            'roi'             => $dropbox->roi_document_path,
            default           => null,
        };

        // 404 if nothing to download
        abort_if(empty($path), 404);

        // Always check via the disk you used to save
        abort_if(! Storage::exists($path), 404);

        // Stream the file (works for private storage too)
        $downloadName = basename($path);
        return Storage::download($path, $downloadName);
    }

    protected function resolveUrinePath(Dropbox $dropbox, ?int $index): ?string
    {
        $paths = $dropbox->urine_history_paths ?? [];

        if ($index === null) {
            return $paths[0] ?? null;
        }

        return $paths[$index] ?? null;
    }

    protected function notifyApproval(Dropbox $dropbox): void
    {
        $recipients = $this->parseEmails(env('DROPBOX_APPROVAL_MAIL'));

        $templateService = app(EmailTemplateService::class);
        $message = $templateService->buildDropboxMessage('dropbox_approval', $dropbox);

        if ($message) {
            $to = $message['to'] ?: $recipients;
            if (empty($to)) {
                return;
            }

            Mail::raw($message['body'], function ($mail) use ($message, $to) {
                $mail->to($to)
                    ->subject($message['subject']);

                if (! empty($message['cc'])) {
                    $mail->cc($message['cc']);
                }

                if (! empty($message['bcc'])) {
                    $mail->bcc($message['bcc']);
                }
            });
            return;
        }

        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->send(new DropboxApprovalMail($dropbox));
    }

    protected function parseEmails(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        return collect(preg_split('/[;,\n]/', $raw))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->all();
    }
}
