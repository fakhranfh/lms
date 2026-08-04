# Course Restructure — Assessment: THEORY Personal Assignment

## Definition

Detail for the **THEORY: Personal Assignment** assessment type (weight 20%, see [course-restructure-assessment.md](course-restructure-assessment.md)). A personal assignment consists of one or more questions, and a student submits one answer covering all questions in a single attempt.

Uses the shared schema in [course-restructure-assessment-assignment-schema.md](course-restructure-assessment-assignment-schema.md) (questions, answer, attempt).

## Personal-specific notes

- Attempts (`assessment_attempts`) are tied to `user_id` — one student, one set of attempts.
- Every time a student submits an answer to the assignment, it counts as one new attempt, incrementing `attempt_number`.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
