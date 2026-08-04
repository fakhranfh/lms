# Course Restructure — Gradebook

## Definition

Gradebook is the recap of a student's scores in a Course (see [course-restructure-overview.md](course-restructure-overview.md)). It is fully derived from Assessment data (see [course-restructure-assessment.md](course-restructure-assessment.md)) — not manually entered. Displayed as a **Final Score** summary followed by a breakdown per assessment type, each expandable to a per-session breakdown where applicable.

## Structure

### 1. Final Score
Top-level summary card for the course.

- `weight` — always `100%` (sum of all assessment type weights)
- `score` — weighted average across all assessment types
- `grade` — letter grade derived from `score` via a grading scale
- `last_updated` — most recent `graded_at` across all contributing assessments

### 2. Assessment Type Row
One row per assessment type (Forum Discussion, Attendance, THEORY: Team Assignment, THEORY: Quiz, THEORY: Personal Assignment, THEORY: FINAL EXAM — see [course-restructure-assessment.md](course-restructure-assessment.md)).

- `assessment_type`
- `weight` — the type's weight as defined in the Syllabus Evaluation (see [course-restructure-syllabus.md](course-restructure-syllabus.md))
- `score` — the student's score for that type (0-100)
- `last_updated`
- Expandable (chevron) to show a **session-level breakdown**, for types that are tracked per session (e.g. Forum Discussion, Attendance).

### 3. Session Breakdown (within an expanded type)
Shown when an assessment type spans multiple sessions (e.g. Forum Discussion tracked per session, see [course-restructure-assessment-forum-discussion.md](course-restructure-assessment-forum-discussion.md)).

- `session` (title + delivery mode, e.g. "Session 2 - Online")
- `weight` — the type's total weight divided evenly across its contributing sessions (e.g. Forum Discussion 10% ÷ 8 sessions ≈ 1% each)
- `score` — the student's score for that session's contribution (0-100)

## Grading Scale

Reuses the same proficiency-level style as the Assessment Rubric (see [course-restructure-syllabus.md](course-restructure-syllabus.md)) but applied to the final numeric score.

- `label` (e.g. `A`, `B`, `C`, `D`, `E`)
- `score_min`, `score_max`
- `course_id` (nullable — school-level default if null, overridable per course)

## Schema Summary (proposal)

```
gradebook_grade_scales
  - id
  - course_id (nullable)
  - label (string, e.g. A)
  - score_min (integer)
  - score_max (integer)
  - order

gradebook_entries               -- one row per user per assessment type, derived/cached
  - id
  - course_id
  - user_id                      -- must have role_in_course = student in the course's People roster
  - assessment_type (enum, same as assessments.type)
  - weight (decimal)
  - score (decimal)
  - last_updated_at (datetime)

gradebook_session_entries       -- one row per user per session, within an assessment_type
  - id
  - gradebook_entry_id
  - session_id
  - weight (decimal)
  - score (decimal)
```

- There is no dedicated `students` table — the app has a single `users` table with roles. `gradebook_entries.user_id` must reference a user whose `role_in_course` in the course's People roster is `student` (see [course-restructure-overview.md](course-restructure-overview.md)); this should be enforced at the application/query level (e.g. only generating/showing gradebook entries for course members with that role), not via a separate foreign table.
- `gradebook_entries` and `gradebook_session_entries` are denormalized/cached recomputations of `assessment_scores` (see [course-restructure-assessment.md](course-restructure-assessment.md)), refreshed whenever an underlying assessment is graded.
- The Final Score `score` = Σ(`gradebook_entries.weight` × `gradebook_entries.score`) across all assessment types for that student.
- Within a type, `gradebook_session_entries.weight` = the type's total weight ÷ number of contributing sessions; `gradebook_entries.score` = weighted average of its session entries.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
