# Course Restructure — Progress

## Definition

Tracks what has been implemented so far for the course restructure (see [course-restructure-overview.md](course-restructure-overview.md)) versus what remains, across schema and UI. Updated as batches complete.

---

## Done

### Schema (migrations, models, repositories, services, factories)

Full backend schema for all 7 components + supporting components, built across two batches:

- **Course** — soft delete added (`deleted_at`).
- **Session / Period** — `Session` (table `course_sessions`, renamed to avoid clashing with Laravel's own `sessions` table), `SessionSubtopic`, `VideoConference`, `VideoConferenceParticipation`, `Period`.
- **Syllabus** — `Syllabus`, `SyllabusClassPolicy`, `SyllabusLearningOutcome`, `SyllabusEvaluation`, `SyllabusEvaluationActivity`, `SyllabusRubricKeyIndicator`, `SyllabusRubricProficiencyLevel`, `SyllabusRubricCell`.
- **People / Group** — `CoursePerson` (table `course_people`), `Group`, `GroupMember`.
- **Forum** — `Forum`, `ForumThread`, `ForumComment`, `ForumCommentLike` (with `comments_count`/`likes_count` counters).
- **Assessment** — generic `Assessment`, `AssessmentAttempt`, `AssessmentScore`; assignment schema (`AssessmentQuestion`, `AssessmentAnswer`) shared by Personal/Team Assignment; `Quiz`, `QuizInstruction`, `QuizQuestion`, `QuizQuestionOption`, `AssessmentQuizAnswer`; `FinalExam`.
- **Proctor** — `ProctorSession`, `ProctorEvent`, `ProctorSnapshot` (schema only, no client-side detection logic).
- **Gradebook** — `GradebookGradeScale`, `GradebookEntry`, `GradebookSessionEntry`.
- **Attendance** — `Attendance`, `AttendanceRequirement`, `CourseAttendanceSetting`.
- Every model has a `RepositoryInterface` + `Repository` + `Service` (per `docs/REPOSITORY_PATTERN.md`), bound in `AppServiceProvider`, plus a factory and Pest feature tests through the Service layer.
- **Fix**: `session_materials` / `syllabus_materials` / `assessment_question_files` were corrected to point at `media_library_items` (the actual school-wide Media Library) instead of the older lesson-scoped `lesson_materials` table.

### UI — Batch 1: Course tab shell + Session (Teacher & Student)

- Shared 7-tab navigation (Session / Syllabus / Forum / Assessment / Gradebook / People / Attendance) — only **Session** is functional; the other 6 render a "Coming soon" placeholder (`CourseComingSoon`).
- **Session**: `SessionsIndex` (list + accordion detail: subtopics, media, video conferences; Teacher gets create/edit/delete, Student read-only + join links) and `SessionForm` (create/edit with subtopic repeater, media-library picker, video-conference repeater).
- `sessions.*` permissions (Teacher: full CRUD, Student: view-only, School Admin: full group), routes registered.
- 12 Livewire feature tests; full suite green (879/880, 1 pre-existing skip); Pint clean; Larastan 0 errors.

School Admin UI was explicitly deferred (per user decision) — the tab shell and permissions still account for it, but no dedicated screens exist yet.

### UI — Batch 2: Syllabus (Teacher & Student)

- **Syllabus**: 11-section editor (`SyllabusForm`) and viewer (`SyllabusIndex`, split teacher/student rendering via a shared `syllabus-sections-readonly` partial) covering all sections — Course Description, Class Policies (scoped F2F/Video/Online/General), Submission & Collection, Tutorial Activity Plan, Learning Outcomes, Evaluation (class-type groups with weight-sums-to-100 validation and activity↔Learning Outcome mapping), Assessment Rubric (key indicators × proficiency levels matrix), Teaching & Learning Strategies, Textbooks, Competency Map, Video Overview. Per-section optional Media Library attachments via `syllabus_materials`.
- `syllabus.view` / `syllabus.edit` permissions (Teacher & School Admin: both; Student: view-only), routes registered (`syllabus.index`, `syllabus.edit`).
- Extracted `App\Support\CourseTabs::build()` to keep tab-href logic out of Blade (repo convention), reused by Session/Syllabus/CourseComingSoon.
- 26 Livewire feature tests for Syllabus; full `Courses` test folder (Session + Syllabus + CourseComingSoon) 70/70 green; Pint clean; Larastan 0 errors.

### UI — Batch 3: Forum (Teacher & Student)

- **Forum**: `ForumIndex` (session tab bar, thread list with create/delete, pagination) and `ForumThreadShow` (thread detail with edit, comments with add/edit/delete, single-level replies, like/unlike toggle). One shared Blade per screen for both roles — differences are button-visibility only (`canCreate`/`canModerate`), not full layout splits.
- **No course-wide "General" forum** — forums are strictly session-scoped (`forums.session_id` is NOT NULL at the DB level; the general-forum concept was built then removed per user feedback). Session-scoped forums are auto-created by `SessionService::create()` whenever a `Session` is created.
- Deletion model: thread/comment owner can delete their own; Teacher/School Admin can delete anyone's via `forum.moderate`. Deleting a comment with replies decrements the thread's `comments_count` by the full removed-post count.
- Single-level comment replies (`forum_comments.parent_id`, self-referencing FK, cascade delete) — replying to a reply is blocked.
- Thread and comment edit capability (`updateThread`/`updateComment`), rich text via Trix (`resources/views/components/rich-text-editor.blade.php`), sanitized server-side on write via `App\Support\HtmlSanitizer` (`mews/purifier`, `forum` config in `config/purifier.php`).
- Per-user read tracking (`forum_thread_reads`) drives unread-post badges on session tabs and unbolds a thread's title once read.
- All forum/session dates render in the viewer's timezone via `HasViewerTimezoneDates` (`_display` accessors), no hardcoded "GMT+7" label.
- Like button is an Alpine-optimistic toggle (instant fill/count change client-side, `$wire.call` in the background).
- `forum.view` / `forum.create` / `forum.moderate` permissions (Teacher & School Admin: all three; Student: view + create), routes registered (`forum.index`, `forum.thread.show`).
- `CourseTabs::build()` and `CourseComingSoon` updated to route Forum to its real screens; the Session tab's per-session forum chip links to the session-scoped forum.
- Session tab bar styling unified with the Session tab's own pill pattern (`rounded-t-lg border-b-2`, Alpine `pendingSessionId` optimistic active state).
- `database/seeders/ForumSeeder.php` seeds per-session threads, comments, replies, and likes (no general forum).
- 37 Livewire feature tests + 3 service-level tests; full suite 691/691 green; Pint clean; Larastan 0 errors.

### UI — Batch 4: Assessment shell + Personal/Team Assignment + Groups (Teacher & Student)

- **Assessment list** (`AssessmentIndex`): grouped by `AssessmentType`, all 6 types shown (only Personal/Team have working create/edit/detail links this batch, others render as inert "coming soon" chips). Teacher gets create dropdown, edit/delete (delete blocked if any `AssessmentAttempt` exists — no soft-delete on `Assessment`), "Manage Groups" button. Student rows show computed status (not started / submitted / graded) via `AssessmentAttemptService`.
- **Personal/Team Assignment builder** (`AssessmentForm`, shared via `assigned_to`): title/weight (defaulted from `AssessmentType::defaultWeight()`)/dates/session/status, question repeater (description via rich-text-editor + points + per-question Media Library picker syncing `assessment_question_files`). Type is immutable after create.
- **Personal Assignment show** (`AssessmentPersonalShow`) / **Team Assignment show** (`AssessmentTeamShow`): single shared component per type, role-gated view data (Forum-style, not template-split). Student submits text answer (file upload deferred — see below), can resubmit until graded or until `end_date` passes (`attempt_number` increments each resubmission). Teacher sees per-student/per-group rows with inline grading (score + feedback via `AssessmentScore`). Team variant resolves the student's own `Group` and shows/accepts one shared submission per group (any member can submit; score attaches to the `AssessmentAttempt` and is visible live to all members, including ones who join after grading).
- **Group management** (`GroupsManage`): standalone screen (not nested in the builder) since `Group`/`GroupMember` are course-scoped and reused across all Team Assignments and the future People batch. Create/rename/delete (delete blocked unless empty) groups; add/move/remove students; **one group per course per student enforced** — assigning to a new group auto-removes the prior membership in that course.
- `assessment.{view,create,edit,delete,submit,grade}` + `groups.manage` permissions (Teacher/School Admin: all 7; Student: view + submit only), seeded via migration following the Forum-permissions pattern; routes registered under `assessments.*` / `groups.manage`.
- `CourseTabs::build()` and `CourseComingSoon` updated to route Assessment to its real screen (`CourseComingSoonTest` updated to test against `gradebook` instead, plus an explicit "assessment tab is no longer coming soon" 404 case, mirroring the Forum batch's pattern).
- **Known gap**: student file-upload on submission was scoped out of this batch — `AssessmentAnswer.answer_file_id` exists in schema but submission is currently text-only, since wiring a student-facing upload into the Media Library's presigned-URL + 3-layer-validation pipeline (designed for Teacher/Admin use) needs its own design pass.
- Added missing `@return Collection<int, X>` generics to `AssessmentAttemptService`, `AssessmentService`, `GroupService`, `GroupMemberService` (pre-existing gap from the schema batch, following the convention already used by `SessionService::forCourse`) to keep Larastan clean against the new call sites.
- 41 Livewire feature tests across 5 new test files; full suite 743/743 green; Pint clean; Larastan 0 errors.

### UI — Batch 5: Quiz + global Instruction Page (Teacher & Student)

- **Quiz builder** (`AssessmentQuizForm`): top-level fields (title/weight/session/status) plus question/option repeater. Type is `AssessmentType::TheoryQuiz`, immutable after create. Assessment `start_date`/`end_date` and Quiz `start_date`/`due_date` are both derived from the selected Session's `date_start`/`date_end` — no manual date pickers. Quiz-specific settings: `totalAttempts` (blank = unlimited), `scoringMethod` (highest/latest/average), `timeLimitPerAttempt` (blank = unlimited). Question repeater supports `multiple_choice`/`true_false`/`short_answer`/`essay`; switching type auto-manages the options sub-repeater (fixed True/False pair, cleared for text types, ≥2 empty options for MC). Multiple-choice/true_false enforce exactly one correct option (single-answer radio semantics, matching `assessment_quiz_answers.selected_option_id` being a single FK).
- **Quiz attempt/grading** (`AssessmentQuizShow`, shared Teacher/Student like Personal/Team): Student sees an inline Instruction Page panel (from `QuizInstructionService::current()`, no separate route/screen, no "seen" tracking), starts attempts (capped by `total_attempts`, enforced server-side), answers all question types, submits (time-limit clamp applied server-side on submit). multiple_choice/true_false auto-score immediately via `QuizAttemptScoringService`; short_answer/essay stay ungraded until Teacher scores them. Students see score-only after submission (no correct-answer reveal). `AssessmentScore` shows **partial/live** totals — auto-graded portion visible immediately, updates again once pending essay/short_answer items are graded (no gating). Teacher sees a roster with a "needs grading" badge and can grade pending answers inline.
- **Global Instruction Page** (`QuizInstructionEdit`): single global `quiz_instructions` row (no `quiz_id`/course scoping), edited via `assessment.edit` permission, linked from the Quiz builder.
- **New service**: `App\Services\QuizAttemptScoringService` — auto-scoring for objective question types, `highest`/`latest`/`average` aggregation across a user's attempts, writes/updates the single `AssessmentScore` row keyed by `assessment_attempt_id`.
- `AssessmentIndex` updated to route Quiz rows to `assessments.quiz.show`/`assessments.quiz.create` and compute quiz-specific student status (not started / pending grading / graded); "coming soon" placeholder removed for Quiz.
- Routes added: `assessments.quiz.create`, `assessments.quiz.edit`, `assessments.quiz.show`, `quiz-instructions.edit`. No new permissions — reuses existing `assessment.*` set.
- **Known simplifications**: answers are persisted in one batch on `submitAttempt()` (not autosaved per keystroke, mirroring Personal Assignment's submit flow) — attempt creation/timing is still recorded immediately on `startAttempt()`, so attempt-cap and time-limit enforcement are unaffected. A stale in-progress attempt past its time limit is only clamped/force-closed on the next explicit submit, not auto-submitted on a passive page reload.
- 32 new Livewire/service feature tests (`AssessmentQuizFormTest`, `AssessmentQuizShowTest`, `QuizInstructionEditTest`, `QuizAttemptScoringServiceTest`) plus extended `AssessmentIndexTest`; full `Courses` + `CourseRestructure` test folders 227/227 green; Pint clean; Larastan has 3 pre-existing errors unrelated to this batch (confirmed via `git stash` comparison), none in newly added code.

### UI — Batch 6: Attendance (Teacher & Student)

- **Attendance Requirement config** (`AttendanceRequirementSettings`, Teacher-only): CRUD screen for a course's `attendance_requirements` (type/label/order, with up/down reordering) and its single `course_attendance_settings.minimal_attendance` value. Route `attendance.settings`.
- **Attendance Management / Attendance (student view)** — one shared component (`AttendanceIndex`, Forum-style role branching, not template-split): Teacher gets a session tab bar + per-session student roster showing each student's requirement checklist, the computed attend status, and a manual override form (status/notes) that writes an `attendances` row with `recorded_by` set. Student gets a read-only Attendance Summary card (`total_session` / `total_attendance` / `minimal_attendance`) plus a per-session table (delivery mode, dates, attend boolean, requirement checklist). Route `attendance.index`; `CourseTabs::build()` updated to route the Attendance tab here instead of `CourseComingSoon`.
- **New service `App\Services\AttendanceDerivationService`**: derives per-requirement fulfillment and the overall per-session "attended" boolean (`isSessionAttended` = all configured requirements fulfilled, or a manual `present` record when no requirements are configured), plus the student summary. `manual_checkin` reads the `attendances` row's status; `forum_completed` counts the student's `forum_comments` in that session's forum against `sessions.required_forum_posts` (already existed in schema, defaults to 2 — no new threshold config was needed); `class_duration_completed` sums `VideoConferenceParticipation` duration per video conference against `required_duration_minutes` (a conference with no `required_duration_minutes` set is treated as non-blocking; a session with no video conferences is trivially fulfilled).
- **Assessment wiring for `AssessmentType::Attendance`**: added as a real row in `AssessmentIndex` (Quiz-style, no longer a "coming soon" chip), with a "Create → Attendance" option reusing `AssessmentForm` (extended to accept `type=attendance`: same title/weight/dates/session/status fields as Personal/Team Assignment, question repeater hidden/skipped entirely — no builder needed for this type). New **`AssessmentAttendanceShow`** screen (route `assessments.attendance.show`) is purely derived — no submit action, matching the Forum Discussion "derived, no manual submit" pattern: Student sees sessions-attended/percentage/score; Teacher sees a per-student summary table.
- **New service `App\Services\AttendanceScoringService`**: `computeForUser()` = (sessions attended / sessions in scope) × assessment weight. **Scoping decision** (date-range ambiguity in the docs): sessions in scope are those whose `date_start` falls within the assessment's `[start_date, end_date]` window; if none fall in that window (e.g. a course-wide Attendance assessment), all course sessions are used instead. `recomputeForUser()` writes/updates a single `AssessmentAttempt` + `AssessmentScore` row per student (mirrors `QuizAttemptScoringService`'s pattern), recomputed live on every view of `AssessmentAttendanceShow`/`AssessmentIndex` rather than cached.
- `attendance.view` (Teacher/School Admin/Student) and `attendance.manage` (Teacher/School Admin only) permissions, seeded via migration following the Forum/Assessment permissions pattern.
- **Repository pattern fix**: added `ForumCommentRepository::countForUserInSession()` / `ForumCommentService::countForUserInSession()` so `AttendanceDerivationService`'s forum-completion check goes through the repository layer instead of querying `ForumComment` directly (caught by the repo-pattern lint hook); added missing `@return Collection<int, X>` generics to `AttendanceRequirementService`/`AttendanceRequirementRepository` (same gap pattern as Batch 4) to keep Larastan clean.
- 26 new Livewire/service feature tests (`AttendanceDerivationServiceTest`, `AttendanceScoringServiceTest`, `AttendanceRequirementSettingsTest`, `AttendanceIndexTest`, `AssessmentAttendanceShowTest`); `Courses` + `CourseRestructure` test folders 250/250 green; Pint clean; Larastan has the same 3 pre-existing errors as Batch 5 (confirmed via `git stash` comparison), none in newly added code.
- **Known simplifications**: `class_duration_completed` and `forum_completed` are computed live on every render rather than cached/denormalized; for large rosters this re-queries per student per row (acceptable at current course sizes, but a candidate for future optimization). Attendance override in the Teacher UI is one row at a time (no bulk "mark all present").

---

## Not Done

### UI — remaining components (Teacher & Student), one batch at a time

- **Assessment** — remaining builders/attempt UIs for:
  - Student file-upload on Personal/Team Assignment submission (deferred from Batch 4, see note above)
  - THEORY: FINAL EXAM (Open Book / Closed Book / Take Home) — Open/Closed Book requires **Proctor UI + actual client-side detection mechanism** (webcam, tab-switch, etc. — flagged in the schema batch as needing dedicated technical research)
  - Forum Discussion (auto-graded from Forum participation)
  - Grading Queue (Teacher-side manual grading for assignments/essay/take-home)
- **Gradebook** — Final Score summary + per-type/per-session breakdown, Grading Scale config.
- **People** — Teachers/Students/Groups roster tabs, group management (create/assign/move students).

### School Admin UI (all components)

Course List (with restore), People (assign Teacher/Assistant), Course Settings (grading scale, attendance settings), Cross-Course Reports — see [course-restructure-role-flows.md](course-restructure-role-flows.md).

### Other

- Client-side Proctor detection mechanism (webcam access, tab-switch/devtools/network-activity detection) — explicitly out of scope until dedicated research, per earlier scoping decision.

---

## Notes

This document reflects implementation state at time of writing and should be updated as each new batch lands. See [course-restructure-role-flows.md](course-restructure-role-flows.md) for the full target screen/flow list per role, and [course-restructure-schema.md](course-restructure-schema.md) for the combined schema reference.
