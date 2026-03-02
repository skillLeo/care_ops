<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\LevelOfCare;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'levelOfCare'])->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();
        $positions = Position::orderBy('name')->get();

        return view('users.create', compact('roles', 'levelOfCares', 'positions'));
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'short_name' => 'nullable|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'positions' => 'nullable|array',
            'positions.*' => 'exists:positions,id',
            'password' => 'required|min:8',
            'level_of_care' => 'nullable|exists:level_of_cares,id',
        ]);
        $validator->after(function ($validator) use ($request) {
            $roleNames = Role::whereIn('id', $request->input('roles', []))
                ->pluck('name')
                ->map(fn ($name) => strtolower($name))
                ->all();

            if (in_array('counselor', $roleNames, true) && ! $request->input('level_of_care')) {
                $validator->errors()->add('level_of_care', 'The level of care field is required when creating a counselor.');
            }
        });
        $validator->validate();

        $primaryRoleId = $request->input('roles.0');
        $data = $request->only('name','short_name','email');
        $data['role_id'] = $primaryRoleId;
        $data['password'] = bcrypt($request->password);
        $roleNames = Role::whereIn('id', $request->input('roles', []))
            ->pluck('name')
            ->map(fn ($name) => strtolower($name))
            ->all();
        $data['level_of_care'] = array_intersect($roleNames, ['counselor', 'peer']) ? $request->level_of_care : null;

        $user = User::create($data);
        $user->roles()->sync($request->input('roles', []));
        $user->positions()->sync($request->input('positions', []));

        return redirect()->route('users.index')
                         ->with('success','User created successfully.');
    }

    public function show($id)
    {
        $user = User::with(['roles', 'levelOfCare'])->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::with(['roles', 'positions'])->findOrFail($id);
        $roles = Role::all();
        $levelOfCares = LevelOfCare::orderBy('level_of_care')->get();
        $positions = Position::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'levelOfCares', 'positions'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'short_name' => 'nullable|string|max:255',
            'email'   => 'required|email|unique:users,email,'.$user->id,
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'positions' => 'nullable|array',
            'positions.*' => 'exists:positions,id',
            'level_of_care' => 'nullable|exists:level_of_cares,id',
        ]);
        $validator->after(function ($validator) use ($request) {
            $roleNames = Role::whereIn('id', $request->input('roles', []))
                ->pluck('name')
                ->map(fn ($name) => strtolower($name))
                ->all();

            if (in_array('counselor', $roleNames, true) && ! $request->input('level_of_care')) {
                $validator->errors()->add('level_of_care', 'The level of care field is required when creating a counselor.');
            }
        });
        $validator->validate();

        $primaryRoleId = $request->input('roles.0');
        $data = $request->only('name','short_name','email');
        $data['role_id'] = $primaryRoleId;
        $roleNames = Role::whereIn('id', $request->input('roles', []))
            ->pluck('name')
            ->map(fn ($name) => strtolower($name))
            ->all();
        $data['level_of_care'] = array_intersect($roleNames, ['counselor', 'peer']) ? $request->level_of_care : null;


        $user->update($data);
        $user->roles()->sync($request->input('roles', []));
        $user->positions()->sync($request->input('positions', []));

        return redirect()->route('users.index')
                         ->with('success','User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')
                         ->with('success','User deleted successfully.');
    }

    public function resetPasswordForm($id)
    {
        $user = User::findOrFail($id);
        return view('users.reset_password', compact('user'));
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Password reset successfully. Please share the new password manually.');
    }

    

}
