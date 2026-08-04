# Course Restructure — Assessment: Assignment Schema (shared)

## Definition

Shared schema used by both [THEORY: Personal Assignment](course-restructure-assessment-personal-assignment.md) and [THEORY: Team Assignment](course-restructure-assessment-team-assignment.md). Both assessment types have the same structure — questions and a single whole-assignment answer per attempt — the only difference is whether an attempt is tied to a `user_id` (personal) or a `group_id` (team, see [course-restructure-group.md](course-restructure-group.md)).

## Structure

### 1. Question
Each assignment has one or more questions.

- `assessment_id` (FK -> assessment, type = `theory_personal_assignment` or `theory_team_assignment`)
- `description` (text — the question text, can have file attachments, e.g. from media library)
- `points` (decimal/integer — max score for this question)
- `order`

### 2. Answer
The student (personal) or group (team) submits one answer for the whole assignment, covering all its questions together, not per individual question. Each answer can include a file and can receive a comment (e.g. instructor feedback).

- `assessment_attempt_id` (FK -> assessment attempt, see [course-restructure-assessment.md](course-restructure-assessment.md))
- `answer_text` (text, nullable)
- `answer_file` (nullable — attached file for the answer)
- `comment` (text, nullable — feedback on the answer)
- `score` (decimal, nullable — score given for the answer)

### 3. Attempt

Uses the generic `assessment_attempts` table (see [course-restructure-assessment.md](course-restructure-assessment.md)):

- Personal Assignment: `user_id` is filled, `group_id` is null.
- Team Assignment: `group_id` is filled, `user_id` is null; an attempt is counted as soon as **any one member** of the group submits — the attempt belongs to the group, not the individual. `submitted_by` (user_id) records which member actually performed the submission, for audit purposes.

## Schema Summary (proposal)

```
assessment_questions
  - id
  - assessment_id
  - description (text)
  - points (decimal)
  - order

assessment_question_files (pivot, optional attachments on the question)
  - id
  - assessment_question_id
  - file (attachment, e.g. from media library)
  - order

assessment_attempts               -- generic table, see course-restructure-assessment.md
  - id
  - assessment_id
  - user_id (nullable)            -- filled for Personal Assignment
  - group_id (nullable)           -- filled for Team Assignment
  - submitted_by (user_id, nullable) -- for Team Assignment: which group member submitted
  - attempt_number (integer)
  - submitted_at (datetime, nullable)
  - created_at / updated_at

assessment_answers
  - id
  - assessment_attempt_id
  - answer_text (text, nullable)
  - answer_file (attachment, nullable)
  - comment (text, nullable)
  - score (decimal, nullable)
  - created_at / updated_at
```

- One submission = one `assessment_attempts` row + one `assessment_answers` row for the whole assessment.
- The `assessment_scores.score` for the attempt (see [course-restructure-assessment.md](course-restructure-assessment.md)) is based on `assessment_answers.score`, informed by grading against each question's `points` in `assessment_questions`.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
