<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantFieldController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Welcome');
})->name('welcome');


Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login')->middleware('guest');

Route::post('/login', [AuthController::class, 'store'])->name('login.store');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register')->middleware('guest');

Route::post('/register', [AuthController::class, 'register'])->name('register.store');


Route::post('/logout', [AuthController::class, 'destroy'])->name('logout')->middleware('auth');

Route::get('/dashboard', [LeadController::class, 'dashboard'])->name('dashboard')->middleware(['auth', 'tenant']);

// Route::middleware('auth')->group(function () {
//     Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
//     Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
//     Route::get('/leads/upload', [LeadController::class, 'upload'])->name('leads.upload');
    

// });



Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::get('/leads/upload', [LeadController::class, 'upload'])->name('leads.upload');
    
    Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');

    Route::get('/settings/tenant-fields', [TenantFieldController::class, 'index'])->name('tenant.fields.index');
    Route::post('/settings/tenant-fields', [TenantFieldController::class, 'store'])->name('tenant.fields.store');
    Route::put('/settings/tenant-fields/{id}', [TenantFieldController::class, 'updateOption'])->name('tenant.fields.updateOption');
    Route::delete('/settings/tenant-fields/{id}', [TenantFieldController::class, 'destroy'])->name('tenant.fields.destroy');

    Route::get('/plans', [\App\Http\Controllers\PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [\App\Http\Controllers\PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [\App\Http\Controllers\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\PlanController::class, 'destroy'])->name('plans.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    
  
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions');
    
    Route::post('/permissions', [RoleController::class, 'storePermission']);

    
    Route::get('/settings', [\App\Http\Controllers\ProfileController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\ProfileController::class, 'update'])->name('settings.update');
    Route::post('/settings/backup', [\App\Http\Controllers\ProfileController::class, 'createBackup'])->name('settings.backup.create');
    Route::get('/settings/backup/{id}/download', [\App\Http\Controllers\ProfileController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::delete('/settings/backup/{id}', [\App\Http\Controllers\ProfileController::class, 'deleteBackup'])->name('settings.backup.delete');
});
