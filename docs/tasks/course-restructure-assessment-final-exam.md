# Course Restructure — Assessment: THEORY Final Exam

## Definition

Detail for the **THEORY: FINAL EXAM** assessment type (weight 30%, see [course-restructure-assessment.md](course-restructure-assessment.md)). A final exam has exactly one **exam type**, chosen per exam instance:

- **Open Book**
- **Closed Book**
- **Take Home**

```
assessment (type = theory_final_exam) 1---1 final_exam N---1 period 1---N session
```

A Final Exam is scoped to a **Period** rather than a single Session — a Period groups multiple Sessions together (e.g. all sessions up to the midterm, or the whole term), and the exam covers everything within that Period.

## Common Configuration

- `assessment_id` (FK -> assessment, type = `theory_final_exam`, 1-to-1)
- `period_id` (FK -> period — the period this exam covers)
- `exam_type` (enum: `open_book`, `closed_book`, `take_home`)
- `start_date` (datetime, required — when the exam becomes available)
- `end_date` (datetime, required — hard deadline; the exam must be completed/submitted before this time)

## Period

A Period groups multiple Sessions (see [course-restructure-session.md](course-restructure-session.md)) within a Course. Used to scope which sessions' material a Final Exam covers.

- `course_id` (FK -> course)
- `title` (string, e.g. "Midterm Period", "Final Period")
- `order`
- **Period Session** (child relation, many-to-many with Session): `period_id`, `session_id`, `order`

## Exam Types

### 1. Open Book
Structured like a Quiz (see [course-restructure-assessment-quiz.md](course-restructure-assessment-quiz.md)): questions are multiple choice and essay. Students are allowed to open files stored on their own device, but must not access the internet during the attempt.

- Reuses the Quiz structure (`quiz`, `quiz_questions`, `quiz_question_options`, `assessment_quiz_answers`) with `question_type` including `essay` in addition to `multiple_choice`.
- No auto-fullscreen; switching windows/apps is allowed, but switching browser tabs or navigating to another site is flagged (see Proctor rules below).
- Requires Proctor monitoring (see [course-restructure-proctor.md](course-restructure-proctor.md)).

### 2. Closed Book
Same structure as Open Book (multiple choice + essay, quiz-like), but no files or references of any kind may be opened during the attempt.

- Auto-enters fullscreen on start; exiting fullscreen for any reason is flagged.
- Requires Proctor monitoring (see [course-restructure-proctor.md](course-restructure-proctor.md)).

### 3. Take Home
Structured like an Assignment (see [course-restructure-assessment-assignment-schema.md](course-restructure-assessment-assignment-schema.md)): the student works on the exam independently and submits by uploading a file within the `start_date`–`end_date` window. No proctoring is required.

- Reuses the Assignment structure (`assessment_questions`, `assessment_answers`, whole-exam answer with file upload).

## Attempt

- Uses the generic `assessment_attempts` table (see [course-restructure-assessment.md](course-restructure-assessment.md)).
- An attempt must be started and submitted before `end_date`; an attempt still open at `end_date` is auto-submitted with whatever was saved (open/closed book) or marked as a missed submission (take home, if nothing was uploaded).

## Schema Summary (proposal)

```
periods
  - id
  - course_id
  - title (string)
  - order
  - created_at / updated_at

period_sessions (pivot)
  - id
  - period_id
  - session_id
  - order

final_exams
  - id
  - assessment_id (unique, FK -> assessments, type = theory_final_exam)
  - period_id (FK -> periods)
  - exam_type (enum: open_book, closed_book, take_home)
  - start_date (datetime)
  - end_date (datetime)
  - created_at / updated_at
```

- For `exam_type = open_book` or `closed_book`, question/answer data lives in the Quiz tables (`quiz_questions`, `quiz_question_options`, `assessment_quiz_answers`, see [course-restructure-assessment-quiz.md](course-restructure-assessment-quiz.md)), keyed off a `quiz_id` tied to this `final_exam` instead of a plain quiz assessment. `question_type` adds `essay` (manually graded, similar to `short_answer`).
- For `exam_type = take_home`, question/answer data lives in the Assignment tables (`assessment_questions`, `assessment_answers`, see [course-restructure-assessment-assignment-schema.md](course-restructure-assessment-assignment-schema.md)).
- For `exam_type = open_book` or `closed_book`, a Proctor Session is created per attempt (see [course-restructure-proctor.md](course-restructure-proctor.md)).

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
