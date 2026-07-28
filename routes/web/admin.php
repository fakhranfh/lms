<?php

use App\Http\Controllers\Admin\GatewayConfigController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\PermissionController;
use App\Livewire\Admin\AdminStorageDashboard;
use App\Livewire\Admin\AdminStorageMaterials;
use App\Livewire\Admin\AuditLogTable;
use App\Livewire\PricingTiers\PricingTierCreate;
use App\Livewire\PricingTiers\PricingTierEdit;
use App\Livewire\PricingTiers\PricingTierIndex;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Schools\SchoolEdit;
use App\Livewire\Schools\SchoolIndex;
use App\Livewire\Schools\SchoolTierHistory;
use App\Livewire\Users\UserIndex;
use App\Services\R2StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Admin panel (admin.lms.local): admin-only, school_id must be null.
Route::domain('admin.'.config('app.domain'))->group(function () {
    Route::get('/', function () {
        return redirect()->route(Auth::check() ? 'admin.dashboard' : 'admin.login');
    })->name('admin.home');

    Route::middleware('guest')->group(function () {
        Route::get('/login', function () {
            return view('auth.admin-login');
        })->name('admin.login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
    });

    Route::middleware(['auth', 'role:Admin'])->group(function () {
        Route::get('/dashboard', function (R2StorageService $r2Service) {
            return view('admin.dashboard', [
                'quota' => $r2Service->checkSchoolQuota(''),
            ]);
        })->name('admin.dashboard');

        Route::resource('gateways', GatewayConfigController::class)->names('admin.gateways');
        Route::post('gateways/{gateway}/test-connection', [GatewayConfigController::class, 'testConnection'])->name('admin.gateways.test-connection');
        Route::get('gateways/{gateway}/test-result', [GatewayConfigController::class, 'testResult'])->name('admin.gateways.test-result');

        Route::get('/pricing-tiers', PricingTierIndex::class)->name('admin.pricing-tiers.index');
        Route::get('/pricing-tiers/create', PricingTierCreate::class)->name('admin.pricing-tiers.create');
        Route::get('/pricing-tiers/{tier}/edit', PricingTierEdit::class)->name('admin.pricing-tiers.edit');

        Route::get('/audit-logs', AuditLogTable::class)->name('admin.audit-logs.index');

        Route::get('/storage', AdminStorageDashboard::class)->name('admin.storage.dashboard');
        Route::get('/storage/materials', AdminStorageMaterials::class)->name('admin.storage.materials');

        Route::get('/schools', SchoolIndex::class)->name('admin.schools.index');
        Route::get('/schools/{school}/edit', SchoolEdit::class)->name('admin.schools.edit');
        Route::get('/schools/{school}/tier-history', SchoolTierHistory::class)->name('admin.schools.tier-history');

        Route::get('/demo-credentials', function () {
            return view('admin.demo-credentials');
        })->name('admin.demo-credentials');

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
