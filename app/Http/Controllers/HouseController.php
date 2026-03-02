<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    public function index()
    {
        $houses = House::all();
        return view('houses.index', compact('houses'));
    }

    public function create()
    {
        return view('houses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'house_name' => 'required|unique:houses|max:255',
            'house_address' => 'nullable|string',
        ]);

        $house = House::create($request->all());

        $house->apartments()->create([
            'apartment_number' => '_Unallocated',
            'capacity' => 0,
        ]);

        return redirect()->route('houses.index')->with('success', 'House added successfully.');
    }

    public function edit(House $house)
    {
        return view('houses.edit', compact('house'));
    }

    public function update(Request $request, House $house)
    {
        $request->validate([
            'house_name' => 'required|max:255|unique:houses,house_name,' . $house->id,
            'house_address' => 'nullable|string',
        ]);

        $house->update($request->all());

        return redirect()->route('houses.index')->with('success', 'House updated successfully.');
    }

    public function show($id)
    {
        $house = House::with(['apartments.clients'])->findOrFail($id);

        return view('houses.show', compact('house'));
    }


    public function destroy(House $house)
    {
        $house->delete();
        return redirect()->route('houses.index')->with('success', 'House deleted successfully.');
    }
}
