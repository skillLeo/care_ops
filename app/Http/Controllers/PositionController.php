<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('positions.index', compact('positions', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $position = Position::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        if (Schema::hasTable('position_user')) {
            $position->users()->sync($validated['user_ids'] ?? []);
        }

        return redirect()->route('positions.index')
            ->with('success', 'Position created successfully.');
    }

    public function edit(Position $position)
    {
        $users = User::orderBy('name')->get();
        $assignedUserIds = Schema::hasTable('position_user')
            ? $position->users()->pluck('users.id')->all()
            : [];

        return view('positions.edit', compact('position', 'users', 'assignedUserIds'));
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('positions', 'name')->ignore($position->id)],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $position->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        if (Schema::hasTable('position_user')) {
            $position->users()->sync($validated['user_ids'] ?? []);
        }

        return redirect()->route('positions.index')
            ->with('success', 'Position updated successfully.');
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return redirect()->route('positions.index')
            ->with('success', 'Position deleted successfully.');
    }
}
