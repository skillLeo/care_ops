<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $roleIds = $user->roles()->pluck('roles.id');
        $canViewAll = $user->can('document.view_all');
        $canView = $user->can('document.view') || $canViewAll;

        abort_unless($canView, 403);

        if ($canViewAll) {
            $documents = Document::with(['roles', 'uploader'])
                ->latest()
                ->get();
        } else {
            $documents = Document::with(['roles', 'uploader'])
                ->when($roleIds->isNotEmpty(), function ($query) use ($roleIds) {
                    $query->whereHas('roles', function ($roleQuery) use ($roleIds) {
                        $roleQuery->whereIn('roles.id', $roleIds);
                    });
                })
                ->when($roleIds->isEmpty(), function ($query) {
                    $query->whereRaw('1 = 0');
                })
                ->latest()
                ->get();
        }

        return view('documents.index', compact('documents'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('display_name')->get();

        return view('documents.create', compact('roles'));
    }

    public function show(Request $request, Document $document): View
    {
        $user = $request->user();
        $roleIds = $user->roles()->pluck('roles.id');
        $canViewAll = $user->can('document.view_all');
        $canView = $user->can('document.view') || $canViewAll;
        $hasAccess = $canViewAll || $document->roles()->whereIn('roles.id', $roleIds)->exists();

        abort_unless($canView && $hasAccess, 403);

        $document->load(['roles', 'uploader']);

        return view('documents.show', compact('document'));
    }

    public function edit(Request $request, Document $document): View
    {
        $user = $request->user();

        abort_unless($user->can('document.upload'), 403);

        $roles = Role::orderBy('display_name')->get();
        $document->load('roles');

        return view('documents.edit', compact('document', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'documents' => 'required|array',
            'documents.*' => 'file|max:51200',
        ]);

        $roleIds = $validated['roles'];

        foreach ($request->file('documents', []) as $file) {
            $path = $file->store('documents', 'private');

            $document = Document::create([
                'title' => $validated['title'] ?: $file->getClientOriginalName(),
                'description' => $validated['description'] ?? null,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);

            $document->roles()->sync($roleIds);
        }

        return redirect()->route('documents.index')->with('success', 'Documents uploaded successfully.');
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->can('document.upload'), 403);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $document->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $document->roles()->sync($validated['roles']);

        return redirect()->route('documents.show', $document)->with('success', 'Document updated successfully.');
    }

    public function download(Request $request, Document $document)
    {
        $user = $request->user();
        $roleIds = $user->roles()->pluck('roles.id');
        $canViewAll = $user->can('document.view_all');
        $canDownload = $user->can('document.download') || $canViewAll;
        $hasAccess = $canViewAll || $document->roles()->whereIn('roles.id', $roleIds)->exists();

        abort_unless($canDownload && $hasAccess, 403);

        $disk = Storage::disk('private');

        abort_if(! $disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $disk = Storage::disk('private');

        if ($disk->exists($document->path)) {
            $disk->delete($document->path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
