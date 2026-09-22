<?php

use App\Http\Controllers\DemoLmsController;
use App\Http\Controllers\ForumCommentLikeController;
use App\Http\Controllers\GradebookSessionBreakdownController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProctorSpeedTestController;
use App\Http\Controllers\ProctorSubmissionStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaportExportController;
use App\Http\Controllers\StudentSelectionController;
use App\Http\Controllers\TierChangeController;
use App\Http\Controllers\UserAvailabilityController;
use App\Http\Controllers\UserLoginLinkController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Livewire\ChangePassword;
use App\Livewire\Courses\AssessmentAttendanceShow;
use App\Livewire\Courses\AssessmentFinalExamForm;
use App\Livewire\Courses\AssessmentFinalExamGrade;
use App\Livewire\Courses\AssessmentFinalExamShow;
use App\Livewire\Courses\AssessmentForm;
use App\Livewire\Courses\AssessmentForumDiscussionShow;
use App\Livewire\Courses\AssessmentIndex;
use App\Livewire\Courses\AssessmentPersonalGrade;
use App\Livewire\Courses\AssessmentPersonalShow;
use App\Livewire\Courses\AssessmentQuizForm;
use App\Livewire\Courses\AssessmentQuizShow;
use App\Livewire\Courses\AssessmentTeamGrade;
use App\Livewire\Courses\AssessmentTeamShow;
use App\Livewire\Courses\AttendanceIndex;
use App\Livewire\Courses\CourseComingSoon;
use App\Livewire\Courses\CourseForm;
use App\Livewire\Courses\CoursesIndex;
use App\Livewire\Courses\ForumIndex;
use App\Livewire\Courses\ForumMonitoringIndex;
use App\Livewire\Courses\ForumThreadShow;
use App\Livewire\Courses\GradebookGradeBands;
use App\Livewire\Courses\GradebookIndex;
use App\Livewire\Courses\GradebookShow;
use App\Livewire\Courses\GradebookWeights;
use App\Livewire\Courses\HeadMovementTest;
use App\Livewire\Courses\PeopleIndex;
use App\Livewire\Courses\ProctorExamShow;
use App\Livewire\Courses\ProctorPreflightShow;
use App\Livewire\Courses\ProctorQuizQuestionsForm;
use App\Livewire\Courses\QuizInstructionEdit;
use App\Livewire\Courses\SessionForm;
use App\Livewire\Courses\SessionsIndex;
use App\Livewire\Courses\SyllabusIndex;
use App\Livewire\Dashboard;
use App\Livewire\EditProfile;
use App\Livewire\MediaLibrary\MediaLibraryIndex;
use App\Livewire\Raport\RaportIndex;
use App\Livewire\Raport\RaportShow;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Students\ForcePasswordChange;
use App\Livewire\Students\StudentForm;
use App\Livewire\Students\StudentGenerate;
use App\Livewire\Students\StudentImport;
use App\Livewire\Students\StudentIndex;
use App\Livewire\Students\StudentPhotoUpload;
use App\Livewire\Teachers\TeacherForm;
use App\Livewire\Teachers\TeacherGenerate;
use App\Livewire\Teachers\TeacherImport;
use App\Livewire\Teachers\TeacherIndex;
use App\Livewire\Teachers\TeacherPhotoUpload;
use App\Livewire\Users\UserRoles;
use App\Models\Course;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'redirect-if-no-school', EnsurePasswordIsChanged::class])->group(function () {
    Route::get('/force-password-change', ForcePasswordChange::class)->name('password.force-change');

    Route::get('/edit-profile', EditProfile::class)->name('edit-profile');
    Route::get('/edit-profile/verify-email', [ProfileController::class, 'verifyEmailChange'])->name('profile.verify-email-change');

    Route::get('/change-password', ChangePassword::class)->name('change-password');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/dev/head-movement-test', HeadMovementTest::class)->name('dev.head-movement-test');

    Route::get('/roles', RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', RoleCreate::class)->middleware(['permission:roles.view', 'permission:roles.create'])->name('roles.create');
    Route::get('/roles/{role}/edit', RoleEdit::class)->middleware(['permission:roles.view', 'permission:roles.update'])->name('roles.edit');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

    Route::get('/users/check-availability', [UserAvailabilityController::class, 'check'])->name('users.check-availability');
    Route::get('/users/{id}/roles', UserRoles::class)->middleware('permission:users.assign-roles')->name('users.roles.edit');

    Route::middleware('role:School Admin')->group(function () {
        Route::get('/students', StudentIndex::class)->middleware('permission:students.view')->name('students.index');
        Route::get('/students/create', StudentForm::class)->middleware('permission:students.create')->name('students.create');
        Route::get('/students/import', StudentImport::class)->middleware('permission:students.import')->name('students.import');
        Route::get('/students/generate', StudentGenerate::class)->middleware('permission:students.create')->name('students.generate');
        Route::get('/students/photos', StudentPhotoUpload::class)->middleware('permission:students.edit')->name('students.photos');
        Route::get('/students/selection', [StudentSelectionController::class, 'show'])->middleware('permission:students.view')->name('students.selection.show');
        Route::post('/students/selection', [StudentSelectionController::class, 'update'])->middleware('permission:students.view')->name('students.selection.update');
        Route::post('/students/selection/batch', [StudentSelectionController::class, 'updateMany'])->middleware('permission:students.view')->name('students.selection.update-many');
        Route::delete('/students/selection', [StudentSelectionController::class, 'clear'])->middleware('permission:students.view')->name('students.selection.clear');
        Route::get('/students/{id}/edit', StudentForm::class)->middleware('permission:students.edit')->name('students.edit');

        Route::get('/teachers', TeacherIndex::class)->middleware('permission:teachers.view')->name('teachers.index');
        Route::get('/teachers/create', TeacherForm::class)->middleware('permission:teachers.create')->name('teachers.create');
        Route::get('/teachers/import', TeacherImport::class)->middleware('permission:teachers.import')->name('teachers.import');
        Route::get('/teachers/generate', TeacherGenerate::class)->middleware('permission:teachers.create')->name('teachers.generate');
        Route::get('/teachers/photos', TeacherPhotoUpload::class)->middleware('permission:teachers.edit')->name('teachers.photos');
        Route::get('/teachers/{id}/edit', TeacherForm::class)->middleware('permission:teachers.edit')->name('teachers.edit');
    });

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
        Route::get('/courses/{course}/syllabus/edit', SyllabusIndex::class)->defaults('startInEditMode', true)->middleware('permission:syllabus.edit')->name('syllabus.edit');

        Route::get('/courses/{course}/forum', ForumIndex::class)->middleware('permission:forum.view')->name('forum.index');
        Route::get('/courses/{course}/forum/threads/{thread}', ForumThreadShow::class)->middleware('permission:forum.view')->name('forum.thread.show');
        Route::post('/forum/comments/{comment}/toggle-like', [ForumCommentLikeController::class, 'toggle'])->middleware('permission:forum.create')->name('forum.comment.toggle-like');
        Route::get('/courses/{course}/forum-monitoring', ForumMonitoringIndex::class)->middleware('permission:forum.moderate')->name('forum.monitoring.index');

        Route::get('/courses/{course}/assessments', AssessmentIndex::class)->middleware('permission:assessment.view')->name('assessments.index');
        Route::get('/courses/{course}/assessments/create/{type}', AssessmentForm::class)->whereIn('type', ['personal', 'team'])->middleware('permission:assessment.create')->name('assessments.create');
        Route::get('/assessments/{assessment}/edit', AssessmentForm::class)->middleware('permission:assessment.edit')->name('assessments.edit');
        Route::get('/assessments/{assessment}/personal', AssessmentPersonalShow::class)->middleware('permission:assessment.view')->name('assessments.personal.show');
        Route::get('/assessments/{assessment}/personal/grade/{student}', AssessmentPersonalGrade::class)->middleware('permission:assessment.grade')->name('assessments.personal.grade');
        Route::get('/assessments/{assessment}/team', AssessmentTeamShow::class)->middleware('permission:assessment.view')->name('assessments.team.show');
        Route::get('/assessments/{assessment}/team/grade/{group}', AssessmentTeamGrade::class)->middleware('permission:assessment.grade')->name('assessments.team.grade');
        Route::get('/courses/{course}/assessments/create/quiz', AssessmentQuizForm::class)->middleware('permission:assessment.create')->name('assessments.quiz.create');
        Route::get('/assessments/{assessment}/quiz/edit', AssessmentQuizForm::class)->middleware('permission:assessment.edit')->name('assessments.quiz.edit');
        Route::get('/assessments/{assessment}/quiz', AssessmentQuizShow::class)->middleware('permission:assessment.view')->name('assessments.quiz.show');
        Route::get('/courses/{course}/assessments/create/final-exam', AssessmentFinalExamForm::class)->middleware('permission:assessment.create')->name('assessments.final-exam.create');
        Route::get('/assessments/{assessment}/final-exam/edit', AssessmentFinalExamForm::class)->middleware('permission:assessment.edit')->name('assessments.final-exam.edit');
        Route::get('/assessments/{assessment}/final-exam', AssessmentFinalExamShow::class)->middleware('permission:assessment.view')->name('assessments.final-exam.show');
        Route::get('/assessments/{assessment}/final-exam/grade/{student}', AssessmentFinalExamGrade::class)->middleware('permission:assessment.grade')->name('assessments.final-exam.grade');
        Route::get('/assessments/{assessment}/final-exam/questions/edit', ProctorQuizQuestionsForm::class)->middleware('permission:assessment.edit')->name('assessments.final-exam.questions.edit');
        Route::get('/proctor/speed-test-download', [ProctorSpeedTestController::class, 'download'])->name('proctor.speed-test-download');
        Route::post('/proctor/speed-test-upload', [ProctorSpeedTestController::class, 'upload'])->name('proctor.speed-test-upload');
        Route::get('/assessments/{assessment}/final-exam/proctor/preflight', ProctorPreflightShow::class)->middleware('permission:assessment.view')->name('assessments.final-exam.proctor.preflight');
        Route::get('/assessments/{assessment}/final-exam/proctor', ProctorExamShow::class)->middleware('permission:assessment.view')->name('assessments.final-exam.proctor.show');
        Route::get('/assessments/{assessment}/final-exam/proctor/submission-status-stream', [ProctorSubmissionStatusController::class, 'stream'])->middleware('permission:assessment.view')->name('assessments.final-exam.proctor.submission-status-stream');
        Route::get('/assessments/{assessment}/attendance', AssessmentAttendanceShow::class)->middleware('permission:assessment.view')->name('assessments.attendance.show');
        Route::get('/assessments/{assessment}/forum-discussion', AssessmentForumDiscussionShow::class)->middleware('permission:assessment.view')->name('assessments.forum-discussion.show');
        Route::get('/quiz-instructions', QuizInstructionEdit::class)->middleware('permission:assessment.edit')->name('quiz-instructions.edit');

        Route::get('/courses/{course}/people', PeopleIndex::class)->middleware('permission:people.view')->name('people.index');

        Route::get('/courses/{course}/attendance', AttendanceIndex::class)->middleware('permission:attendance.view')->name('attendance.index');
        Route::get('/courses/{course}/gradebook', GradebookIndex::class)->middleware('permission:gradebook.view')->name('gradebook.index');
        Route::get('/courses/{course}/gradebook/students/{student}', GradebookShow::class)->middleware('permission:gradebook.view')->name('gradebook.show');
        Route::get('/courses/{course}/gradebook/weights', GradebookWeights::class)->middleware('permission:gradebook.manage')->name('gradebook.weights');
        Route::get('/courses/{course}/gradebook/grade-bands', GradebookGradeBands::class)->middleware('permission:gradebook.manage')->name('gradebook.grade-bands');
        Route::get('/courses/{course}/gradebook/types/{type}/sessions', GradebookSessionBreakdownController::class)->middleware('permission:gradebook.view')->name('gradebook.sessions');

        Route::get('/courses/{course}/tabs/{tab}', CourseComingSoon::class)->middleware('permission:courses.view')->name('course-tabs.coming-soon');

        Route::get('/media-library', MediaLibraryIndex::class)->middleware('permission:media.view')->name('media-library.index');

        Route::get('/raport', RaportIndex::class)->middleware('permission:raport.view')->name('raport.index');
        Route::get('/raport/export', [RaportExportController::class, 'exportSelf'])->middleware('permission:raport.view')->name('raport.export.self');
        Route::get('/raport/export/{student}', [RaportExportController::class, 'exportStudent'])->middleware('permission:raport.view')->name('raport.export.student');
        Route::get('/raport/students/{student}', RaportShow::class)->middleware('permission:raport.view')->name('raport.show');
    });
});

Route::get('/demo-lms/login/{token}', [DemoLmsController::class, 'login'])->name('demo-lms.login');

Route::get('/login-link/{token}', [UserLoginLinkController::class, 'login'])->name('user-login-link.login');
