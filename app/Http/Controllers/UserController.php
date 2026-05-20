<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ActivityLog;

class UserController extends Controller
{
    public function index()
    {
        $usersQuery = User::on('mysql_central')->with(['allRoles', 'tenant', 'plan', 'staff', 'organizations', 'manager'])->latest();

        if (!auth()->user()->is_admin) {
            $tenantId = auth()->user()->tenant_id ?: auth()->id();
            $usersQuery->where('tenant_id', $tenantId);
            
            $authUser = auth()->user();
            if ($authUser->hasRole('Company Super Admin')) {
                // Sees everyone in the tenant
            } elseif ($authUser->hasRole('Manager')) {
                // A Manager should only see Users (Staff) in their assigned organizations
                $orgIds = $authUser->organizations()->pluck('organizations.id')->toArray();
                $usersQuery
                    ->whereHas('organizations', function($q2) use ($orgIds) {
                        $q2->whereIn('organizations.id', $orgIds);
                    })
                    // Exclude Super Admins and other Managers from Manager's view
                    ->whereDoesntHave('allRoles', function($q2) {
                        $q2->whereIn('name', ['Company Super Admin', 'Manager']);
                    });
            } else {
                // A User should only see themselves
                $usersQuery->where('id', $authUser->id);
            }
        } else {
            $usersQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Staff');
            });
        }

        $users = $usersQuery->get();
        $rolesQuery = Role::on('mysql_central')->with('permissions');
        if (!auth()->user()->is_admin) {
            $rolesQuery->where('tenant_id', $tenantId);
            
            if (auth()->user()->hasRole('Company Super Admin')) {
                $rolesQuery->whereIn('name', ['Manager', 'User']);
            } elseif (auth()->user()->hasRole('Manager')) {
                $rolesQuery->where('name', 'User');
            } else {
                // User shouldn't be able to create users anyway, but just in case
                $rolesQuery->where('id', -1); 
            }
        } else {
            $rolesQuery->whereNull('tenant_id');
        }
        $roles = $rolesQuery->get();
        $permissions = Permission::on('mysql_central')->get();
        $plans = Plan::on('mysql_central')->get();

        // Scope organizations: Manager sees only their own orgs; Super Admin sees all tenant orgs
        if (!auth()->user()->is_admin && auth()->user()->hasRole('Manager')) {
            $organizations = auth()->user()->organizations()->get(['organizations.id', 'organizations.name']);
        } else {
            $organizations = \App\Models\Organization::where('tenant_id', $tenantId ?? null)->get();
        }

        return Inertia::render('UserManagement', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'plans' => $plans,
            'organizations' => $organizations
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->is_admin) {
            $tenantId = $authUser->tenant_id ?: $authUser->id;
            $limit = $authUser->plan ? $authUser->plan->staff_limit : 5;
            $currentStaffCount = User::on('mysql_central')->where('tenant_id', $tenantId)->count();
            if ($currentStaffCount >= $limit) {
                return back()->withErrors(['email' => 'Staff limit reached for your account (Max: ' . $limit . '). Please upgrade your plan.']);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            // Rule::unique(User::class) picks up User->$connection = 'mysql_central'
            'email' => ['required', 'email', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'nullable|array',
            'organizations' => 'nullable|array',
            'plan_id' => ['nullable', Rule::exists(Plan::class, 'id')],
        ]);

        $calculatedPlanExpiredAt = null;
        if ($authUser->is_admin && $request->plan_id) {
            $plan = \App\Models\Plan::find($request->plan_id);
            if ($plan && $plan->duration_in_days) {
                $calculatedPlanExpiredAt = now()->addDays($plan->duration_in_days)->toDateString();
            }
        } elseif (!$authUser->is_admin) {
            $calculatedPlanExpiredAt = $authUser->plan_expired_at;
        }
        $newTenantId = $authUser->is_admin
            ? null
            : ($authUser->tenant_id ?: $authUser->id);

        $user = User::create([
            'name'           => $request->name,
            'company_name'   => $request->company_name,
            'phone'          => $request->phone,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'is_active'      => $authUser->is_admin ? true : true, // Active by default within tenant
            'tenant_id'      => $newTenantId,
            'plan_id'        => $authUser->is_admin ? $request->plan_id : $authUser->plan_id,
            'plan_expired_at'=> $calculatedPlanExpiredAt,
            'created_by'     => $authUser->id,
            // Set manager_id: if creator is a Manager, link staff to them
            'manager_id'     => $authUser->hasRole('Manager') ? $authUser->id : null,
        ]);

        // Refresh to get tenant_id generated by UserObserver
        $user->refresh();
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($user->tenant_id);

        if ($request->has('roles') && count($request->roles) > 0) {
            $user->syncRoles($request->roles);
        } else {
            $user->assignRole('User'); // Default to User instead of Staff
        }

        // Sync organizations
        if ($authUser->hasRole('Company Super Admin')) {
            if ($request->has('organizations')) {
                $user->organizations()->sync($request->organizations);
            }
        } elseif ($authUser->hasRole('Manager')) {
            // Managers can only assign organizations they belong to
            $allowedOrgs = $authUser->organizations()->pluck('organizations.id')->toArray();
            $requestedOrgs = $request->input('organizations', []);
            $validOrgs = array_intersect($requestedOrgs, $allowedOrgs);
            
            // If they didn't specify, default to all their orgs (or let UI handle it)
            if (empty($validOrgs) && empty($requestedOrgs)) {
                $validOrgs = $allowedOrgs; 
            }
            $user->organizations()->sync($validOrgs);
        }

        setPermissionsTeamId($originalTeamId);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user_created',
            'description' => "Created new user: {$user->email}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            // Rule::unique(User::class) picks up User->$connection = 'mysql_central'
            'email' => ['required', 'email', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => 'nullable|array',
            'organizations' => 'nullable|array',
            'plan_id' => ['nullable', Rule::exists(Plan::class, 'id')],
        ]);

        $user->name = $request->name;
        $user->company_name = $request->company_name;
        $user->phone = $request->phone;
        $user->email = $request->email;

        if (auth()->user()->is_admin) {
            if ($request->plan_id != $user->plan_id) {
                $user->plan_id = $request->plan_id;
                if ($request->plan_id) {
                    $plan = \App\Models\Plan::find($request->plan_id);
                    if ($plan && $plan->duration_in_days) {
                        $user->plan_expired_at = now()->addDays($plan->duration_in_days)->toDateString();
                    }
                } else {
                    $user->plan_expired_at = null;
                }
            }
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        if ($request->has('roles')) {
            $user->refresh();
            $originalTeamId = getPermissionsTeamId();
            if ($user->tenant_id) {
                setPermissionsTeamId($user->tenant_id);
            }
            $user->syncRoles($request->roles);
            setPermissionsTeamId($originalTeamId);
        }

        $authUser = auth()->user();
        if ($authUser->hasRole('Company Super Admin')) {
            if ($request->has('organizations')) {
                $user->organizations()->sync($request->organizations);
            }
        } elseif ($authUser->hasRole('Manager')) {
            if ($request->has('organizations')) {
                $allowedOrgs = $authUser->organizations()->pluck('organizations.id')->toArray();
                $validOrgs = array_intersect($request->organizations, $allowedOrgs);
                $user->organizations()->sync($validOrgs);
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user_updated',
            'description' => "Updated user profile: {$user->email}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id !== auth()->id()) {
            $email = $user->email;
            $user->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'user_deleted',
                'description' => "Deleted user: {$email}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id !== auth()->id()) {

            if (!$user->is_active && $user->hasRole('Company Super Admin') && empty($user->plan_id)) {
                return back()->withErrors(['statusError' => 'Cannot approve this user. Please click "Edit" and assign a SaaS Plan first!']);
            }

            $user->is_active = !$user->is_active;
            $user->save();

            $status = $user->is_active ? 'Activated' : 'Deactivated';
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'user_status_changed',
                'description' => "{$status} user account: {$user->email}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    /**
     * Transfer a Staff member to a different Organization and/or Manager.
     * Only Company Super Admin can do this.
     */
    public function transferStaff(Request $request, User $user)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('Company Super Admin') && !$authUser->is_admin) {
            return back()->withErrors(['error' => 'Only Company Super Admins can transfer staff.']);
        }

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'manager_id'      => 'nullable|exists:users,id',
        ]);

        // Ensure the target organization belongs to this Super Admin's tenant
        $org = \App\Models\Organization::findOrFail($request->organization_id);
        if ($org->tenant_id !== $authUser->tenant_id) {
            return back()->withErrors(['error' => 'You can only assign staff to organizations within your company.']);
        }

        // Reassign the organization
        $user->organizations()->sync([$request->organization_id]);

        // Reassign the manager
        $user->manager_id = $request->manager_id;
        $user->save();

        ActivityLog::create([
            'user_id'     => $authUser->id,
            'action'      => 'staff_transferred',
            'description' => "Transferred {$user->name} to organization: {$org->name}",
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        return back()->with('success', "{$user->name} has been transferred to {$org->name} successfully.");
    }
}
