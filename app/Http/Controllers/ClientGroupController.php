<?php

namespace App\Http\Controllers;

use App\Models\ClientGroup;
use App\Models\ClientLevelOfCares;
use App\Models\LevelOfCare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientGroupController extends Controller
{
    public function index(): View
    {
        $groups = ClientGroup::with('levelOfCare')->orderBy('name')->get();
        $levelOfCares = LevelOfCare::orderBy('display_name')->get();

        return view('client_groups.index', compact('groups', 'levelOfCares'));
    }

    public function edit(ClientGroup $clientGroup): View
    {
        $levelOfCares = LevelOfCare::orderBy('display_name')->get();

        return view('client_groups.edit', compact('clientGroup', 'levelOfCares'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_of_care_id' => 'required|exists:level_of_cares,id',
        ]);

        ClientGroup::create($data);

        return redirect()->route('client-groups.index')->with('success', 'Client group created successfully.');
    }

    public function update(Request $request, ClientGroup $clientGroup): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_of_care_id' => 'required|exists:level_of_cares,id',
        ]);

        $clientGroup->update($data);

        return redirect()->route('client-groups.index')->with('success', 'Client group updated successfully.');
    }

    public function destroy(ClientGroup $clientGroup): RedirectResponse
    {
        \App\Models\ClientClientGroup::where('client_group_id', $clientGroup->id)
            ->whereNull('end_date')
            ->update(['end_date' => now()->toDateString()]);

        $clientGroup->delete();

        return redirect()->route('client-groups.index')->with('success', 'Client group deleted successfully.');
    }
}
