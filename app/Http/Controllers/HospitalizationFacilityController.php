<?php

namespace App\Http\Controllers;

use App\Models\HospitalizationFacility;
use Illuminate\Http\Request;

class HospitalizationFacilityController extends Controller
{
    public function index()
    {
        $facilities = HospitalizationFacility::orderBy('name')->get();

        return view('hospitalization_facilities.index', compact('facilities'));
    }

    public function create()
    {
        return view('hospitalization_facilities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:detox,hospital'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        HospitalizationFacility::create($validated);

        return redirect()->route('hospitalization-facilities.index')
            ->with('success', 'Facility added successfully.');
    }

    public function edit(HospitalizationFacility $hospitalizationFacility)
    {
        return view('hospitalization_facilities.edit', compact('hospitalizationFacility'));
    }

    public function update(Request $request, HospitalizationFacility $hospitalizationFacility)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:detox,hospital'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $hospitalizationFacility->update($validated);

        return redirect()->route('hospitalization-facilities.index')
            ->with('success', 'Facility updated successfully.');
    }

    public function destroy(HospitalizationFacility $hospitalizationFacility)
    {
        $hospitalizationFacility->delete();

        return redirect()->route('hospitalization-facilities.index')
            ->with('success', 'Facility deleted successfully.');
    }
}
