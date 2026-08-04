# Course Restructure — Overview

## Purpose

Restructure the Course entity into a container for several learning components, following common LMS patterns (e.g. Moodle/Canvas). Course no longer holds only linear modules/lessons, but has the following functional sub-areas.

## Course Components

Every Course has:

1. **Session** — a learning meeting unit; sessions can be grouped into Periods (see [course-restructure-session.md](course-restructure-session.md)).
2. **Syllabus** — the course plan document, organized into 11 sections (see [course-restructure-syllabus.md](course-restructure-syllabus.md)).
3. **Forum** — discussion threads and comments (see [course-restructure-forum.md](course-restructure-forum.md)).
4. **Assessment** — 6 predefined grading types (see [course-restructure-assessment.md](course-restructure-assessment.md)).
5. **Gradebook** — derived score recap (see [course-restructure-gradebook.md](course-restructure-gradebook.md)).
6. **People** — course membership: teachers, students, groups (see [course-restructure-people.md](course-restructure-people.md)).
7. **Attendance** — per-session presence, auto-derived from requirements (see [course-restructure-attendance.md](course-restructure-attendance.md)).

Supporting components used across the above:

- **Group** — student groupings for team-based assessments (see [course-restructure-group.md](course-restructure-group.md)).
- **Proctor** — cheating-detection for Open/Closed Book Final Exams (see [course-restructure-proctor.md](course-restructure-proctor.md)).

A combined schema across all of the above is available at [course-restructure-schema.md](course-restructure-schema.md).

---

## Soft Delete

Course uses soft delete (`deleted_at`), so a deleted course is not immediately removed from the database and can be restored.

- Add a `deleted_at` (nullable timestamp) column to the `courses` table via a separate migration.
- The `Course` model uses the `Illuminate\Database\Eloquent\SoftDeletes` trait.
- Child relations (module, lesson, session, etc.) need to be considered — whether they should be soft-deleted (cascade) or remain when the course is soft-deleted.

---

## 1. Session

A learning meeting unit (title, learning outcome + subtopics, start/end date, delivery mode, learning material, video conference(s)). See [course-restructure-session.md](course-restructure-session.md) for full detail.

Sessions can be grouped into **Periods** (e.g. "Midterm Period"), used to scope which sessions a Final Exam covers (see [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)).

## 2. Syllabus

The overall course plan document, organized into 11 sections, each supporting rich text plus multiple file attachments from the media library. See [course-restructure-syllabus.md](course-restructure-syllabus.md) for full detail.

- Course Description
- Class Policies (scoped per delivery mode: F2F/Video Conference, Online, General)
- Submission and Collection of Assignment
- Tutorial Activity Plan
- Learning Outcomes (structured LO1, LO2, ... items)
- Evaluation (structured table: activity × weight × Learning Outcome mapping, grouped by class type)
- Assessment Rubric (structured table: Learning Outcome × Key Indicator × Proficiency Level)
- Teaching & Learning Strategies
- Textbooks
- Competency Map
- Video Overview

## 3. Forum

Discussion space made up of threads created by users. Each thread has a title, description, and comment count; each comment can be liked. See [course-restructure-forum.md](course-restructure-forum.md) for full detail.

**Main fields:**
- `course_id`
- `session_id` (nullable — null means a general course forum, filled means a session-specific forum)
- `title`
- `created_by` (user_id)
- **Thread**: `forum_id`, `user_id`, `title`, `description`, `comments_count`
- **Comment**: `thread_id`, `user_id`, `body`, `likes_count`
- **Comment Like**: `comment_id`, `user_id`

## 4. Assessment

A grading unit belonging to one of 6 predefined types, each with its own weight and detail doc:

| Type | Weight | Detail |
|---|---|---|
| Forum Discussion | 10% | [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md) |
| Attendance | 10% | [course-restructure-attendance.md](course-restructure-attendance.md) |
| THEORY: Team Assignment | 15% | [course-restructure-assessment-team-assignment.md](course-restructure-assessment-team-assignment.md) |
| THEORY: Quiz | 15% | [course-restructure-assessment-quiz.md](course-restructure-assessment-quiz.md) |
| THEORY: Personal Assignment | 20% | [course-restructure-assessment-personal-assignment.md](course-restructure-assessment-personal-assignment.md) |
| THEORY: FINAL EXAM | 30% | [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md) |

**Common fields:** `title`, `assigned_to` (individual/group), `start_date`, `end_date`, `status`, `attempt`, `score`. See [course-restructure-assessment.md](course-restructure-assessment.md) for the generic schema (`assessments`, `assessment_attempts`, `assessment_scores`) shared by all types.

## 5. Gradebook

A recap of scores for all students across all assessment types in a course. Fully derived from Assessment data — not manually entered. Displayed as a **Final Score** summary (weight/score/grade) plus a breakdown per assessment type, expandable to a per-session breakdown where applicable. See [course-restructure-gradebook.md](course-restructure-gradebook.md) for full detail.

**Main fields:**
- `course_id`
- `user_id` (must have `role_in_course = student` in People)
- `assessment_type`
- `weight`, `score`
- `last_updated_at`

## 6. People

A list of all course participants and their roles (teacher, assistant, student), distinct from the school's global RBAC roles — this is course-level membership. Also lists Groups students are organized into. See [course-restructure-people.md](course-restructure-people.md) and [course-restructure-group.md](course-restructure-group.md) for full detail.

**Main fields:**
- `course_id`
- `user_id`
- `role_in_course` (teacher / assistant / student)
- `enrolled_at`
- `status` (active / dropped / completed)

## 7. Attendance

Attendance records for participants per session, auto-derived from configurable requirements (manual check-in, forum participation, video conference duration) rather than only manual marking. See [course-restructure-attendance.md](course-restructure-attendance.md) for full detail.

**Main fields:**
- `session_id`
- `user_id` (must have `role_in_course = student` in People)
- `status` (present / absent / late / excused)
- `recorded_by` (user_id of the instructor, nullable when auto-derived)
- `recorded_at`
- `notes` (optional)

---

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet — this will be broken down into separate tasks per component.
