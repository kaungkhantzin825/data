<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                 \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'login_failed',
                    'description' => 'Login attempt failed: Account is inactive',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                
                return back()->withErrors([
                    'email' => 'Your account is pending admin approval or has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in successfully',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return redirect()->intended(route('dashboard'));
        }

        \App\Models\ActivityLog::create([
            'user_id' => null,
            'action' => 'login_failed',
            'description' => 'Failed login attempt for email: ' . $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => false,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->assignRole($role);

        return redirect()->route('login')->withErrors([
            'email' => 'Registration complete! Your account is pending admin approval.'
        ]);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'description' => 'User logged out',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}
