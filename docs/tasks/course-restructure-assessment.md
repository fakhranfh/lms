# Course Restructure — Assessment

## Definition

Assessment is a grading unit within a Course (see [course-restructure-overview.md](course-restructure-overview.md)). Each assessment belongs to one of the predefined types below, and its weight should correspond to the Evaluation breakdown defined in the course Syllabus (see section 6 of [course-restructure-syllabus.md](course-restructure-syllabus.md)).

## Assessment Types

| Type | Weight |
|---|---|
| THEORY: Personal Assignment | 20% |
| THEORY: Team Assignment | 15% |
| THEORY: Quiz | 15% |
| THEORY: FINAL EXAM | 30% |
| Attendance | 10% |
| Forum Discussion | 10% |

## Common Fields

Every assessment, regardless of type, has:

- `title` (string, required)
- `assigned_to` (enum: `individual`, `group`)
- `start_date` (datetime, required)
- `end_date` (datetime, required)
- `status` (enum: `draft`, `published`, `ongoing`, `closed`)
- `attempt` — attempt tracking per participant/group (see below)
- `score` — grading result per participant/group (see below)

## Schema Summary (proposal)

```
assessments
  - id
  - course_id
  - session_id (nullable — see course-restructure-session.md)
  - type (enum: theory_personal_assignment, theory_team_assignment, theory_quiz,
          theory_final_exam, attendance, forum_discussion)
  - title (string)
  - weight (decimal/percentage)
  - assigned_to (enum: individual, group)
  - start_date (datetime)
  - end_date (datetime)
  - status (enum: draft, published, ongoing, closed)
  - created_at / updated_at

assessment_attempts
  - id
  - assessment_id
  - user_id (nullable — filled when assigned_to = individual)
  - group_id (nullable — filled when assigned_to = group)
  - attempt_number (integer)
  - submitted_at (datetime, nullable)
  - created_at / updated_at

assessment_scores
  - id
  - assessment_attempt_id
  - score (decimal)
  - graded_by (user_id)
  - graded_at (datetime)
  - feedback (text, nullable)
```

- When `assigned_to = group`, `assessment_attempts.group_id` references the group/team roster for that assessment (grouping mechanism to be defined together with the People component — see [course-restructure-overview.md](course-restructure-overview.md)).
- `assessment_scores` feeds into the Gradebook component (see [course-restructure-overview.md](course-restructure-overview.md)).

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
