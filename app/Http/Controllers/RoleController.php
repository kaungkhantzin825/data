<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array'
        ]);

        $permissions = Permission::whereIn('name', $request->permissions ?? [])->get();
        $role->syncPermissions($permissions);

        return redirect()->back()->with('success', 'Role permissions updated successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id
        ]);

        $role->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ]);

        Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'menu_name' => 'nullable|string|max:255'
        ]);

        Permission::create([
            'name' => $request->name,
            'menu_name' => $request->menu_name ?: null,
            'guard_name' => 'web'
        ]);

        return redirect()->back()->with('success', 'Permission created successfully.');
    }
}
