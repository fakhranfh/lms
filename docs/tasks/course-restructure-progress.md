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

---

## Not Done

### UI — remaining components (Teacher & Student), one batch at a time

- **Assessment** — remaining builders/attempt UIs for:
  - Student file-upload on Personal/Team Assignment submission (deferred from Batch 4, see note above)
  - THEORY: Quiz (+ global Instruction Page)
  - THEORY: FINAL EXAM (Open Book / Closed Book / Take Home) — Open/Closed Book requires **Proctor UI + actual client-side detection mechanism** (webcam, tab-switch, etc. — flagged in the schema batch as needing dedicated technical research)
  - Forum Discussion (auto-graded from Forum participation)
  - Attendance (auto-graded from attendance requirements)
  - Grading Queue (Teacher-side manual grading for assignments/essay/take-home)
- **Gradebook** — Final Score summary + per-type/per-session breakdown, Grading Scale config.
- **People** — Teachers/Students/Groups roster tabs, group management (create/assign/move students).
- **Attendance** — Attendance Summary + per-session table with requirement checklist, manual override, requirement config (`AttendanceRequirement`, `CourseAttendanceSetting`).

### School Admin UI (all components)

Course List (with restore), People (assign Teacher/Assistant), Course Settings (grading scale, attendance settings), Cross-Course Reports — see [course-restructure-role-flows.md](course-restructure-role-flows.md).

### Other

- Client-side Proctor detection mechanism (webcam access, tab-switch/devtools/network-activity detection) — explicitly out of scope until dedicated research, per earlier scoping decision.

---

## Notes

This document reflects implementation state at time of writing and should be updated as each new batch lands. See [course-restructure-role-flows.md](course-restructure-role-flows.md) for the full target screen/flow list per role, and [course-restructure-schema.md](course-restructure-schema.md) for the combined schema reference.
