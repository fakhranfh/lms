# LMS

A multi-tenant Learning Management System for schools — launch a school's own online learning platform in minutes, with course delivery, discussion, assessment, exam proctoring, and grading wired up from day one.

---

## Core Features

### Multi-School, Role-Based Access

Each school runs as an isolated tenant — data is scoped per school with no cross-tenant leakage. Access is role-based: **Admin**, **Instructor**, and **Student** roles each get a scoped view of the platform, with permissions enforced consistently across the app.

### Course Builder

Structure a course into sessions, each with a title, learning outcome, subtopics, scheduled start/end window, and delivery mode (virtual class or in-person). Attach lesson materials — video, PDFs, audio, images, presentations, and interactive content — then publish when ready.

### Discussion Forums

Per-session discussion threads let students ask questions and instructors respond. Threads support replies, likes, and unread tracking, so conversation stays organized around the material it relates to.

### Assessments

Quizzes, assignments, and final exams are grouped by weighted category (e.g. Quiz 15%, Assignment 25%, Final Exam 30%, Attendance 20%, Forum Discussion 10%). Multiple-choice and essay questions are supported, with due dates and submission status tracked per student.

### Exam Proctoring

Timed exams run through a pre-flight check (connection speed, camera, and screen-share verification) before starting. Live sessions capture snapshots and integrity events during the exam, which instructors can review after the fact for anything flagged.

### Gradebook & Report Cards

Scores from assessments, attendance, and forum participation roll up automatically into a per-student gradebook, broken down by weighted category. Report cards aggregate results across all of a student's courses into a final score and letter grade.

### Authentication

Full authentication flow powered by **Laravel Fortify**: registration with password strength validation, login, email verification, forgot/reset password, and logout.

---
