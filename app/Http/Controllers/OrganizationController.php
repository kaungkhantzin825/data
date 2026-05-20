<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Only Company Super Admin can manage organizations.
     */
    public function store(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Company Super Admin') && !$authUser->is_admin) {
            return back()->withErrors(['error' => 'Only Company Super Admins can create organizations.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tenantId = $authUser->tenant_id;

        // Check for duplicate name within same tenant
        $exists = Organization::where('tenant_id', $tenantId)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'An organization with this name already exists in your company.']);
        }

        Organization::create([
            'name'      => $request->name,
            'tenant_id' => $tenantId,
        ]);

        return back()->with('success', 'Organization created successfully.');
    }

    public function update(Request $request, Organization $organization)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Company Super Admin') && !$authUser->is_admin) {
            return back()->withErrors(['error' => 'Only Company Super Admins can update organizations.']);
        }

        // Ensure Super Admin can only edit orgs in their own tenant
        if ($organization->tenant_id !== $authUser->tenant_id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization->update(['name' => $request->name]);

        return back()->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Company Super Admin') && !$authUser->is_admin) {
            return back()->withErrors(['error' => 'Only Company Super Admins can delete organizations.']);
        }

        if ($organization->tenant_id !== $authUser->tenant_id) {
            abort(403, 'Unauthorized.');
        }

        // Detach all users before deleting
        $organization->users()->detach();
        $organization->delete();

        return back()->with('success', 'Organization deleted successfully.');
    }
}
