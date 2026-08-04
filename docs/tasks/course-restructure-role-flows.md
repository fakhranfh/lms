# Course Restructure — Role Flows & Screens

## Definition

Describes the screens (views) and flows that need to be built for each role — **School Admin**, **Teacher**, **Student** — across the restructured Course components (see [course-restructure-overview.md](course-restructure-overview.md)). This is a UI/flow planning doc, not a schema doc (see [course-restructure-schema.md](course-restructure-schema.md) for the combined schema).

Role definitions here refer to `role_in_course` (course-level, see [course-restructure-people.md](course-restructure-people.md)) — a school-level Admin is assumed to also be able to access any course within their school, regardless of `course_people` membership.

---

## School Admin

School Admin oversees courses at the school level; day-to-day teaching (sessions, grading, forum moderation) is the Teacher's responsibility.

### Screens
- **Course List** — all courses in the school, with status (draft/published), soft-delete/restore action.
- **Course Create/Edit** — title, description, slug, publish toggle.
- **Course People (admin view)** — assign/remove Teachers and Assistants to a course (Students are typically self-enrolled or bulk-imported); view Student and Group rosters read-only.
- **Course Settings** — grading scale defaults (`gradebook_grade_scales`), attendance settings (`course_attendance_settings`, `attendance_requirements`).
- **Cross-Course Reports** — aggregated gradebook/attendance stats across courses in the school (read-only).

### Flow: Create and set up a new course
1. Admin opens Course List → clicks "New Course".
2. Fills title, description, slug → saves as draft.
3. Assigns one or more Teachers to the course via People.
4. Configures course-level Attendance Settings (minimal attendance, requirement types) and Grading Scale (or leaves school default).
5. Publishes the course once the Teacher confirms Syllabus/Sessions/Assessments are ready.

### Flow: Restore a deleted course
1. Admin opens Course List → filters by "Deleted".
2. Selects a course → clicks "Restore" (undoes soft delete, `deleted_at = null`).

---

## Teacher

Teacher is responsible for authoring and running the course day-to-day: content, forum moderation, assessments, grading, attendance, and group management.

### Screens
- **Course Dashboard** — tabs for Session, Syllabus, Forum, Assessment, Gradebook, People, Attendance (see [course-restructure-overview.md](course-restructure-overview.md)).
- **Session List/Builder** — create/reorder Sessions; per session: title, description (learning outcome + subtopics), start/end date, delivery mode, learning material picker (media library), Video Conference scheduling (see [course-restructure-session.md](course-restructure-session.md)).
- **Period Builder** — group Sessions into Periods, used when creating a Final Exam (see [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)).
- **Syllabus Editor** — 11-section form (see [course-restructure-syllabus.md](course-restructure-syllabus.md)), including the structured Evaluation table (activity × weight × LO mapping) and Assessment Rubric table (LO × Key Indicator × Proficiency Level).
- **Forum Moderation** — view all threads/comments (general + per-session), delete/pin, respond as instructor.
- **Assessment List** — all assessments in the course, grouped by type; create/edit per type:
  - Personal/Team Assignment builder (questions + points) — [course-restructure-assessment-personal-assignment.md](course-restructure-assessment-personal-assignment.md), [course-restructure-assessment-team-assignment.md](course-restructure-assessment-team-assignment.md)
  - Quiz builder (config + questions/options) — [course-restructure-assessment-quiz.md](course-restructure-assessment-quiz.md)
  - Final Exam builder (exam type, period, questions) — [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)
  - Forum Discussion config (required posts per session) — [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md)
  - Attendance requirement config — [course-restructure-attendance.md](course-restructure-attendance.md)
- **Grading Queue** — list of ungraded attempts (assignments, essay/short-answer quiz answers, take-home exams) needing manual score/feedback/comment.
- **Proctor Review** — list of Open/Closed Book exam attempts with flagged Proctor Events, per-session evidence viewer, review decision form (see [course-restructure-proctor.md](course-restructure-proctor.md)).
- **Gradebook (course view)** — per-student Final Score table, drill into any student's breakdown.
- **People Management** — Teachers/Assistants list, Students list, Group management (create groups, assign/move students).
- **Attendance Management** — per-session attendance table, manual override, view auto-derived requirement fulfillment.

### Flow: Build out a course
1. Teacher creates Sessions (with materials, video conferences) → optionally groups them into Periods.
2. Fills the Syllabus (all 11 sections), defining the Evaluation table (weights must total 100% per class type) and Assessment Rubric.
3. Creates Assessments matching the Syllabus Evaluation activities (Forum Discussion, Attendance, Team/Personal Assignment, Quiz, Final Exam), setting weight/dates/config per type.
4. Creates Groups and assigns Students (for Team Assignment).
5. Publishes the course (or hands off to Admin to publish).

