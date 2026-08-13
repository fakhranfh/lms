# Course Restructure — Proctor

## Definition

Proctoring system used to detect cheating during **Open Book** and **Closed Book** Final Exam attempts (see [course-restructure-assessment-final-exam.md](course-restructure-assessment-final-exam.md)). Monitors a student's exam attempt and flags suspicious behavior for instructor review.

## Structure

### 1. Proctor Session
One per exam attempt. Tracks the monitoring lifecycle for that attempt.

- `assessment_attempt_id` (FK -> assessment attempt, see [course-restructure-assessment.md](course-restructure-assessment.md))
- `status` (enum: `active`, `completed`, `terminated` — `terminated` when the session is force-ended due to severe violation)
- `started_at` (datetime)
- `ended_at` (datetime, nullable)
- `risk_score` (integer, nullable — aggregated score derived from flagged events, higher = more suspicious)

### 2. Proctor Event
Individual detection events logged during a session.

- `proctor_session_id`
- `event_type` (enum: `tab_switch`, `window_blur`, `multiple_faces`, `no_face_detected`, `face_mismatch`, `copy_paste`, `right_click`, `devtools_opened`, `fullscreen_exit`, `network_activity_detected`, `unauthorized_app_detected`)
- `severity` (enum: `low`, `medium`, `high`)
- `detected_at` (datetime)
- `metadata` (JSON, nullable — e.g. snapshot URL, app name, detection confidence)

### 3. Proctor Snapshot (optional evidence capture)
Periodic or event-triggered webcam/screen captures kept as evidence.

- `proctor_session_id`
- `type` (enum: `webcam`, `screen`)
- `captured_at` (datetime)
- `file` (attachment — stored image, e.g. via media library storage)
- `triggered_by_event_id` (nullable, FK -> proctor event)

## Detection Rules by Exam Type

- **Open Book** (`allow_local_files = true`, `allow_internet = false`): flags internet/network activity and unauthorized apps, but does not flag opening local files.
- **Closed Book** (`allow_local_files = false`, `allow_internet = false`): flags internet/network activity, unauthorized apps, and any file-open activity.

## Review & Action

Instructors review flagged sessions before/while grading the exam attempt.

- `reviewed_by` (user_id, nullable)
- `reviewed_at` (datetime, nullable)
- `review_decision` (enum: `no_action`, `warning`, `disqualified`, nullable)
- `review_notes` (text, nullable)

A `disqualified` decision should be reflected back on the related `assessment_scores` (see [course-restructure-assessment.md](course-restructure-assessment.md)), e.g. forcing a score of 0 or flagging it for manual override.

## Schema Summary (proposal)

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

## Exam Entry Flow (Open Book / Closed Book only)

Unlike a regular Quiz (taken inline in a modal on the lesson/course page), starting a Final Exam of type **Open Book** or **Closed Book** routes the student to a **dedicated full-page route** (not a modal). Standard/no-proctor Final Exams keep the existing quiz-style modal flow.

Sequence when the student clicks "Start Exam" on an Open/Closed Book Final Exam:

1. **Pre-flight checks page** — before the attempt/proctor session is created:
   1. **Internet speed check** — quick download/upload probe, must meet a minimum threshold to proceed.
   2. **Camera check** — request webcam permission, show live preview, confirm a face is visible.
   3. **Screen capture check** — request screen-share permission (`getDisplayMedia`), confirm capture is active.
   - Each check shows pass/fail status; student cannot proceed to the exam until all three pass.
2. **Exam page (proctored)** — on confirming all checks pass:
   - Create the `assessment_attempt` and `proctor_session` (`status = active`, `started_at = now()`).
   - Start webcam recording, screen recording, and periodic snapshot capture (per [Proctor Snapshot](#3-proctor-snapshot-optional-evidence-capture)) client-side.
   - Question/answer UI reuses the same components as the Quiz modal (question list, navigation, timer, autosave), just rendered as its own full page (own route) instead of inside a modal.
   - Client-side detectors run in the background and log `proctor_events` per the [Detection Rules by Exam Type](#detection-rules-by-exam-type).
3. **Submission / end of session**:
   - On submit (manual or auto via timer expiry), stop webcam/screen recording.
   - Upload the recorded webcam + screen video to R2 (reuse existing R2 storage integration, see [Phase 1.2 Section 11.4](phase-1-2-section-11-4-service-layer.md) service layer), attach as `proctor_snapshots` (or a session-level recording reference) linked to the `proctor_session`.
   - Mark `proctor_session.status = completed`, `ended_at = now()`.
   - Redirect student back to the course/exam results context (same as regular quiz submission flow).

Open questions to resolve before implementation: exact route naming/URL for the proctor exam page, whether the full video recording is a new field/table vs. reusing `proctor_snapshots` with a `recording` type, minimum internet speed threshold, and fallback UX when a check fails (retry vs. block with instructor contact instructions).

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet. Actual client-side detection mechanisms (browser lock-down, webcam access, network monitoring) will require dedicated technical research before implementation.
