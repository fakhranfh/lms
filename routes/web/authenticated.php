<?php

use App\Http\Controllers\DemoLmsController;
use App\Http\Controllers\ForumCommentLikeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolPaymentController;
use App\Http\Controllers\TierChangeController;
use App\Http\Controllers\UserAvailabilityController;
use App\Http\Controllers\UserLoginLinkController;
use App\Livewire\ChangePassword;
use App\Livewire\Courses\AssessmentForm;
use App\Livewire\Courses\AssessmentIndex;
use App\Livewire\Courses\AssessmentPersonalShow;
use App\Livewire\Courses\AssessmentTeamShow;
use App\Livewire\Courses\CourseComingSoon;
use App\Livewire\Courses\CourseForm;
use App\Livewire\Courses\CoursesIndex;
use App\Livewire\Courses\ForumIndex;
use App\Livewire\Courses\ForumThreadShow;
use App\Livewire\Courses\GroupsManage;
use App\Livewire\Courses\SessionForm;
use App\Livewire\Courses\SessionsIndex;
use App\Livewire\Courses\SyllabusForm;
use App\Livewire\Courses\SyllabusIndex;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\MediaLibrary\MediaLibraryIndex;
use App\Livewire\MyTransactions;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserImport;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserPhotoUpload;
use App\Livewire\Users\UserRoles;
use App\Models\Course;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment/{transaction}', [SchoolPaymentController::class, 'index'])->name('school.payment.index');
    Route::post('/payment/{transaction}/confirm', [SchoolPaymentController::class, 'confirm'])->name('school.payment.confirm');
    Route::post('/payment/{transaction}/simulate', [SchoolPaymentController::class, 'simulate'])->name('school.payment.simulate');
    Route::get('/payment/{transaction}/stream', [SchoolPaymentController::class, 'stream'])->name('school.payment.stream');

    Route::get('/transactions', MyTransactions::class)->name('transactions.index');
});

Route::middleware(['auth', 'verified', 'redirect-if-no-school'])->group(function () {
    Route::get('/edit-profile', EditProfile::class)->name('edit-profile');
    Route::get('/edit-profile/verify-email', [ProfileController::class, 'verifyEmailChange'])->name('profile.verify-email-change');

    Route::get('/change-password', ChangePassword::class)->name('change-password');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/roles', RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', RoleCreate::class)->middleware(['permission:roles.view', 'permission:roles.create'])->name('roles.create');
    Route::get('/roles/{role}/edit', RoleEdit::class)->middleware(['permission:roles.view', 'permission:roles.update'])->name('roles.edit');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

    Route::get('/users', UserIndex::class)->middleware('permission:users.view')->name('users.index');
    Route::get('/users/check-availability', [UserAvailabilityController::class, 'check'])->name('users.check-availability');
    Route::get('/users/create', UserForm::class)->middleware('permission:users.create')->name('users.create');
    Route::get('/users/import/{role}', UserImport::class)->whereIn('role', ['teacher', 'student'])->middleware('permission:users.import')->name('users.import');
    Route::get('/users/photos', UserPhotoUpload::class)->middleware('permission:users.edit')->name('users.photos');
    Route::get('/users/{id}/edit', UserForm::class)->middleware('permission:users.edit')->name('users.edit');
    Route::get('/users/{id}/roles', UserRoles::class)->middleware('permission:users.assign-roles')->name('users.roles.edit');

    Route::get('/tier-management', [TierChangeController::class, 'show'])->name('tier-management.show');
    Route::post('/tier-management/change', [TierChangeController::class, 'initiate'])->name('tier-management.change');
    Route::post('/tier-management/cancel', [TierChangeController::class, 'cancel'])->name('tier-management.cancel');

    Route::middleware('require-school')->group(function () {
        Route::get('/courses', CoursesIndex::class)->middleware('permission:courses.view')->name('courses.index');
        Route::get('/courses/create', CourseForm::class)->middleware('permission:courses.create')->name('courses.create');
        Route::get('/courses/{course}/edit', CourseForm::class)->middleware('permission:courses.edit')->name('courses.edit');
        Route::get('/courses/{course}', fn (Course $course) => redirect()->route('sessions.index', $course))
            ->middleware('permission:courses.view')
            ->name('courses.show');

        Route::get('/courses/{course}/sessions', SessionsIndex::class)->middleware('permission:sessions.view')->name('sessions.index');
        Route::get('/courses/{course}/sessions/create', SessionForm::class)->middleware('permission:sessions.create')->name('sessions.create');
        Route::get('/sessions/{session}/edit', SessionForm::class)->middleware('permission:sessions.edit')->name('sessions.edit');

        Route::get('/courses/{course}/syllabus', SyllabusIndex::class)->middleware('permission:syllabus.view')->name('syllabus.index');
        Route::get('/courses/{course}/syllabus/edit', SyllabusForm::class)->middleware('permission:syllabus.edit')->name('syllabus.edit');

        Route::get('/courses/{course}/forum', ForumIndex::class)->middleware('permission:forum.view')->name('forum.index');
        Route::get('/courses/{course}/forum/threads/{thread}', ForumThreadShow::class)->middleware('permission:forum.view')->name('forum.thread.show');
        Route::post('/forum/comments/{comment}/toggle-like', [ForumCommentLikeController::class, 'toggle'])->middleware('permission:forum.create')->name('forum.comment.toggle-like');

        Route::get('/courses/{course}/assessments', AssessmentIndex::class)->middleware('permission:assessment.view')->name('assessments.index');
        Route::get('/courses/{course}/assessments/create/{type}', AssessmentForm::class)->whereIn('type', ['personal', 'team'])->middleware('permission:assessment.create')->name('assessments.create');
        Route::get('/assessments/{assessment}/edit', AssessmentForm::class)->middleware('permission:assessment.edit')->name('assessments.edit');
        Route::get('/assessments/{assessment}/personal', AssessmentPersonalShow::class)->middleware('permission:assessment.view')->name('assessments.personal.show');
        Route::get('/assessments/{assessment}/team', AssessmentTeamShow::class)->middleware('permission:assessment.view')->name('assessments.team.show');

        Route::get('/courses/{course}/groups', GroupsManage::class)->middleware('permission:groups.manage')->name('groups.manage');

        Route::get('/courses/{course}/tabs/{tab}', CourseComingSoon::class)->middleware('permission:courses.view')->name('course-tabs.coming-soon');

        Route::get('/media-library', MediaLibraryIndex::class)->middleware('permission:media.view')->name('media-library.index');
    });
});

Route::get('/demo-lms/login/{token}', [DemoLmsController::class, 'login'])->name('demo-lms.login');

Route::get('/login-link/{token}', [UserLoginLinkController::class, 'login'])->name('user-login-link.login');
