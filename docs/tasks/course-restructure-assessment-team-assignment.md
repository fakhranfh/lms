# Course Restructure — Assessment: THEORY Team Assignment

## Definition

Detail for the **THEORY: Team Assignment** assessment type (weight 15%, see [course-restructure-assessment.md](course-restructure-assessment.md)). Same structure as [THEORY: Personal Assignment](course-restructure-assessment-personal-assignment.md), except the assignment is answered on behalf of a Group (see [course-restructure-group.md](course-restructure-group.md)) instead of an individual student.

Uses the shared schema in [course-restructure-assessment-assignment-schema.md](course-restructure-assessment-assignment-schema.md) (questions, answer, attempt).

## Team-specific notes

- Attempts (`assessment_attempts`) are tied to `group_id` instead of `user_id`.
- An attempt is counted as soon as **any one member** of the group submits an answer — the attempt belongs to the group as a whole, not to the individual student who submitted it.
- `submitted_by` (user_id) records which group member actually performed the submission, for audit purposes.
- `assessment_scores` for the attempt applies to all members of the group.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
