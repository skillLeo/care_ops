<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // handle avatar upload
        if ($request->hasFile('avatar')) {
            // delete old avatar file if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // store new file
            $path = $request->file('avatar')->store('avatars', 'public');

            // save path in db
            $user->avatar = $path;
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePin(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            // if user already has pin, they must enter current_pin
            'current_pin' => ['nullable', 'string', 'min:4', 'max:8'],
            'pin'         => ['required', 'string', 'min:4', 'max:8', 'confirmed'],
        ]);

        // if pin exists already -> require correct current_pin
        if (!empty($user->pin)) {
            if (empty($data['current_pin']) || !Hash::check($data['current_pin'], $user->pin)) {
                return back()->withErrors(['current_pin' => 'Current PIN is incorrect.']);
            }
        }

        $user->pin = Hash::make($data['pin']);
        $user->save();

        return back()->with('success', 'PIN updated successfully.');
    }
}