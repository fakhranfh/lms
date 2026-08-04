# Course Restructure — Syllabus

## Definition

Syllabus is the course plan document for a Course, organized into sections (tabs). It gives participants a complete overview of the course before/while they take it.

## Sections

Every section below supports both a text field and file attachments. Attachments are selected from the Media Library (not uploaded directly to the syllabus), and each section can have multiple attachments.

### 1. Course Description
General description of the course: background, scope, and what the course is about.

- `course_description` (text)
- attachments (optional, from media library)

### 2. Class Policies
List of policy points, each scoped to a delivery mode: **F2F/Video Conference Session**, **Online Session**, or **General** (applies to both, shown as a full-width row).

- **Class Policy item** (child relation): `syllabus_id`, `scope` (enum: `f2f_video`, `online`, `general`), `content` (text), `order`
- attachments (optional, from media library)

Example:

| F2F/Video Conference Session | Online Session |
|---|---|
| Student must attend class, and participate in classroom discussions | Student must be active in classroom discussion forum, responding to lecturer's questions and discussing with classmates |
| The ringing, beeping, buzzing of cell phones, watches during class time are extremely rude and disruptive... Please turn off all mobile phones and watches or change into silent mode before coming to the classroom. | Student must be active in team room, especially discussing team assignment |

General (full-width, applies to both):
- Student must read learning material and other references before class. Reading/case will be distributed before class. Team member and group discussion will be notified before class.
- Student must complete and submit all personal assignment and team assignment.
- Do not rely on handout distributed by lecturer, student can use other references.
- Achieve a satisfactory average grade on assignments and examinations.
- Penalty for cheating and plagiarism will be extremely severe. If you are not sure about certain activities, consult the instructor. Standard academic honesty procedure will be followed.

### 3. Submission and Collection of Assignment
Explanation of how assignments are submitted and collected (format, deadline, submission method).

- `submission_and_collection` (text)
- attachments (optional, from media library)

### 4. Tutorial Activity Plan
Plan of tutorial/practical activities across the course timeline.

- `tutorial_activity_plan` (text)
- attachments (optional, from media library)

### 5. Learning Outcomes
Overall learning outcomes expected from the entire course (distinct from per-session learning outcomes in [course-restructure-session.md](course-restructure-session.md)). Stored as individual items (LO1, LO2, ...) so each can be referenced/mapped by Evaluation activities (see section 6).

- **Learning Outcome item** (child relation): `syllabus_id`, `code` (e.g. `LO1`), `description`, `order`
- attachments (optional, from media library)

### 6. Evaluation
Structured grading breakdown, grouped by class type (e.g. LEC, LAB), listing each graded activity with its weight and which Learning Outcomes it maps to. The weights within a class type must total 100%.

- `class_type` (string, e.g. `LEC`, `LAB` — a syllabus can have more than one)
- **Evaluation Activity** (child relation, per class type):
  - `evaluation_id`
  - `activity` (string, e.g. "Forum Discussion", "THEORY: Final Exam")
  - `weight` (decimal/percentage)
  - `order`
- **Evaluation Activity ↔ Learning Outcome** (pivot, many-to-many): maps each activity to one or more Learning Outcomes (the checkmarks per LO column in the table)

Example (from `LEC (100%)`):

| Activity | Weight | LO1 | LO2 | LO3 | LO4 |
|---|---|---|---|---|---|
| Forum Discussion | 10% | ✓ | ✓ | ✓ | ✓ |
| Attendance | 10% | ✓ | ✓ | ✓ | ✓ |
| THEORY: Final Exam | 30% | ✓ | ✓ | ✓ | ✓ |
| THEORY: Team Assignment | 15% | ✓ | ✓ | ✓ | ✓ |
| THEORY: Personal Assignment | 20% | ✓ | ✓ | ✓ | ✓ |
| THEORY: Quiz | 15% | ✓ | ✓ | ✓ | ✓ |
| **TOTAL** | **100%** | | | | |

- attachments (optional, from media library)

### 7. Assessment Rubric
Structured grading rubric, organized by Learning Outcome (from section 5), each with one or more Key Indicators, each scored across Proficiency Levels (e.g. Excellent/Good/Average/Poor with score ranges) with a description per level.

- **Rubric Key Indicator** (child relation, per Learning Outcome): `learning_outcome_id`, `code` (e.g. `1.1`), `description` (text), `order`
- **Rubric Proficiency Level** (child relation, per syllabus): `syllabus_id`, `label` (e.g. "Excellent"), `score_min`, `score_max`, `order`
- **Rubric Cell**: `rubric_key_indicator_id`, `rubric_proficiency_level_id`, `description` (text — the criteria text shown in that cell)

Example:

| Learning Outcome | Key Indicator | Excellent (85-100) | Good (75-84) | Average (65-74) | Poor (0-64) |
|---|---|---|---|---|---|
| LO1: the concepts of software, software engineering, and process model in the theories and practical fields. (LOBJ 5.1) | 1.1. Ability to describe the concept of software and software engineering | Concepts are described correctly and completely supported with relevant examples | Concepts are described correctly and completely without the relevant examples | Concepts are described correctly, but incomplete supported with relevant examples | Concepts are described correctly, but incomplete without the relevant examples |
| | 1.2. Ability to describe the concepts of process models | Concepts are described correctly and completely supported with relevant examples | Concepts are described correctly and completely without the relevant examples | Concepts are described correctly but incomplete and supported with relevant examples | Concepts are described correctly but incomplete without the relevant examples |
| LO2: the software engineering practices as the component of the software process model, their characteristics, and how to implement them in real work (LOBJ 5.1) | 2.1 Ability to describe the concepts of software requirement and design | Concepts are described correctly, completely and 85% accurate | Concepts are described correctly, completely and 75% accurate | Concepts are described correctly, but incomplete and 75% accurate | Concepts are described correctly, but incomplete and less than 75% accurate |

- attachments (optional, from media library)

### 8. Teaching & Learning Strategies
Teaching methods/strategies used in the course (lecture, discussion, project-based, etc.).

- `teaching_learning_strategies` (text)
- attachments (optional, from media library)

### 9. Textbooks
List of textbooks/references used in the course.

- `textbooks` (text, or list — title, author, edition)
- attachments (optional, from media library)

### 10. Competency Map
Mapping between course topics/sessions and the competencies/skills targeted.

- `competency_map` (text)
- attachments (optional, from media library)

### 11. Video Overview
Video introducing/overviewing the course.

- `video_overview` (text, optional description)
- attachments (from media library — typically a video file)

## Schema Summary (proposal)

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

One Course has one Syllabus (1-to-1). Each section can have multiple file attachments via `syllabus_materials`, following the same pattern as `session_materials` in [course-restructure-session.md](course-restructure-session.md). The `section` column identifies which section the attachment belongs to.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
