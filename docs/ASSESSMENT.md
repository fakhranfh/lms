# Assessment & Submission State Machine

## Submission State Machine

```
pending ──▶ processing ──▶ graded
                       └──▶ failed ──▶ (retry) ──▶ pending
```

- `pending` — submission created, awaiting grading.
- `processing` — grading job picked it up (AI grading pipeline, Phase 1.4 — not yet wired; see "Known Gaps" below).
- `graded` — terminal, AI score/feedback (and optionally instructor override) available.
- `failed` — terminal, `error_message` populated; can be retried by an instructor via `POST /submissions/{id}/retry`, which resets status to `pending`.

`App\Enums\SubmissionStatus` cases: `Pending`, `Processing`, `Graded`, `Failed`. `isTerminal(): bool` is true for `Graded`/`Failed`. `label(): string` gives the human-readable form used in the UI.

## Rubric JSON Schema

Stored on `assignments.rubric` (JSON column), built via the visual rubric builder folded into `AssignmentForm` (`app/Livewire/Assignments/AssignmentForm.php`). Each item:

```php
[
    'criterion' => 'Clarity of argument',
    'weight' => 40,        // percentage points this item contributes
    'description' => 'How clearly the essay states and defends its thesis.',
    'max_points' => 40,
]
```

Weights across all non-empty items must sum to exactly 100 (`round($totalWeight, 2) === 100.0`); `AssignmentForm::save()` blocks the save with a visible error ("Rubric item weights must total 100 (currently {total}).") otherwise. `Assignment::rubricItems(): array` returns `$this->rubric ?? []` for read-only display (e.g. on `EssaySubmissionForm`).

## Rate Limiting

`POST /submissions` is throttled at the route level:

```php
Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:3,1');
```

3 requests per 1 minute, keyed by the authenticated user (Laravel's default throttle key). A 4th request within the window gets Laravel's default `429 Too Many Requests` response. This only covers the JSON API endpoint (`SubmissionController::store`) — the `EssaySubmissionForm` Livewire component calls `SubmissionService::submit()` directly and is not currently rate-limited (no toast/error-banner library exists in this codebase to surface a 429 from a direct service call).

`SubmissionController::store()` must stay fast (<500ms): it only validates and inserts a `pending` row via `SubmissionService::submit()`. Dispatching the grading job is a `// TODO` pending Phase 1.4.

## Override Behavior

Instructor score always takes precedence over the AI score:

- `Submission::getDisplayScore(): ?float` → `instructor_score` if set, else `ai_score`, else `null`.
- `Submission::getDisplayFeedback(): ?string` → `instructor_feedback` if set, else a formatted `ai_feedback` (`overall_feedback` or `feedback` key, else raw JSON), else `null`.
- `Assignment::getScore(Submission $submission): ?float` mirrors the same precedence for a given assignment.
- `Assignment::isPassing(Submission $submission): bool` → `false` if `passing_score` is null, else `getScore() >= passing_score`.

Overriding a score: `PATCH /submissions/{id}/override`, requires `submissions.override-grade` (enforced by `OverrideScoreFormRequest::authorize()` — `$this->user()?->can('submissions.override-grade') ?? false` — and route middleware). Validation: `instructor_score` required|numeric|min:0|max:100, `instructor_feedback` required|string.

```php
Submission::overrideScore(float $score, string $feedback, User $instructor): void
```

sets `instructor_score`, `instructor_feedback`, `reviewed_by`, `instructor_reviewed_at`. `SubmissionController::override()` calls `SubmissionService::overrideScore($submission->id, $score, $feedback, $user)` and returns the updated submission as JSON.

## Error Handling (Failed Submissions)

- `submissions.error_message` (nullable TEXT) stores the failure detail (e.g. from the Anthropic API, once the grading job exists).
- `submissions.retry_count` (unsigned int, default 0) tracks retry attempts.
- `POST /submissions/{id}/retry` (requires `submissions.grade`): if status is `failed`, resets it to `pending`. Redispatching the grading job itself is a `// TODO` pending Phase 1.4, same as initial dispatch.
- `SubmissionStatusChip` polls every 3 seconds while `pending`/`processing` and shows "Retrying... attempt N/5"-style copy when applicable.

## Known Gaps (Deferred to Phase 1.4 / 1.5)

- No `GradeSubmissionJob` exists yet — submissions never actually transition past `pending` on their own; `processing`/`graded` transitions have no automated trigger to test.
- Audit logging of submission creation, score overrides, and status changes to an `audit_logs` table is deferred to Phase 1.5 (no such table exists yet).
