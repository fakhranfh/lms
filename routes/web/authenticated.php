<?php

use App\Http\Controllers\DemoLmsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TierChangeController;
use App\Livewire\Assignments\AssignmentForm;
use App\Livewire\Assignments\AssignmentsIndex;
use App\Livewire\ChangePassword;
use App\Livewire\Courses\CourseBuilder;
use App\Livewire\Courses\CourseForm;
use App\Livewire\Courses\CoursesIndex;
use App\Livewire\Courses\LessonForm;
use App\Livewire\Courses\LessonViewer;
use App\Livewire\Courses\ModuleForm;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Submissions\EssaySubmissionForm;
use App\Livewire\Submissions\GradingQueueTable;
use App\Livewire\Submissions\MySubmissions;
use App\Livewire\Submissions\OverrideScoreModal;
use App\Livewire\Submissions\SubmissionShow;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use Illuminate\Support\Facades\Route;

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

        // Student-facing lesson viewing
        Route::get('/lessons/{lesson}', LessonViewer::class)->name('lessons.show');

        Route::get('/assignments', AssignmentsIndex::class)->middleware('permission:assignments.view')->name('assignments.index');
        Route::get('/lessons/{lesson}/assignments/create', AssignmentForm::class)->middleware('permission:assignments.create')->name('assignments.create');
        Route::get('/assignments/{assignment}/edit', AssignmentForm::class)->middleware('permission:assignments.edit')->name('assignments.edit');

        // Student-facing submission — students only have submissions.view, not a dedicated
        // create permission, so this mirrors the lesson-viewing route: auth + require-school
        // only, with ownership/publish checks enforced inside EssaySubmissionForm::mount().
        Route::get('/lessons/{lesson}/assignments/{assignment}/submit', EssaySubmissionForm::class)->name('submissions.create');

        Route::get('/submissions/{submission}', SubmissionShow::class)->name('submissions.show');
        Route::get('/my-submissions', MySubmissions::class)->middleware('permission:submissions.view')->name('submissions.index');
        Route::get('/grading-queue', GradingQueueTable::class)->middleware('permission:submissions.grade')->name('grading-queue.index');
        Route::get('/submissions/{submission}/override', OverrideScoreModal::class)->middleware('permission:submissions.override-grade')->name('submissions.override');

        Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:3,1')->name('submissions.store');
        Route::get('/submissions/{submission}/status', [SubmissionController::class, 'show'])->name('submissions.status');
        Route::patch('/submissions/{submission}/override', [SubmissionController::class, 'override'])->middleware('permission:submissions.override-grade')->name('submissions.override.update');
        Route::post('/submissions/{submission}/retry', [SubmissionController::class, 'retry'])->middleware('permission:submissions.grade')->name('submissions.retry');
    });
});

Route::get('/demo-lms/login/{token}', [DemoLmsController::class, 'login'])->name('demo-lms.login');
