<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->canDeleteRecords()) {
            abort(403);
        }

        return view('users.pin', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->canDeleteRecords()) {
            abort(403);
        }

        $rules = [
            'new_pin' => 'required|string|min:4|confirmed',
        ];

        if ($user->pin) {
            $rules['current_pin'] = 'required|string';
        }

        $request->validate($rules);

        if ($user->pin && ! Hash::check($request->current_pin, $user->pin)) {
            return back()->withErrors([
                'current_pin' => 'The current PIN is incorrect.',
            ]);
        }

        $user->update([
            'pin' => Hash::make($request->new_pin),
        ]);

        return back()->with('success', 'PIN updated successfully.');
    }

    public function resetForm(User $user, Request $request)
    {
        $admin = $request->user();

        if (! $admin || ! $admin->roles()->where('name', 'admin')->exists()) {
            abort(403);
        }

        return view('users.reset_pin', compact('user'));
    }

    public function reset(User $user, Request $request)
    {
        $admin = $request->user();

        if (! $admin || ! $admin->roles()->where('name', 'admin')->exists()) {
            abort(403);
        }

        $request->validate([
            'new_pin' => 'required|string|min:4|confirmed',
        ]);

        $user->update([
            'pin' => Hash::make($request->new_pin),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'PIN reset successfully.');
    }
}
