# Course Restructure — Assessment: Forum Discussion

## Definition

Detail for the **Forum Discussion** assessment type (weight 10%, see [course-restructure-assessment.md](course-restructure-assessment.md)). Unlike the assignment types, this assessment is not answered directly — it is graded based on the student's participation in the course Forum (see [course-restructure-forum.md](course-restructure-forum.md)).

## Rule

Each student must post in the forum **at least 2 times per session**. A "post" counts as either a thread or a comment created by the student within a session's discussion scope.

- `assessment_id` (FK -> assessment, type = `forum_discussion`)
- `required_posts_per_session` (integer, default `2`)

## Tracking

Participation is derived from existing Forum data (`forum_threads` and `forum_comments`, see [course-restructure-forum.md](course-restructure-forum.md)). A thread/comment's session is resolved via its `forum_id` -> `forums.session_id` (session-specific forum, see [course-restructure-overview.md](course-restructure-overview.md)).

- **Session Participation** (derived, per student per session): count of `forum_threads` + `forum_comments` created by the student within the forum tied to that session.
- A session is considered "fulfilled" for a student once their post count for that session reaches `required_posts_per_session`.

## Attempt & Score

Unlike assignment-type assessments, there is no manual "submit" action — the attempt/score is computed automatically from forum activity.

- `assessment_attempts` (see [course-restructure-assessment.md](course-restructure-assessment.md)): one row per student per session once the minimum post requirement is first met, or recalculated continuously until `end_date`.
- `assessment_scores.score`: derived — e.g. percentage of sessions where the student met `required_posts_per_session`, applied against the assessment's weight (10%).

## Schema Summary (proposal)

```
assessments
  - ... (see course-restructure-assessment.md)
  - required_posts_per_session (integer, default 2, only relevant when type = forum_discussion)
```

No new tables are needed beyond the existing `forum_threads` / `forum_comments` (see [course-restructure-forum.md](course-restructure-forum.md)) and the generic `assessment_attempts` / `assessment_scores` (see [course-restructure-assessment.md](course-restructure-assessment.md)). Per-session tracking relies on the forum's existing `session_id` (via `forums.session_id`, see [course-restructure-overview.md](course-restructure-overview.md)) — for this assessment type, discussions must therefore happen in a per-session forum rather than the general course forum.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
