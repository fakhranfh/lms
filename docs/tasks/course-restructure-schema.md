# Course Restructure — Combined Schema

## Definition

Combined database schema across all Course Restructure documents. This is a reference index only — each table's authoritative definition and rationale lives in its source document (linked per section). If a table changes, update it in its source doc first, then mirror the change here.

## Course

```
courses
  - id
  - school_id
  - title
  - description
  - created_by
  - is_published
  - slug
  - deleted_at            -- soft delete
  - created_at / updated_at
```

## Session — [course-restructure-session.md](course-restructure-session.md)

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

## Period — [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)

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
```

## Syllabus — [course-restructure-syllabus.md](course-restructure-syllabus.md)

```
syllabuses
  - id
  - course_id
  - course_description (text, nullable)
  - submission_and_collection (text, nullable)
  - tutorial_activity_plan (text, nullable)
  - teaching_learning_strategies (text, nullable)
  - textbooks (text, nullable)
  - competency_map (text, nullable)
  - video_overview (text, nullable)
  - created_at / updated_at

syllabus_class_policies
  - id
  - syllabus_id
  - scope (enum: f2f_video, online, general)
  - content (text)
  - order

syllabus_learning_outcomes
  - id
  - syllabus_id
  - code (string, e.g. LO1)
  - description (text)
  - order

syllabus_evaluations
  - id
  - syllabus_id
  - class_type (string, e.g. LEC, LAB)
  - order

syllabus_evaluation_activities
  - id
  - syllabus_evaluation_id
  - activity (string)
  - weight (decimal)
  - order

syllabus_evaluation_activity_learning_outcome (pivot)
  - id
  - syllabus_evaluation_activity_id
  - learning_outcome_id (FK -> syllabus_learning_outcomes)

syllabus_rubric_key_indicators
  - id
  - learning_outcome_id (FK -> syllabus_learning_outcomes)
  - code (string, e.g. 1.1)
  - description (text)
  - order

syllabus_rubric_proficiency_levels
  - id
  - syllabus_id
  - label (string, e.g. Excellent)
  - score_min (integer)
  - score_max (integer)
  - order

syllabus_rubric_cells
  - id
  - rubric_key_indicator_id (FK -> syllabus_rubric_key_indicators)
  - rubric_proficiency_level_id (FK -> syllabus_rubric_proficiency_levels)
  - description (text)

syllabus_materials (pivot)
  - id
  - syllabus_id
  - section (enum: course_description, class_policies, submission_and_collection,
             tutorial_activity_plan, learning_outcomes, evaluation, assessment_rubric,
             teaching_learning_strategies, textbooks, competency_map, video_overview)
  - lesson_material_id (FK -> media library)
  - order
```

## Forum — [course-restructure-forum.md](course-restructure-forum.md)

```
forums
  - id
  - course_id
  - session_id (nullable — null = general course forum)

forum_threads
  - id
  - forum_id
  - user_id
  - title (string)
  - description (text)
  - comments_count (integer, default 0)
  - created_at / updated_at

forum_comments
  - id
  - thread_id
  - user_id
  - body (text)
  - likes_count (integer, default 0)
  - created_at / updated_at

forum_comment_likes
  - id
  - comment_id
  - user_id
  - created_at
  - unique (comment_id, user_id)
```

## Assessment (generic) — [course-restructure-assessment.md](course-restructure-assessment.md)

```
assessments
  - id
  - course_id
  - session_id (nullable)
  - type (enum: theory_personal_assignment, theory_team_assignment, theory_quiz,
          theory_final_exam, attendance, forum_discussion)
  - title (string)
  - weight (decimal/percentage)
  - assigned_to (enum: individual, group)
  - start_date (datetime)
  - end_date (datetime)
  - status (enum: draft, published, ongoing, closed)
  - required_posts_per_session (integer, nullable — only for forum_discussion)
  - created_at / updated_at

assessment_attempts
  - id
  - assessment_id
  - user_id (nullable — filled when assigned_to = individual)
  - group_id (nullable — filled when assigned_to = group)
  - submitted_by (user_id, nullable — team assignment: which group member submitted)
  - attempt_number (integer)
  - started_at (datetime, nullable)
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

## Assignment (Personal / Team) — [course-restructure-assessment-assignment-schema.md](course-restructure-assessment-assignment-schema.md)

```
assessment_questions
  - id
  - assessment_id
  - description (text)
  - points (decimal)
  - order

assessment_question_files (pivot)
  - id
  - assessment_question_id
  - file (attachment)
  - order

assessment_answers
  - id
  - assessment_attempt_id
  - answer_text (text, nullable)
  - answer_file (attachment, nullable)
  - comment (text, nullable)
  - score (decimal, nullable)
  - created_at / updated_at
```

## Quiz — [course-restructure-assessment-quiz.md](course-restructure-assessment-quiz.md)

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

quiz_instructions              -- single global row (not per quiz)
  - id
  - content (text)
  - updated_by (user_id, nullable)
  - updated_at

