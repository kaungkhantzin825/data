<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    public function syncPermissions(Request $request, Role $role)
    {
        if (!auth()->user()->is_admin && is_null($role->tenant_id)) {
            return redirect()->back()->with('error', 'You cannot modify a global system role.');
        }

        $request->validate([
            'permissions' => 'array'
        ]);

        $permissions = Permission::whereIn('name', $request->permissions ?? [])->get();
        $role->syncPermissions($permissions);

        return redirect()->back()->with('success', 'Role permissions updated successfully.');
    }

    public function update(Request $request, Role $role)
    {
        if (!auth()->user()->is_admin && is_null($role->tenant_id)) {
            return redirect()->back()->with('error', 'You cannot modify a global system role.');
        }
        $tenantId = auth()->user()->is_admin ? null : (auth()->user()->tenant_id ?: auth()->id());
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('roles', 'name')->ignore($role->id)->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)->where('guard_name', 'web');
                }),
            ]
        ]);

        $role->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->is_admin ? null : (auth()->user()->tenant_id ?: auth()->id());
        
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique across the same team
                \Illuminate\Validation\Rule::unique('roles', 'name')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)->where('guard_name', 'web');
                }),
            ]
        ]);

        Role::create(['name' => $request->name, 'guard_name' => 'web', 'tenant_id' => $tenantId]);

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

    public function destroy(Role $role)
    {
        // Prevent deletion of critical system roles
        $protectedRoles = ['Pipline Admin', 'Staff', 'Company Admin', 'Company Super Admin'];
        
        if (in_array($role->name, $protectedRoles)) {
            return redirect()->back()->with('error', 'Cannot delete system role: ' . $role->name);
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
