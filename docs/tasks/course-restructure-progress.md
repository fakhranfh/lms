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

> Revised after product-owner review: the configurable `AttendanceRequirement` system was replaced with a fixed rule, and the Assessment page's Attendance row is now auto-provisioned instead of manually created. See below for the current design.
>
> **Revision 2**: `online` sessions are back in scope for the general Attendance page (all three delivery modes now count toward `total_session`/`total_attendance`), each delivery mode gets its own fixed, non-configurable "Attendance Requirement" (replacing the "Source" column), and the Assessment page's derived Attendance score is now scoped to `virtual_class` sessions only — see the revision-2 bullets below.

- **Fixed attendance rule per delivery mode (no configurable requirements)**: `virtual_class`: a student is "attended" if EITHER a `VideoConferenceParticipation` row exists for them on any of that session's video conferences (mere presence of a join record, no minimum-duration check), OR a Teacher manually marked them present. `offline`: attended ONLY via a Teacher's manual "present" mark — there is no auto-derivation for offline sessions. `online` (revision 2): attended if the student's forum post count (threads + comments, summed) in that session's forum reaches `session->required_forum_posts` (column default `2`). The configurable `AttendanceRequirement` system (model, migration/table, repository, service, `AttendanceRequirementType` enum, the `AttendanceRequirementSettings` screen/route/tests) was removed entirely — no deprecation shim, and revision 2 does not resurrect it; the per-mode requirement is pure code logic. `course_attendance_settings.minimal_attendance` remains (unrelated setting used by the summary card only).
- **Attendance Management / Attendance (student view)** — one shared component (`AttendanceIndex`, Forum-style role branching, not template-split): Teacher gets a session tab bar (all sessions, all delivery modes) + per-session student roster showing delivery mode, the derived/attended boolean, an "Attendance Requirement" column with a human-readable description of that session's requirement (e.g. "Join video conference or teacher mark", "2 forum posts", "Teacher mark"), and a manual override form (status/notes) that writes an `attendances` row with `recorded_by` set — this is still how offline attendance and virtual_class overrides get recorded. Student gets a read-only Attendance Summary card (`total_session` / `total_attendance` / `minimal_attendance`, all delivery modes counted) plus a per-session table (delivery mode, dates, attend boolean, Attendance Requirement). Route `attendance.index` only; the separate `attendance.settings` route/screen was removed. `attendance.manage` (Teacher/School Admin) still gates the manual-override controls on this same screen.
- **`App\Services\AttendanceDerivationService`** (revision 2): `isSessionAttended()` now handles all three delivery modes, with `online` fulfilled via forum post count (`ForumThreadService::countForUserInSession()` + `ForumCommentService::countForUserInSession()`, summed against `required_forum_posts` falling back to `2`). `isAttendanceApplicable()` was removed (all sessions are now in scope) and `applicableSessionsForCourse()` was renamed to `sessionsForCourse()` (no longer filters by delivery mode). `attendanceSourceForSession()` was renamed to `attendanceRequirementDescriptionForSession()` and now describes the requirement rather than how it was fulfilled. `summaryForStudent()` counts sessions across all three delivery modes again.
- **`App\Repositories\ForumThread`** gained `countForUserInSession(string $userId, string $sessionId): int` on the interface, repository, and `ForumThreadService`, mirroring the existing `ForumCommentRepositoryInterface::countForUserInSession()` pattern — counts threads created by a user within a session's forum.
- **`App\Services\AttendanceScoringService::sessionsInScope()`** (revision 2, narrowed from revision 1): now only considers `virtual_class` sessions — `offline` and `online` sessions are excluded from the derived Attendance assessment score, even though they appear on the general Attendance page — before applying the assessment's date-range scoping (unchanged: sessions whose `date_start` falls in `[start_date, end_date]`, falling back to all `virtual_class` course sessions when none match or when dates are null). `AssessmentAttendanceShow`'s blade copy was updated from "Sessions Attended" to "Virtual Class Sessions Attended" to avoid confusion with the general Attendance page's totals.
- **Auto-provisioned course-wide Attendance assessment**: `CourseService::create()` now auto-creates a single `Assessment` (type `attendance`, title "Attendance", weight `AssessmentType::Attendance->defaultWeight()`, `assigned_to` individual, `start_date`/`end_date` null, status published) for every new course — mirrors how `SessionService::create()` auto-creates a per-session Forum. `CourseService::ensureAttendanceAssessment()` is idempotent (checks for an existing attendance-type Assessment first) and is also the entry point a backfill migration (`2026_08_13_000003_backfill_course_attendance_assessments`) uses to seed the row for pre-existing courses. This means the Assessment page's Attendance row always exists and always mirrors the standalone Attendance page's computed data — no manual "Create → Attendance" flow anymore (removed from `AssessmentForm`'s allowed create types and from the `assessments.create` route's `type` whitelist; the Delete button is hidden for `AssessmentType::Attendance` rows in `AssessmentIndex`, both in the blade and as a server-side guard in `deleteAssessment()`, since deleting it would break the mirror — Edit is still available, since weight/date-range are legitimate per-course tuning knobs and no other derived type establishes a "no edit" precedent yet).
- New migration `2026_08_13_000002_drop_attendance_requirements_table` drops `attendance_requirements` (kept as a separate migration rather than editing the original Batch 6 migration).
- **`AssessmentAttendanceShow`** (route `assessments.attendance.show`) and its `AttendanceScoringService` wiring are unchanged in shape — still purely derived, no submit action; see the `sessionsInScope()` note above for the revision-2 scoping change.
- `attendance.view` (Teacher/School Admin/Student) and `attendance.manage` (Teacher/School Admin only) permissions unchanged.
- Tests: `AttendanceDerivationServiceTest` and `AttendanceScoringServiceTest` rewritten around the fixed rule (delivery-mode filtering, join-based virtual_class, manual-only offline); `AttendanceIndexTest` and `AssessmentAttendanceShowTest` updated for explicit `delivery_mode` fixtures and an online-exclusion assertion; `AttendanceRequirementSettingsTest` deleted; new `CourseServiceAttendanceProvisioningTest` covers auto-creation on `CourseService::create()`, idempotency of `ensureAttendanceAssessment()`, and the backfill migration; `AssessmentIndexTest` gained a delete-blocked-for-Attendance case. Revision 2 updated all of the above for online back in scope, the "Attendance Requirement" column/method rename, and `AttendanceScoringService` narrowed to `virtual_class`-only, plus new coverage for online forum-post fulfillment at/below/above threshold and thread+comment summing. Pint clean; Larastan has the same 3 pre-existing errors as prior batches, none in changed code.
- **Known simplifications carried forward**: Attendance override in the Teacher UI is one row at a time (no bulk "mark all present"). `AttendanceScoringService::recomputeForUser()` is still recomputed live on every view rather than cached.

