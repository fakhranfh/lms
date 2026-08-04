# Course Restructure — Session

## Definition

Session is the learning meeting unit within a Course — replacing/complementing the previous Module/Lesson concept. One Course has many Sessions, each representing one meeting/topic with a specific time range.

## Field Structure

### 1. Title
Session name, e.g. "Session 1: Introduction to Algebra".

- `title` (string, required)

### 2. Description

Consists of two parts:

- **Learning outcome** — the expected learning result after completing this session.
- **Subtopic** — the list of subtopics covered in the session.

**Fields:**
- `learning_outcome` (text)
- `subtopics` (array/list of string, or a relation to a child table `session_subtopics` if ordering/more detailed structure is needed)

### 3. Date Start & End

The time range for the session.

- `date_start` (datetime, required)
- `date_end` (datetime, required)

### 4. Delivery Mode

Determines how the session is conducted.

- `delivery_mode` (enum: `online`, `offline`)

### 5. Learning Material

Learning material attached to the session, taken from the Media Library (not uploaded directly to the session).

- Many-to-many/pivot relation between Session and `LessonMaterial` (existing media library), e.g. `session_materials` (`session_id`, `lesson_material_id`, `order`).

### 6. Video Conference

A Session can have one or more Video Conferences (e.g. a rescheduled/split meeting still counts toward the same session). Used by Attendance to determine whether a student fulfills the "Complete the Class Duration" requirement (see [course-restructure-attendance.md](course-restructure-attendance.md)).

- `session_id` (FK -> session)
- `title` (string, nullable — e.g. "Main Meeting", "Makeup Session")
- `scheduled_start_at` / `scheduled_end_at` (datetime)
- `meeting_url` (string, nullable)
- `required_duration_minutes` (integer, nullable — minimum minutes a student must be present across the session's video conference(s) to fulfill the attendance requirement)

**Video Conference Participation** — tracks a student's actual join/leave activity per video conference, used to compute total duration attended.

- `video_conference_id`
- `user_id`
- `joined_at` (datetime)
- `left_at` (datetime, nullable)
- `duration_minutes` (integer, derived — `left_at` - `joined_at`, summed across multiple join/leave segments if the student rejoins)

## Schema Summary (proposal)

```
sessions
  - id
  - course_id
  - title
  - learning_outcome (text)
  - date_start (datetime)
  - date_end (datetime)
  - delivery_mode (enum: online, offline)
  - created_at / updated_at

session_subtopics
  - id
  - session_id
  - subtopic (string)
  - order

session_materials (pivot)
  - id
  - session_id
  - lesson_material_id (FK -> media library)
  - order

video_conferences
  - id
  - session_id
  - title (string, nullable)
  - scheduled_start_at (datetime)
  - scheduled_end_at (datetime)
  - meeting_url (string, nullable)
  - required_duration_minutes (integer, nullable)
  - created_at / updated_at

video_conference_participations
  - id
  - video_conference_id
  - user_id
  - joined_at (datetime)
  - left_at (datetime, nullable)
```

## Relations to Other Components

- **Forum** and **Assessment** can optionally be tied to `session_id` (see [course-restructure-overview.md](course-restructure-overview.md)).
- **Attendance** is always tied to `session_id`, and can depend on Video Conference participation (see [course-restructure-attendance.md](course-restructure-attendance.md)).

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
