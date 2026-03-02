<?php

namespace App\Http\Controllers;

use App\Models\MedicalContact;
use Illuminate\Http\Request;

class MedicalContactController extends Controller
{
    public function index()
    {
        $medicalContacts = MedicalContact::all();
        return view('medical_contacts.index', compact('medicalContacts'));
    }

    public function create()
    {
        return view('medical_contacts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:medical_contacts,email',
            'contact' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        MedicalContact::create($validated);

        return redirect()->route('medical-contacts.index')
            ->with('success', 'Medical Contact added successfully.');
    }

    public function edit(MedicalContact $medicalContact)
    {
        return view('medical_contacts.edit', compact('medicalContact'));
    }

    public function update(Request $request, MedicalContact $medicalContact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:medical_contacts,email,' . $medicalContact->id,
            'contact' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $medicalContact->update($validated);

        return redirect()->route('medical-contacts.index')
            ->with('success', 'Medical Contact updated successfully.');
    }

    public function destroy(MedicalContact $medicalContact)
    {
        $medicalContact->delete();
        return redirect()->route('medical-contacts.index')
            ->with('success', 'Medical Contact deleted successfully.');
    }

    public function show(MedicalContact $medicalContact)
    {
        return view('medical_contacts.show', compact('medicalContact'));
    }

}
