<?php

use App\Http\Controllers\Admin\GatewayConfigController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Livewire\ChangePassword;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\PricingTiers\PricingTierCreate;
use App\Livewire\PricingTiers\PricingTierEdit;
use App\Livewire\PricingTiers\PricingTierIndex;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root domain (lms.local): public landing + school registration.
Route::domain(config('app.domain'))->group(function () {
    Route::view('/', 'landing-page')->name('home');

    Route::get('/register-school', function () {
        return view('schools.register');
    })->name('schools.register');

    Route::post('/register-school', [SchoolController::class, 'store'])->name('schools.store');
});

// Admin panel (admin.lms.local): admin-only, school_id must be null.
Route::domain('admin.'.config('app.domain'))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', function () {
            return view('auth.admin-login');
        })->name('admin.login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/settings', function () {
            return view('admin.settings');
        })->name('admin.settings');

        Route::get('/logs', function () {
            return view('admin.logs');
        })->name('admin.logs');

        Route::resource('gateways', GatewayConfigController::class)->names('admin.gateways');
        Route::post('gateways/{gateway}/test-connection', [GatewayConfigController::class, 'testConnection'])->name('admin.gateways.test-connection');

        Route::get('/pricing-tiers', PricingTierIndex::class)->name('admin.pricing-tiers.index');
        Route::get('/pricing-tiers/create', PricingTierCreate::class)->name('admin.pricing-tiers.create');
        Route::get('/pricing-tiers/{tier}/edit', PricingTierEdit::class)->name('admin.pricing-tiers.edit');

        Route::get('/users', UserIndex::class)->name('admin.users.index');
        Route::get('/roles', RoleIndex::class)->name('admin.roles.index');
        Route::get('/roles/create', RoleCreate::class)->name('admin.roles.create');
        Route::get('/roles/{role}/edit', RoleEdit::class)->name('admin.roles.edit');
        Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');

        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('admin.logout');
    });
});

// School subdomains (schoolN.lms.local): school-scoped app.
Route::domain('{school}.'.config('app.domain'))->group(function () {
    Route::view('/', 'landing-page')->name('school.home');
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

// Webhook routes - publicly accessible with rate limiting
Route::middleware(['throttle:100,1', 'throttle:1000,60'])->group(function () {
    Route::post('/webhooks/midtrans', [PaymentWebhookController::class, 'handleMidtrans'])->name('webhooks.midtrans');
    Route::post('/webhooks/xendit', [PaymentWebhookController::class, 'handleXendit'])->name('webhooks.xendit');
});
