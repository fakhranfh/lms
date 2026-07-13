<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Livewire\ChangePassword;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('', function () {
        return view('auth.login');
    })->name('login');
});

Route::view('/', 'landing-page');

// Root domain (lms.local): public landing + school registration.
Route::domain(config('app.domain'))->group(function () {
    Route::get('/register-school', function () {
        return view('tenants.register');
    })->name('tenants.register');

    Route::post('/register-school', [TenantController::class, 'store'])->name('tenants.store');
});

// Admin panel (admin.lms.local): admin-only, tenant_id must be null.
Route::domain('admin.'.config('app.domain'))->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');

    Route::get('/admin/logs', function () {
        return view('admin.logs');
    })->name('admin.logs');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/edit-profile', EditProfile::class)->name('edit-profile');
    Route::get('/edit-profile/verify-email', [ProfileController::class, 'verifyEmailChange'])->name('profile.verify-email-change');

    Route::get('/change-password', ChangePassword::class)->name('change-password');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/roles', RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', RoleCreate::class)->middleware(['permission:roles.view', 'permission:roles.create'])->name('roles.create');
    Route::get('/roles/{role}/edit', RoleEdit::class)->middleware(['permission:roles.view', 'permission:roles.update'])->name('roles.edit');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

    Route::get('/users', UserIndex::class)->middleware('permission:users.view')->name('users.index');
    Route::get('/users/{id}/roles', UserRoles::class)->middleware('permission:users.assign-roles')->name('users.roles.edit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
