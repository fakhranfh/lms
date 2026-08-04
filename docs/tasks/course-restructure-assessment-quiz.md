# Course Restructure — Assessment: THEORY Quiz

## Definition

Detail for the **THEORY: Quiz** assessment type (weight 15%, see [course-restructure-assessment.md](course-restructure-assessment.md)). Each `theory_quiz` assessment has exactly one Quiz record holding the quiz-specific configuration; questions belong to the Quiz, not directly to the assessment.

```
assessment (type = theory_quiz) 1---1 quiz 1---N question
```

## Quiz Configuration

- `assessment_id` (FK -> assessment, type = `theory_quiz`, 1-to-1)
- `start_date` (datetime, required — when the quiz becomes available)
- `due_date` (datetime, required — when the quiz closes)
- `total_question` (integer — number of questions in the quiz)
- `total_attempts` (integer — max attempts allowed per student; unlimited if null)
- `scoring_method` (enum: `highest`, `latest`, `average` — how the final score is determined when `total_attempts` > 1)
- `time_limit_per_attempt` (integer, minutes — nullable, unlimited if null)

## Instruction Page

A single, global instruction page shown to students before starting any quiz (not configurable per quiz). Explains general rules — e.g. no leaving the page during a timed attempt, auto-submit on time limit, how `scoring_method` works, academic honesty reminder.

- `content` (text, rich text)
- `updated_at` / `updated_by`

## Structure

### 1. Question
Belongs to a Quiz. Quizzes typically use objective question types (multiple choice, true/false) for automated scoring.

- `quiz_id` (FK -> quiz)
- `description` (text — the question text, can have file attachments, e.g. from media library)
- `points` (decimal/integer — max score for this question)
- `question_type` (enum: `multiple_choice`, `true_false`, `short_answer`)
- `order`

### 2. Question Option
For `multiple_choice` / `true_false` question types.

- `quiz_question_id`
- `label` (text)
- `is_correct` (boolean)
- `order`

### 3. Attempt
Each time a student starts and submits the quiz, it counts as one `assessment_attempts` record (see [course-restructure-assessment.md](course-restructure-assessment.md)), up to `total_attempts`.

- `started_at` (datetime)
- `submitted_at` (datetime, nullable — null while in progress)
- Enforced against `time_limit_per_attempt`: an attempt still open past the limit is auto-submitted with whatever answers were saved.

### 4. Answer
One answer per question per attempt (unlike the whole-assignment answer used by Personal/Team Assignment), since quiz questions are graded individually and often automatically.

- `assessment_attempt_id`
- `quiz_question_id`
- `selected_option_id` (nullable — for multiple_choice/true_false)
- `answer_text` (text, nullable — for short_answer)
- `score` (decimal, nullable — auto-computed for objective types, manually graded for short_answer)

## Schema Summary (proposal)

```
quizzes
  - id
  - assessment_id (unique, FK -> assessments, type = theory_quiz)
  - start_date (datetime)
  - due_date (datetime)
  - total_question (integer)
  - total_attempts (integer, nullable)
  - scoring_method (enum: highest, latest, average)
  - time_limit_per_attempt (integer minutes, nullable)
  - created_at / updated_at

quiz_instructions                 -- single global row (not per quiz)
  - id
  - content (text)
  - updated_by (user_id, nullable)
  - updated_at

quiz_questions
  - id
  - quiz_id
  - description (text)
  - points (decimal)
  - question_type (enum: multiple_choice, true_false, short_answer)
  - order

quiz_question_options
  - id
  - quiz_question_id
  - label (text)
  - is_correct (boolean)
  - order

assessment_attempts               -- generic table, see course-restructure-assessment.md
  - id
  - assessment_id
  - user_id
  - attempt_number (integer)
  - started_at (datetime)
  - submitted_at (datetime, nullable)
  - created_at / updated_at

assessment_quiz_answers
  - id
  - assessment_attempt_id
  - quiz_question_id
  - selected_option_id (nullable, FK -> quiz_question_options)
  - answer_text (text, nullable)
  - score (decimal, nullable)
```

- Final `assessment_scores.score` (see [course-restructure-assessment.md](course-restructure-assessment.md)) is derived from `assessment_quiz_answers.score` across the attempt, then combined across attempts according to `scoring_method`.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
