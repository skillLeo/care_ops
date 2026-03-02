<?php

namespace App\Http\Controllers;

use App\Models\LevelOfCare;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LevelOfCareController extends Controller
{
    public function index()
    {
        $levels = LevelOfCare::orderBy('level_of_care')->get();

        return view('level_of_cares.index', compact('levels'));
    }

    public function create()
    {
        return view('level_of_cares.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_of_care' => ['required', 'string', 'max:50', 'unique:level_of_cares,level_of_care'],
            'display_name' => ['required', 'string', 'max:255'],
            'update_note_days' => ['nullable', 'integer', 'min:0'],
            'units' => ['nullable', 'integer', 'min:0'],
            'auth_days' => ['nullable', 'integer', 'min:0'],
        ]);

        LevelOfCare::create($validated);

        return redirect()->route('level-of-cares.index')->with('success', 'Level of care created successfully.');
    }

    public function show(LevelOfCare $levelOfCare)
    {
        return view('level_of_cares.show', compact('levelOfCare'));
    }

    public function edit(LevelOfCare $levelOfCare)
    {
        return view('level_of_cares.edit', compact('levelOfCare'));
    }

    public function update(Request $request, LevelOfCare $levelOfCare)
    {
        $validated = $request->validate([
            'level_of_care' => [
                'required',
                'string',
                'max:50',
                Rule::unique('level_of_cares', 'level_of_care')->ignore($levelOfCare->id),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'update_note_days' => ['nullable', 'integer', 'min:0'],
            'units' => ['nullable', 'integer', 'min:0'],
            'auth_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $levelOfCare->update($validated);

        return redirect()->route('level-of-cares.index')->with('success', 'Level of care updated successfully.');
    }

    public function destroy(LevelOfCare $levelOfCare)
    {
        $levelOfCare->delete();

        return redirect()->route('level-of-cares.index')->with('success', 'Level of care deleted successfully.');
    }
}
