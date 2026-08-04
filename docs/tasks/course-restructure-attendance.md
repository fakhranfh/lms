# Course Restructure — Attendance

## Definition

Attendance tracks a student's presence per session (see [course-restructure-overview.md](course-restructure-overview.md), [course-restructure-session.md](course-restructure-session.md)). Displayed per student as an **Attendance Summary** header followed by a per-session table.

## Structure

### 1. Attendance Summary
Top-level summary card for the student in the course.

- `total_session` — total number of sessions in the course
- `total_attendance` — number of sessions the student attended
- `minimal_attendance` — minimum number of sessions the student is required to attend (course/school policy)

### 2. Session Attendance Row
One row per session.

- `session` (title, e.g. "Session 2")
- `delivery` (e.g. "Online - OL", "Offline - F2F" — from `sessions.delivery_mode`, see [course-restructure-session.md](course-restructure-session.md))
- `start_date` / `end_date` (from the session)
- `attend` (boolean — whether the student is marked present for this session)
- `attendance_requirements` — checklist of conditions that must be fulfilled for the session to count as attended (e.g. "Forum Completed" — tied to the Forum Discussion assessment requirement, see [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md))

## Attendance Requirement

A session's attendance can depend on one or more requirements being met, not just manual check-in. Each requirement is evaluated automatically.

- `requirement_type` (enum: `manual_checkin`, `forum_completed`, `class_duration_completed`)
- `label` (string, e.g. "Forum Completed", "Complete the Class Duration")
- `is_fulfilled` (boolean, derived):
  - `forum_completed` — whether the student met `required_posts_per_session` for that session (see [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md)).
  - `class_duration_completed` — whether the student attended the session's Video Conference(s) for at least the required duration (see [course-restructure-session.md](course-restructure-session.md)).

Video Conferences and their participation tracking (used to compute `class_duration_completed`) now live in [course-restructure-session.md](course-restructure-session.md), since a Video Conference belongs to a Session.

## Schema Summary (proposal)

```
attendances                       -- see course-restructure-overview.md
  - id
  - session_id
  - user_id                       -- must have role_in_course = student in People
  - status (enum: present, absent, late, excused)
  - recorded_by (user_id, nullable — null when auto-derived from requirements)
  - recorded_at
  - notes (nullable)

attendance_requirements
  - id
  - course_id
  - requirement_type (enum: manual_checkin, forum_completed, class_duration_completed)
  - label (string)
  - order

course_attendance_settings
  - id
  - course_id
  - minimal_attendance (integer — minimum sessions required)
```

- `total_session` = count of `sessions` for the course.
- `total_attendance` = count of `attendances` for the student where `status = present`.
- `minimal_attendance` = `course_attendance_settings.minimal_attendance`.
- A session's `attend` value is `true` only once all of that session's `attendance_requirements` are fulfilled (or, for `manual_checkin`, once an instructor records `present`).

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
