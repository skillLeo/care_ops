<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Client;
use App\Models\House;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index()
    {
        $apartments = Apartment::with(['house', 'clients'])->get();
        return view('apartments.index', compact('apartments'));
    }


    public function create()
    {
        $houses = House::all();
        return view('apartments.create', compact('houses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'house_id' => 'required|exists:houses,id',
            'apartment_number' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:single_male,single_female,couples,mixed',
        ]);

        Apartment::create($validated);

        return redirect()->route('apartments.index')->with('success', 'Apartment added successfully.');
    }

    public function edit(Apartment $apartment)
    {
        $houses = House::all();
        return view('apartments.edit', compact('apartment', 'houses'));
    }

    public function show($id)
    {
        $apartment = Apartment::with(['house', 'clients'])->findOrFail($id);

        return view('apartments.show', compact('apartment')); 
    }


    public function update(Request $request, Apartment $apartment)
    {
        $validated = $request->validate([
            'house_id' => 'required|exists:houses,id',
            'apartment_number' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:single_male,single_female,couples,mixed',
        ]);

        $apartment->update($validated);

        return redirect()->route('apartments.index')->with('success', 'Apartment updated successfully.');
    }

    public function destroy(Apartment $apartment)
    {
        $apartment->delete();
        return redirect()->route('apartments.index')->with('success', 'Apartment deleted successfully.');
    }

    public function byHouse(House $house)
    {
        return response()->json($house->apartments()->select('id', 'apartment_number')->get());
    }
}
