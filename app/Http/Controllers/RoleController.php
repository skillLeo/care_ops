<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::with('category')
            ->orderBy('permission_category_id')
            ->orderBy('display_name')
            ->get();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|regex:/^\S+$/',
            'display_name' => 'required|string|max:50|unique:roles,display_name|regex:/^\S.*\S$/',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => trim($request->name),
            'display_name' => trim($request->display_name),
        ]);

        $role->permissions()->sync($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function show($id)
    {
        $role = Role::with(['permissions.category', 'users'])->findOrFail($id);

        $users = User::whereDoesntHave('roles', function ($query) use ($role) {
            $query->where('roles.id', $role->id);
        })->get();

        return view('roles.show', compact('role', 'users'));
    }

    public function edit($id)
    {
        $role = Role::with('permissions', 'users')->findOrFail($id);
        $permissions = Permission::with('category')
            ->orderBy('permission_category_id')
            ->orderBy('display_name')
            ->get();
        $users = User::all();

        return view('roles.edit', compact('role', 'permissions', 'users'));
    }


    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'display_name' => 'required|unique:roles,display_name,' . $role->id,
        ]);

        $role->update($request->only('name', 'display_name'));

        $permissions = $request->input('permissions', []);
        $role->permissions()->sync($permissions);

        $users = $request->input('users', []);
        $role->users()->sync($users);
        User::whereIn('id', $users)
            ->whereNull('role_id')
            ->update(['role_id' => $role->id]);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }



    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')
                         ->with('success','Role deleted successfully.');
    }

    public function addUsers(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        // Assign selected users to the role
        $role->users()->syncWithoutDetaching($request->users);
        User::whereIn('id', $request->users)
            ->whereNull('role_id')
            ->update(['role_id' => $role->id]);

        return redirect()->route('roles.show', $role->id)->with('success', 'Users added to the role successfully!');
    }
}
