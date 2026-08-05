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

---

## Not Done

### UI — remaining components (Teacher & Student), one batch at a time

- **Forum** — thread list/create, comment + like UI, per-session vs general course forum.
- **Assessment** — assessment list; builders/attempt UIs for:
  - THEORY: Personal Assignment
  - THEORY: Team Assignment (+ Group management UI)
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
