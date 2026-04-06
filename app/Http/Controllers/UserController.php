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
        $usersQuery = User::on('mysql_central')->with(['roles', 'tenant', 'plan', 'staff'])->latest();

        if (!auth()->user()->is_admin) {
            $tenantId = auth()->user()->tenant_id ?: auth()->id();
            $usersQuery->where('tenant_id', $tenantId);
        } else {
            $usersQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Staff');
            });
        }

        $users = $usersQuery->get();
        $roles = Role::on('mysql_central')->with('permissions')->get();
        $permissions = Permission::on('mysql_central')->get();
        $plans = Plan::on('mysql_central')->get();

        return Inertia::render('UserManagement', [
            'users'       => $users,
            'roles'       => $roles,
            'permissions' => $permissions,
            'plans'       => $plans
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
            'name'     => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            // Rule::unique(User::class) picks up User->$connection = 'mysql_central'
            'email'    => ['required', 'email', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles'    => 'nullable|array',
            'plan_id'  => ['nullable', Rule::exists(Plan::class, 'id')],
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
            'name'            => $request->name,
            'company_name'    => $request->company_name,
            'phone'           => $request->phone,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'is_active'       => $authUser->is_admin ? true : false,
            'tenant_id'       => $newTenantId,
            'plan_id'         => $authUser->is_admin ? $request->plan_id : $authUser->plan_id,
            'plan_expired_at' => $calculatedPlanExpiredAt,
        ]);

        if ($authUser->is_admin && $request->has('roles') && count($request->roles) > 0) {
            $user->syncRoles($request->roles);
        } else {
            $user->assignRole('Staff');
        }

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'user_created',
            'description'=> "Created new user: {$user->email}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            // Rule::unique(User::class) picks up User->$connection = 'mysql_central'
            'email'    => ['required', 'email', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles'    => 'nullable|array',
            'plan_id'  => ['nullable', Rule::exists(Plan::class, 'id')],
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
            $user->syncRoles($request->roles);
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

            if (!$user->is_active && $user->hasRole('User') && empty($user->plan_id)) {
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
}
