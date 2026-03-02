<?php

namespace App\Http\Controllers;

use App\Models\PeerGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeerGroupController extends Controller
{
    public function index(): View
    {
        $peerGroups = PeerGroup::orderBy('name')->get();
        return view('peer_groups.index', compact('peerGroups'));
    }

    public function edit(PeerGroup $peerGroup): View
    {
        return view('peer_groups.edit', compact('peerGroup'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        PeerGroup::create($data);

        return redirect()->route('peer-groups.index')->with('success', 'Peer group created successfully.');
    }

    public function update(Request $request, PeerGroup $peerGroup): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $peerGroup->update($data);

        return redirect()->route('peer-groups.index')->with('success', 'Peer group updated successfully.');
    }

    public function destroy(PeerGroup $peerGroup): RedirectResponse
    {
        $peerGroup->delete();

        return redirect()->route('peer-groups.index')->with('success', 'Peer group deleted successfully.');
    }
}