quiz_questions
  - id
  - quiz_id
  - description (text)
  - points (decimal)
  - question_type (enum: multiple_choice, true_false, short_answer, essay)
  - order

quiz_question_options
  - id
  - quiz_question_id
  - label (text)
  - is_correct (boolean)
  - order

assessment_quiz_answers
  - id
  - assessment_attempt_id
  - quiz_question_id
  - selected_option_id (nullable, FK -> quiz_question_options)
  - answer_text (text, nullable)
  - score (decimal, nullable)
```

## Final Exam — [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)

```
final_exams
  - id
  - assessment_id (unique, FK -> assessments, type = theory_final_exam)
  - period_id (FK -> periods)
  - exam_type (enum: open_book, closed_book, take_home)
  - start_date (datetime)
  - end_date (datetime)
  - allow_local_files (boolean, nullable — relevant for open_book/closed_book)
  - allow_internet (boolean, nullable — relevant for open_book/closed_book)
  - created_at / updated_at
```

Note: `open_book`/`closed_book` reuse the Quiz tables above (via a `quiz_id` tied to the `final_exam`); `take_home` reuses the Assignment tables (`assessment_questions`, `assessment_answers`).

## Proctor — [course-restructure-proctor.md](course-restructure-proctor.md)

```
proctor_sessions
  - id
  - assessment_attempt_id
  - status (enum: active, completed, terminated)
  - started_at (datetime)
  - ended_at (datetime, nullable)
  - risk_score (integer, nullable)
  - reviewed_by (user_id, nullable)
  - reviewed_at (datetime, nullable)
  - review_decision (enum: no_action, warning, disqualified, nullable)
  - review_notes (text, nullable)
  - created_at / updated_at

proctor_events
  - id
  - proctor_session_id
  - event_type (enum: tab_switch, window_blur, multiple_faces, no_face_detected,
                face_mismatch, copy_paste, right_click, devtools_opened,
                fullscreen_exit, network_activity_detected, unauthorized_app_detected)
  - severity (enum: low, medium, high)
  - detected_at (datetime)
  - metadata (json, nullable)

proctor_snapshots
  - id
  - proctor_session_id
  - type (enum: webcam, screen)
  - captured_at (datetime)
  - file (attachment)
  - triggered_by_event_id (nullable, FK -> proctor_events)
```

## Gradebook — [course-restructure-gradebook.md](course-restructure-gradebook.md)

```
gradebook_grade_scales
  - id
  - course_id (nullable)
  - label (string, e.g. A)
  - score_min (integer)
  - score_max (integer)
  - order

gradebook_entries
  - id
  - course_id
  - user_id
  - assessment_type (enum, same as assessments.type)
  - weight (decimal)
  - score (decimal)
  - last_updated_at (datetime)

gradebook_session_entries
  - id
  - gradebook_entry_id
  - session_id
  - weight (decimal)
  - score (decimal)
```

## People & Group — [course-restructure-people.md](course-restructure-people.md), [course-restructure-group.md](course-restructure-group.md)

```
course_people
  - id
  - course_id
  - user_id
  - role_in_course (enum: teacher, assistant, student)
  - enrolled_at
  - status (enum: active, dropped, completed)

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
```

## Attendance — [course-restructure-attendance.md](course-restructure-attendance.md)

```
attendances
  - id
  - session_id
  - user_id
  - status (enum: present, absent, late, excused)
  - recorded_by (user_id, nullable)
  - recorded_at
  - notes (nullable)

attendance_requirements
  - id
  - course_id
  - requirement_type (enum: manual_checkin, forum_completed, class_duration_completed)
  - label (string)
  - order

course_attendance_settings
  - id
  - course_id
  - minimal_attendance (integer)
```

## Entity Relationship Summary

```
courses 1---N sessions
courses 1---N periods 1---N period_sessions N---1 sessions
courses 1---1 syllabuses
courses 1---N forums (nullable session_id)
courses 1---N assessments (nullable session_id)
courses 1---N course_people
courses 1---N groups 1---N group_members N---1 course_people(user_id)
courses 1---N gradebook_entries 1---N gradebook_session_entries

assessments 1---N assessment_attempts 1---1 assessment_scores
assessments 1---1 quizzes (type = theory_quiz)
assessments 1---1 final_exams (type = theory_final_exam) N---1 periods
assessments 1---N assessment_questions (personal/team assignment)
quizzes 1---N quiz_questions 1---N quiz_question_options
assessment_attempts 1---N assessment_answers (assignment) / assessment_quiz_answers (quiz/exam)
assessment_attempts 1---1 proctor_sessions (open_book/closed_book only) 1---N proctor_events, proctor_snapshots

sessions 1---N video_conferences 1---N video_conference_participations
sessions 1---N attendances
forums 1---N forum_threads 1---N forum_comments 1---N forum_comment_likes
```

## Notes

This document is a structural plan/documentation only, aggregating tables already proposed in the individual Course Restructure docs. No migration/model/Livewire component has been implemented yet.
