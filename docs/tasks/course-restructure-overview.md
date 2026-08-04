# Course Restructure — Overview

## Purpose

Restructure the Course entity into a container for several learning components, following common LMS patterns (e.g. Moodle/Canvas). Course no longer holds only linear modules/lessons, but has the following functional sub-areas.

## Course Components

Every Course has:

1. **Session** — a learning meeting/week unit (see [course-restructure-session.md](course-restructure-session.md) for details).
2. **Syllabus**
3. **Forum**
4. **Assessment**
5. **Gradebook**
6. **People**
7. **Attendance**

---

## Soft Delete

Course uses soft delete (`deleted_at`), so a deleted course is not immediately removed from the database and can be restored.

- Add a `deleted_at` (nullable timestamp) column to the `courses` table via a separate migration.
- The `Course` model uses the `Illuminate\Database\Eloquent\SoftDeletes` trait.
- Child relations (module, lesson, session, etc.) need to be considered — whether they should be soft-deleted (cascade) or remain when the course is soft-deleted.

---

## 2. Syllabus

The overall course plan document, shown to participants as a general overview of the course.

**Main fields:**
- `course_id`
- `content` (rich text — general description, course objectives, grading policy, etc.)
- `attachments` (optional, supporting files from the media library)
- `updated_at`

## 3. Forum

Discussion space between participants and instructors within a course. Can be a general course forum or a per-session forum.

**Main fields:**
- `course_id`
- `session_id` (nullable — null means a general course forum, filled means a session-specific forum)
- `title`
- `created_by` (user_id)
- **Thread/Post** (child relation): `forum_id`, `user_id`, `body`, `parent_post_id` (nullable, for replies)

## 4. Assessment

A grading unit that can be a quiz, assignment, or exam, optionally tied to a specific session.

**Main fields:**
- `course_id`
- `session_id` (nullable — assessment can stand alone or be tied to a session)
- `title`
- `type` (quiz / assignment / exam)
- `description`
- `due_date`
- `max_score`
- `submission_type` (file upload / text / link, depending on type)

## 5. Gradebook

A recap of scores for all participants across all assessments in a course. Derived from Assessment data + participant submissions/scores, not an independent entity filled in manually.

**Main fields:**
- `course_id`
- `user_id` (must have `role_in_course = student` in People)
- `assessment_id`
- `score`
- `graded_by` (user_id of the instructor)
- `graded_at`
- `feedback` (optional)

## 6. People

A list of all course participants and their roles (instructor, assistant, student), distinct from the school's global RBAC roles — this is course-level membership.

**Main fields:**
- `course_id`
- `user_id`
- `role_in_course` (instructor / assistant / student)
- `enrolled_at`
- `status` (active / dropped / completed)

## 7. Attendance

Attendance records for participants per session (most relevant for offline delivery mode, but also applicable to online).

**Main fields:**
- `session_id`
- `user_id` (must have `role_in_course = student` in People)
- `status` (present / absent / late / excused)
- `recorded_by` (user_id of the instructor)
- `recorded_at`
- `notes` (optional)

---

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet — this will be broken down into separate tasks per component.
