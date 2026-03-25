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
    /**
     * Handle sign-in form submission.
     */
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
                
                // Log failed login attempt due to inactive account
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
            
            // Log successful login
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in successfully',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return redirect()->intended(route('dashboard'));
        }

        // Log failed login attempt
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

    /**
     * Handle registration form submission.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => false,
        ]);

        // Assign default role per requirements: 'User'
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->assignRole($role);

        return redirect()->route('login')->withErrors([
            'email' => 'Registration complete! Your account is pending admin approval.'
        ]);
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();
        
        // Log logout activity
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