### UI — Batch 7: Forum Discussion (Teacher & Student)

- **Fixed rule, same shape as Attendance**: a student "meets" a session if their forum post count (threads + comments, summed) in that session's forum reaches `session->required_forum_posts` (same column/default `2` already used by the general Attendance page's `online`-delivery derivation — no new column added). Scoped to `delivery_mode === online` sessions only, since that column is only meaningful there.
- **`App\Services\ForumDiscussionScoringService`**: mirrors `AttendanceScoringService` — `computeForUser()`/`recomputeForUser()` (single `AssessmentAttempt` + `AssessmentScore` row per user), `sessionsInScope()` (online sessions, narrowed to the assessment's `[start_date, end_date]` when set, falling back to all online course sessions otherwise). `hasMetForumPostRequirement()`/`requiredForumPosts()` duplicate `AttendanceDerivationService`'s private forum-post-counting logic (kept separate rather than extracted/shared, since the two services serve different assessment types).
- **Auto-provisioned course-wide Forum Discussion assessment**: `CourseService::ensureForumDiscussionAssessment()` (idempotent, same shape as `ensureAttendanceAssessment()`), called alongside it in `CourseService::create()`. Backfill migration `2026_08_13_000004_backfill_course_forum_discussion_assessments` seeds it for pre-existing courses. No manual create flow (excluded from `AssessmentForm`'s create-type whitelist and the `assessments.create` route); Delete is blocked in `AssessmentIndex` (both blade and `deleteAssessment()` guard); Edit remains available for weight/date-range tuning (`AssessmentForm` extended to allow `ForumDiscussion` in its edit-type whitelist and to skip the question repeater, same as Attendance).
- **`AssessmentForumDiscussionShow`** (route `assessments.forum-discussion.show`) — Forum-style shared Teacher/Student component, purely derived, no submit action: student sees per-session met/not-met + points; teacher sees per-session "X of Y students met the N-post requirement".
- **`AssessmentIndex`** — Forum Discussion rows route to the new show screen and compute derived score via `ForumDiscussionScoringService::computeForUser()`; student view gets an expandable per-session breakdown identical in shape to the Attendance one.
- 12 new Livewire/service feature tests (`ForumDiscussionScoringServiceTest`, `AssessmentForumDiscussionShowTest`) plus extended `CourseServiceAttendanceProvisioningTest`; `Courses` + `CourseRestructure` test folders 267/267 green; Pint clean; Larastan back to the 1 pre-existing unrelated error (`AssessmentPersonalShow.php`).

---

### UI — Batch 8: Final Exam (Teacher & Student)

- **Scoping decision**: submission/grading is implemented uniformly for all three `exam_type` values (`open_book` / `closed_book` / `take_home`) using the same single-attempt-with-resubmit-until-graded flow as Personal Assignment. **Proctor is still deferred** — `ProctorSession`/`ProctorEvent`/`ProctorSnapshot` exist in the schema but nothing in this batch touches them; no proctor session creation, webcam monitoring, or client-side detection. `allow_local_files` / `allow_internet` are stored and displayed as informational flags only, not enforced.
- **`AssessmentFinalExamForm`** (routes `assessments.final-exam.create` / `assessments.final-exam.edit`) — dedicated builder mirroring `AssessmentForm` (title/weight/dates/status + question repeater with rich text and media-library attachments), with the Session select swapped for a **Period** select (`PeriodService::get(['course_id' => ...])`, ordered by `order`), plus an **Exam Type** select and **Allow Local Files** / **Allow Internet** toggles. Type is immutable after create; `save()` runs in a `DB::transaction` and create-or-updates the `FinalExam` row via `FinalExamService` alongside the `Assessment`.
- **`AssessmentFinalExamShow`** (route `assessments.final-exam.show`) — shared Teacher\Student screen copied from `AssessmentPersonalShow`: student submit/resubmit-until-graded with attempt history, teacher per-student per-question grading + feedback. Meta grid adds Exam Type / Local Files (Allowed·Not allowed) / Internet (Allowed·Not allowed) from the related `FinalExam`.
- **`AssessmentIndex`** — Final Exam is now a real "Create Assessment" menu entry (replaces the "coming soon" placeholder), rows route to the new show screen, edit links resolve via a new `editRoute()` helper, and student status computation reuses the individual attempt-based path (`forAssessmentAndUser`).
- Incidental cleanups while making Larastan exhaustive-match-aware: dropped the now-unreachable `default` arm/`unavailable` branch in `rowStatus()` and added the missing `Collection<int, AssessmentQuestionScore>` generic on `AssessmentQuestionScoreService::findByAttempt()` (this also clears the previously known `AssessmentPersonalShow.php` error).
- 14 new Livewire feature tests (`AssessmentFinalExamFormTest`, `AssessmentFinalExamShowTest`); `Courses` + `CourseRestructure` folders 279/281 green — the 2 failures are pre-existing `AttendanceIndexTest` session-label assertions, unrelated and reproducible on a clean tree. Pint clean; **Larastan 0 errors**.

---

## Not Done

### UI — remaining components (Teacher & Student), one batch at a time

- **Assessment** — remaining builders/attempt UIs for:
  - Student file-upload on Personal/Team Assignment submission (deferred from Batch 4, see note above)
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
