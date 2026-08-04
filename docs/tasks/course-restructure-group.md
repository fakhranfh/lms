# Course Restructure — Group

## Definition

Group is a set of students grouped together within a Course, used by assessments with `assigned_to = group` (e.g. THEORY: Team Assignment, see [course-restructure-assessment-team-assignment.md](course-restructure-assessment-team-assignment.md)). A group belongs to a course and has one or more student members.

## Structure

### 1. Group

- `course_id` (FK -> course)
- `name` (string, e.g. "Group 1")
- `created_by` (user_id, nullable — instructor or system if auto-generated)
- `created_at` / `updated_at`

### 2. Group Member

Student membership within a group. A student belongs to at most one group per course (unless the design later allows multiple groupings per course for different assessments).

- `group_id` (FK -> group)
- `user_id` (FK -> user, must be a student enrolled in the course via People, see [course-restructure-overview.md](course-restructure-overview.md))
- `joined_at`

## Schema Summary (proposal)

```
groups
  - id
  - course_id
  - name (string)
  - created_by (nullable)
  - created_at / updated_at

group_members
  - id
  - group_id
  - user_id
  - joined_at
  - unique (group_id, user_id)
  - unique (course_id via group, user_id) -- a student can only be in one group per course
```

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
