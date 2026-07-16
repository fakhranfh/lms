<?php

use App\Http\Controllers\DemoLmsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TierChangeController;
use App\Livewire\ChangePassword;
use App\Livewire\Courses\CourseBuilder;
use App\Livewire\Courses\CourseForm;
use App\Livewire\Courses\CoursesIndex;
use App\Livewire\Courses\LessonForm;
use App\Livewire\Courses\ModuleForm;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use Illuminate\Support\Facades\Route;

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

    Route::get('/tier-management', [TierChangeController::class, 'show'])->name('tier-management.show');
    Route::post('/tier-management/change', [TierChangeController::class, 'initiate'])->name('tier-management.change');
    Route::post('/tier-management/cancel', [TierChangeController::class, 'cancel'])->name('tier-management.cancel');

    Route::middleware('require-school')->group(function () {
        Route::get('/courses', CoursesIndex::class)->middleware('permission:courses.view')->name('courses.index');
        Route::get('/courses/create', CourseForm::class)->middleware('permission:courses.create')->name('courses.create');
        Route::get('/courses/{course}/edit', CourseForm::class)->middleware('permission:courses.edit')->name('courses.edit');
        Route::get('/courses/{course}', CourseBuilder::class)->middleware('permission:courses.view')->name('courses.show');

        Route::get('/courses/{course}/modules/create', ModuleForm::class)->middleware('permission:modules.create')->name('modules.create');
        Route::get('/modules/{module}/edit', ModuleForm::class)->middleware('permission:modules.edit')->name('modules.edit');

        Route::get('/modules/{module}/lessons/create', LessonForm::class)->middleware('permission:lessons.create')->name('lessons.create');
        Route::get('/lessons/{lesson}/edit', LessonForm::class)->middleware('permission:lessons.edit')->name('lessons.edit');
    });
});

Route::get('/demo-lms/login/{token}', [DemoLmsController::class, 'login'])->name('demo-lms.login');
