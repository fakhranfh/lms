# AI Grading

Asynchronous essay grading pipeline: submissions are queued to Redis and graded by Gemini (or DeepSeek) via an OpenAI-compatible chat completions API.

## Setup

1. Get a Gemini API key from [Google AI Studio](https://aistudio.google.com/) and set:
   ```
   GEMINI_API_KEY=AIza...
   GEMINI_MODEL=gemini-flash-latest
   GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai
   ```
2. (Optional, secondary provider) Get a DeepSeek API key and set:
   ```
   DEEPSEEK_API_KEY=sk-...
   DEEPSEEK_MODEL=deepseek-chat
   DEEPSEEK_BASE_URL=https://api.deepseek.com
   ```
3. Select the active provider with `AI_GRADING_PROVIDER=gemini` (or `deepseek`). Switching providers is a one-line `.env` change — no code change — because both providers implement `App\Contracts\AiGradingProvider` and are resolved by `App\Services\AiGradingProviderFactory` from this config value.
4. Ensure Redis is running and `QUEUE_CONNECTION=redis` is set.

## Grading prompt

Both providers share prompt construction in `App\Services\AiGrading\AbstractOpenAiCompatibleProvider`. The rubric, assignment prompt, and student essay are wrapped in `<rubric>`, `<assignment_prompt>`, and `<essay>` XML tags and sent with `temperature: 0` (deterministic output) and `response_format: json_object`. The system prompt explicitly instructs the model to treat `<essay>` content as data, never as instructions — see [PROMPTING.md](PROMPTING.md) for the full rationale.

## Rubric JSON schema

An `Assignment.rubric` column stores an array of rubric items:

```json
[
  { "item": "Clarity", "points": 20 },
  { "item": "Evidence", "points": 30 },
  { "item": "Argument", "points": 30 },
  { "item": "Grammar", "points": 20 }
]
```

The model is asked to respond with:

```json
{
  "score": 85.5,
  "feedback": [
    { "rubric_item": "Clarity", "points_earned": 18, "points_max": 20, "comment": "..." }
  ],
  "summary": "...",
  "suggestions": ["..."]
}
```

`AbstractOpenAiCompatibleProvider::parseGradingResponse()` requires `score` and `feedback` to be present (extracting JSON from markdown code fences if needed) and throws a `RuntimeException` otherwise, which the job treats as a provider failure.

## Error scenarios and retry behavior

- `GradeSubmissionJob` has `$tries = 3` and `backoff() => [1, 5, 15]` (seconds). A provider failure (`{success: false, error}`) causes the job to throw, incrementing `Submission.retry_count` and recording `error_message`; the submission status returns to `pending` so it's visible as retryable.
- After the 3rd failed attempt, Laravel calls `GradeSubmissionJob::failed()`, which delegates to `App\Services\FailedJobHandler`: sets `Submission.status = failed` (final) and logs a `Log::critical` alert (no notification channel exists yet in this codebase — swap in a real `Notification` once one is added).
- The job re-fetches the `Submission` fresh from the database on every attempt (only the submission id is serialized in the job payload), so retries never operate on stale state.
- The job is idempotent: if a submission is already in a terminal status (`graded` or `failed`) when the job runs, it's a no-op.

## Troubleshooting failed submissions

- `php artisan grading:monitor` — shows counts of pending/processing/graded/failed submissions and average grading time.
- Check `Submission.error_message` for the last recorded provider error.
- Check application logs (`storage/logs`) for `Log::error`/`Log::critical` entries tagged with the provider name.
- To retry a failed submission, use the `POST /submissions/{submission}/retry` endpoint (requires `submissions.grade` permission) — it resets status to `pending` and redispatches `GradeSubmissionJob`.

## Monitoring

Horizon is **not installed** on this project (it requires `ext-pcntl`/`ext-posix`, unavailable on Windows dev machines). Until development or deployment moves to Linux/WSL, use instead:

- `php artisan grading:monitor` — queue/submission stats snapshot.
- `App\Services\GradingQueueHealthService` — checks Redis connectivity and warns if queue depth exceeds 1000 (not currently wired to a console command or scheduler; call it directly or add a command if automated alerting is needed).
- `php artisan queue:work --timeout=45` console output, and `php artisan queue:failed` for jobs that exhausted retries.

## Cost estimation

Token usage is dominated by the essay length and rubric size, sent once per submission attempt (up to 3 times on transient failures). Check current Gemini/DeepSeek pricing pages for per-token cost, and multiply by the number of graded submissions per billing period. There is no token-usage logging in this codebase today — add one to `AbstractOpenAiCompatibleProvider::gradeEssay()` if precise cost tracking is needed.
