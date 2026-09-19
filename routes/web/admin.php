<?php

use App\Http\Controllers\Admin\GatewayConfigController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\PermissionController;
use App\Livewire\Admin\AdminStorageDashboard;
use App\Livewire\Admin\AuditLogTable;
use App\Livewire\Admin\BillingSettings;
use App\Livewire\Admin\CacheManagement;
use App\Livewire\Admin\TransactionShow;
use App\Livewire\Admin\TransactionSummary;
use App\Livewire\Admin\TransactionTable;
use App\Livewire\PricingTiers\PricingTierCreate;
use App\Livewire\PricingTiers\PricingTierEdit;
use App\Livewire\PricingTiers\PricingTierIndex;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Schools\SchoolEdit;
use App\Livewire\Schools\SchoolIndex;
use App\Livewire\Schools\SchoolTierHistory;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserImport;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserPhotoUpload;
use App\Livewire\Users\UserRoles;
use App\Services\R2StorageService;
use App\Support\RootDomains;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Admin panel (admin.lms.local) and any APP_EXTRA_DOMAINS: admin-only, school_id must be null.
// The primary domain (index 0) keeps the canonical route names; extra domains
// get suffixed names (see RootDomains) so they can be served directly.
foreach (RootDomains::all() as $index => $rootDomain) {
    $suffix = $index === 0 ? '' : ".alt{$index}";

    Route::domain('admin.'.$rootDomain)->group(function () use ($suffix) {
        Route::get('/', function () use ($suffix) {
            return redirect()->route(Auth::check() ? "admin.dashboard{$suffix}" : "admin.login{$suffix}");
        })->name("admin.home{$suffix}");

        Route::middleware('guest')->group(function () use ($suffix) {
            Route::get('/login', function () {
                return view('auth.admin-login');
            })->name("admin.login{$suffix}");
            Route::post('/login', [AdminLoginController::class, 'store'])->name("admin.login.store{$suffix}");
        });

        Route::middleware(['auth', 'role:Admin'])->group(function () use ($suffix) {
            Route::get('/dashboard', function (R2StorageService $r2Service) {
                return view('admin.dashboard', [
                    'quota' => $r2Service->checkSchoolQuota(''),
                ]);
            })->name("admin.dashboard{$suffix}");

            Route::resource('gateways', GatewayConfigController::class)->names("admin.gateways{$suffix}");
            Route::post('gateways/{gateway}/test-connection', [GatewayConfigController::class, 'testConnection'])->name("admin.gateways.test-connection{$suffix}");
            Route::get('gateways/{gateway}/test-result', [GatewayConfigController::class, 'testResult'])->name("admin.gateways.test-result{$suffix}");
            Route::post('gateways/{gateway}/test-result/simulate', [GatewayConfigController::class, 'simulatePayment'])->name("admin.gateways.test-result.simulate{$suffix}");

            Route::get('/pricing-tiers', PricingTierIndex::class)->name("admin.pricing-tiers.index{$suffix}");
            Route::get('/pricing-tiers/create', PricingTierCreate::class)->name("admin.pricing-tiers.create{$suffix}");
            Route::get('/pricing-tiers/{tier}/edit', PricingTierEdit::class)->name("admin.pricing-tiers.edit{$suffix}");

            Route::get('/settings', BillingSettings::class)->name("admin.settings.index{$suffix}");

            Route::get('/audit-logs', AuditLogTable::class)->name("admin.audit-logs.index{$suffix}");

            Route::get('/transactions', TransactionTable::class)->name("admin.transactions.index{$suffix}");
            Route::get('/transactions/summary', TransactionSummary::class)->name("admin.transactions.summary{$suffix}");
            Route::get('/transactions/{transaction}', TransactionShow::class)->name("admin.transactions.show{$suffix}");

            Route::get('/storage', AdminStorageDashboard::class)->name("admin.storage.dashboard{$suffix}");

            Route::middleware('local-only')->group(function () use ($suffix) {
                Route::get('/cache', CacheManagement::class)->name("admin.cache.index{$suffix}");
            });

            Route::get('/schools', SchoolIndex::class)->name("admin.schools.index{$suffix}");
            Route::get('/schools/{school}/edit', SchoolEdit::class)->name("admin.schools.edit{$suffix}");
            Route::get('/schools/{school}/tier-history', SchoolTierHistory::class)->name("admin.schools.tier-history{$suffix}");

            Route::get('/demo-credentials', function () {
                return view('admin.demo-credentials');
            })->name("admin.demo-credentials{$suffix}");

            Route::get('/users', UserIndex::class)->name("admin.users.index{$suffix}");
            Route::get('/users/create', UserForm::class)->name("admin.users.create{$suffix}");
            Route::get('/users/import/{role}', UserImport::class)->whereIn('role', ['teacher', 'student'])->name("admin.users.import{$suffix}");
            Route::get('/users/photos', UserPhotoUpload::class)->name("admin.users.photos{$suffix}");
            Route::get('/users/{id}/edit', UserForm::class)->name("admin.users.edit{$suffix}");
            Route::get('/users/{id}/roles', UserRoles::class)->name("admin.users.roles.edit{$suffix}");
            Route::get('/roles', RoleIndex::class)->name("admin.roles.index{$suffix}");
            Route::get('/roles/create', RoleCreate::class)->name("admin.roles.create{$suffix}");
            Route::get('/roles/{role}/edit', RoleEdit::class)->name("admin.roles.edit{$suffix}");
            Route::get('/permissions', [PermissionController::class, 'index'])->name("admin.permissions.index{$suffix}");

            Route::post('/logout', function (Request $request) use ($suffix) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route("admin.login{$suffix}");
            })->name("admin.logout{$suffix}");
        });
    });
}