### Flow: Grade a submission
1. Teacher opens Grading Queue → selects an ungraded attempt.
2. Reviews answer (text/file), for Quiz/Final Exam objective questions score is pre-filled (auto-graded); essay/short-answer/assignment need manual score.
3. Enters score + optional comment → saves, which updates `assessment_scores` and cascades into Gradebook.
4. For Open/Closed Book exams, reviews the linked Proctor Session before finalizing the score (see Proctor Review flow below).

### Flow: Review a proctored exam attempt
1. Teacher opens Proctor Review → filters by risk score / unreviewed.
2. Opens a Proctor Session → reviews Proctor Events timeline and Snapshots.
3. Records `review_decision` (no_action / warning / disqualified) + notes.
4. If `disqualified`, the related `assessment_scores` is flagged/forced to 0.

### Flow: Manage attendance
1. Teacher opens Attendance Management for a session.
2. Views auto-derived attendance per student (based on configured requirements: forum posts, video conference duration).
3. Manually overrides status (present/absent/late/excused) where needed, with optional notes.

---

## Student

Student consumes course content, submits assessments, and tracks their own progress.

### Screens
- **Course Dashboard** — same tab structure as Teacher, but read/participate-only where applicable.
- **Session View** — session detail: description, learning material viewer, Video Conference join links + live/upcoming status.
- **Syllabus View** — read-only rendering of all 11 sections.
- **Forum** — browse/create threads, comment, like comments (general + per-session).
- **Assessment List** — assessments assigned to the student, grouped by type, with status (not started / in progress / submitted / graded) and due dates.
- **Assignment Attempt (Personal/Team)** — view questions, submit one whole-assignment answer (text + file); for Team, submission is shared with the Group.
- **Quiz Attempt** — Instruction Page (global) → start attempt (timer starts) → answer questions → submit (auto-submits on time limit).
- **Final Exam Attempt** — Open Book / Closed Book: proctor consent + setup (camera check) → Instruction Page → timed attempt with proctor monitoring running in background. Take Home: assignment-style, submit file before `end_date`.
- **Gradebook (student view)** — own Final Score card + per-assessment-type breakdown, expandable to per-session breakdown.
- **Attendance (student view)** — own Attendance Summary + per-session table with requirement checklist (see screenshot-driven design in [course-restructure-attendance.md](course-restructure-attendance.md)).
- **People (student view)** — read-only Teachers list, own Group (if any) with members.

### Flow: Submit a Personal/Team Assignment
1. Student opens Assessment List → selects an open assignment.
2. Reads questions (with attachments) → writes answer text and/or uploads a file.
3. Submits → creates a new `assessment_attempts` row (Team: any group member submitting counts for the whole group) + `assessment_answers`.
4. Waits for Teacher grading; sees score/feedback once graded, in Gradebook and on the assessment detail.

### Flow: Take a Quiz
1. Student opens Assessment List → selects an open quiz → sees the global Instruction Page first.
2. Starts attempt (if `total_attempts` allows another try) → timer starts per `time_limit_per_attempt`.
3. Answers each question → submits (or auto-submitted at time limit).
4. Objective questions are scored immediately; final score reflects `scoring_method` (highest/latest/average) across attempts.

### Flow: Take an Open/Closed Book Final Exam
1. Student opens Assessment List → selects the Final Exam → confirms proctoring consent, camera/environment check.
2. Sees the Instruction Page (shared with Quiz) plus exam-specific rules (no internet / no files, depending on `exam_type`).
3. Starts attempt — Proctor Session begins recording events/snapshots in the background.
4. Answers multiple choice + essay questions within `time_limit_per_attempt` and before `end_date`.
5. Submits — Proctor Session marked `completed`; Teacher reviews before final score is released.

### Flow: Participate in Forum Discussion (to meet assessment requirement)
1. Student opens a session's Forum tab.
2. Creates a thread or comments on an existing thread (at least 2 posts required per session, see [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md)).
3. Progress toward the 2-post minimum is reflected automatically in the Forum Discussion assessment score and the session's Attendance requirement ("Forum Completed").

### Flow: Attend a session (fulfill attendance)
1. Student opens Session View → joins the scheduled Video Conference via `meeting_url`.
2. System tracks join/leave time (`video_conference_participations`).
3. Once accumulated duration meets `required_duration_minutes`, the "Complete the Class Duration" requirement is marked fulfilled.
4. Attendance for that session flips to `present` once all configured requirements (forum + duration, or whichever apply) are fulfilled.

---

## Notes

This document is a structural plan/documentation only. No UI/Livewire component has been implemented yet — screens listed here are a planning checklist, to be broken down into implementation tasks per component.
