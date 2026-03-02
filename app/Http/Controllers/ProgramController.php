<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    // Display a listing of the programs
    public function index()
    {
        $programs = Program::all();
        return view('programs.index', compact('programs'));
    }

    // Show the form for creating a new program
    public function create()
    {
        return view('programs.create');
    }

    // Store a newly created program in storage
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:programs,name|max:50',
        ]);

        Program::create($request->only('name'));

        return redirect()->route('programs.index')
                         ->with('success', 'Program created successfully.');
    }

    // Display the specified program
    public function show($id)
    {
        $program = Program::findOrFail($id);
        return view('programs.show', compact('program'));
    }

    // Show the form for editing the specified program
    public function edit($id)
    {
        $program = Program::findOrFail($id);
        return view('programs.edit', compact('program'));
    }

    // Update the specified program in storage
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:programs,name,' . $id . '|max:50',
        ]);

        $program = Program::findOrFail($id);
        $program->update($request->only('name'));

        return redirect()->route('programs.index')
                         ->with('success', 'Program updated successfully.');
    }

    // Remove the specified program from storage
    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return redirect()->route('programs.index')
                         ->with('success', 'Program deleted successfully.');
    }
}
