<?php

namespace App\Http\Controllers;

use App\Models\GroupNote;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GroupNoteController extends Controller
{
    public function index()
    {
        $counselors = User::whereHas('roles', function ($query) {
            $query->where('name', 'counselor');
        })->with('groupNotes')->get();

        return view('group_notes.index', compact('counselors'));
    }

    public function edit($counselorId)
    {
        $counselor = User::findOrFail($counselorId);
        $currentMonth = now()->format('Y-m');

        return view('group_notes.edit', compact('counselor', 'currentMonth'));
    }

    public function update(Request $request, $counselorId)
    {
        $request->validate([
            'added_dates' => 'required|string',
            'removed_dates' => 'required|string',
        ]);

        $addedDates = json_decode($request->added_dates, true) ?? [];
        $removedDates = json_decode($request->removed_dates, true) ?? [];

        // Add new group notes
        foreach ($addedDates as $month => $dates) {
            foreach ($dates as $date) {
                GroupNote::updateOrCreate(
                    ['counselor_id' => $counselorId, 'date' => $date],
                    ['completed' => true]
                );
            }
        }

        // Remove unchecked group notes
        foreach ($removedDates as $month => $dates) {
            GroupNote::where('counselor_id', $counselorId)
                ->whereIn('date', $dates)
                ->delete();
        }

        return redirect()->route('group_notes.index')->with('success', 'Group notes updated successfully.');
    }



    public function view($counselorId)
    {
        $counselor = User::findOrFail($counselorId);
        $currentMonth = now()->format('Y-m');

        return view('group_notes.view', compact('counselor', 'currentMonth'));
    }

    public function fetchGroupNotes($month, $counselorId)
    {
        $groupNotes = GroupNote::where('counselor_id', $counselorId)
            ->whereMonth('date', substr($month, 5, 2))
            ->whereYear('date', substr($month, 0, 4))
            ->pluck('date')
            ->toArray();

        return response()->json(['groupNotes' => $groupNotes]);
    }

}
