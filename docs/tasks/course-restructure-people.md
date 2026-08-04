# Course Restructure — People

## Definition

People is the course-level membership roster (see [course-restructure-overview.md](course-restructure-overview.md)). It lists every user participating in a Course — teachers and students — plus the Groups (see [course-restructure-group.md](course-restructure-group.md)) students are organized into.

There is no dedicated `students`/`teachers` table — membership is expressed via `user_id` + `role_in_course` against the shared `users` table.

## Structure

### 1. Teacher
A user with `role_in_course = teacher` (or `assistant`) on the course.

- `course_id`
- `user_id`
- `role_in_course` (enum: `teacher`, `assistant`)
- `enrolled_at`
- `status` (active / dropped / completed)

### 2. Student
A user with `role_in_course = student` on the course.

- `course_id`
- `user_id`
- `role_in_course` = `student`
- `enrolled_at`
- `status` (active / dropped / completed)
- `group` (nullable — the Group this student belongs to, if any, see below)

### 3. Group
Students within the course can be organized into Groups (see [course-restructure-group.md](course-restructure-group.md) for full detail), used for group-based assessments like THEORY: Team Assignment (see [course-restructure-assessment-team-assignment.md](course-restructure-assessment-team-assignment.md)).

- `course_id`
- `name`
- members: list of Students (see [course-restructure-group.md](course-restructure-group.md))

## People Page (view)

The People page presents three lists/tabs for a course:

- **Teachers** — all users with `role_in_course` in (`teacher`, `assistant`)
- **Students** — all users with `role_in_course = student`, showing each student's assigned Group (if any)
- **Groups** — all Groups in the course, each showing its member list

## Schema Summary (proposal)

```
course_people                     -- shared membership table, see course-restructure-overview.md
  - id
  - course_id
  - user_id
  - role_in_course (enum: teacher, assistant, student)
  - enrolled_at
  - status (enum: active, dropped, completed)

groups                            -- see course-restructure-group.md
  - id
  - course_id
  - name (string)
  - created_by (nullable)
  - created_at / updated_at

group_members                     -- see course-restructure-group.md
  - id
  - group_id
  - user_id
  - joined_at
```

- `course_people` is the single source of truth for who belongs to a course and in what role; Teachers, Students, and eligible Group members are all filtered views over this table.
- Only users with `role_in_course = student` in `course_people` may be added to `group_members` for that course.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
