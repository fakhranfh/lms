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

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet. Actual client-side detection mechanisms (browser lock-down, webcam access, network monitoring) will require dedicated technical research before implementation.
