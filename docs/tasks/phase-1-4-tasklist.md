# Phase 1.4 — AI Integration: Task List

**Goal:** Implement asynchronous Redis-based job queue for AI essay grading (Gemini primary, DeepSeek secondary), with retry policy and error handling.

**Dependency:** Phase 1.0 (Data Architecture), Phase 1.1 (RBAC), Phase 1.2 (Content Engine), Phase 1.3 (Assessment & State Machine) must be complete. Phase 2.0C (Payment Gateways) for reference (similar pattern).

Reference: [PRD.md](../PRD.md) — Section 8 (Core System Flow: AI Assessment Pipeline), Section 9 (Functional Requirements: Asynchronous Grader).

---

## 1. Environment & Configuration

- [x] Ensure Redis is running locally (or configured via `REDIS_URL`):
  - [x] Test: `memurai-cli PING` → `PONG`
- [x] Update `.env`:
  - [x] `QUEUE_CONNECTION=redis`
  - [x] `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379` (or use `REDIS_URL`)
  - [x] `AI_GRADING_PROVIDER=gemini` (or `deepseek` — selects the active provider)
  - [x] `GEMINI_API_KEY=AIza...` (from .env.example)
  - [x] `GEMINI_MODEL=gemini-flash-latest` (or latest; `gemini-2.5-flash` returns 404 for new API keys)
  - [x] `GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai` (OpenAI-compatible endpoint)
  - [x] `DEEPSEEK_API_KEY=sk-...` (secondary provider, from .env.example)
  - [x] `DEEPSEEK_MODEL=deepseek-chat`
  - [x] `DEEPSEEK_BASE_URL=https://api.deepseek.com`
  - [x] `AI_GRADING_ENABLED=true` (feature flag, default true)
  - [x] `AI_GRADING_MAX_RETRIES=3`
  - [x] `AI_GRADING_TIMEOUT_SECONDS=30`
- [x] Create `.env.example` entries for new vars

## 2. AI Provider Integration (Gemini + DeepSeek)

- [x] Create `app/Contracts/AiGradingProvider.php` (interface):
  - [x] `gradeEssay(string $essay, array $rubric, string $prompt): array`
  - [x] `buildGradingPrompt(Assignment $assignment, string $essay): string`
  - [x] `parseGradingResponse(string $responseText): array`
  - [x] Lets calling code (job, tests) depend on the contract instead of a concrete provider class
- [x] Create `app/Services/AiGrading/AbstractOpenAiCompatibleProvider.php` (abstract base):
  - [x] Shared logic for providers exposing an OpenAI-compatible chat completions endpoint (Gemini, DeepSeek)
  - [x] `gradeEssay()`:
    - [x] Build prompt with rubric and essay
    - [x] Call `{base_url}/chat/completions` via Laravel `Http` facade
    - [x] Parse response JSON
    - [x] Return structured result: `{ success: bool, score: float, feedback: array, raw_response?: string, error?: string }`
  - [x] `buildGradingPrompt(Assignment $assignment, string $essay): string`
    - [x] System prompt: "You are an expert essay grader..."
    - [x] Include rubric criteria
    - [x] Include essay to grade
    - [x] Request JSON output format
  - [x] `parseGradingResponse(string $responseText): array`
    - [x] Extract JSON from response (handle markdown code blocks)
    - [x] Validate structure: `{ score: float, feedback: { item: string, points: int } }`
    - [x] Return parsed data or throw exception
  - [x] Error handling:
    - [x] Catch HTTP exceptions (401, 429, 500, timeout)
    - [x] Return structured error response
    - [x] Log errors for debugging (provider name included in log message)
  - [x] Timeout: 30 seconds max per request
- [x] Create `app/Services/GeminiService.php` — extends the abstract provider, config from `services.gemini.*`
- [x] Create `app/Services/DeepSeekService.php` — extends the abstract provider, config from `services.deepseek.*`
- [x] Create `app/Services/AiGradingProviderFactory.php`:
  - [x] `make(?string $provider = null): AiGradingProvider` — resolves `gemini`/`deepseek`, defaults to `config('services.ai_grading.provider')`
  - [x] Throws `InvalidArgumentException` for unknown provider names
- [x] Bind `AiGradingProvider::class` in `AppServiceProvider` to the factory-resolved active provider (so `app(AiGradingProvider::class)` always returns the currently configured one)
- [x] Create `app/Dto/GradingRequest` (immutable DTO):
  - [x] Properties: submission_id, assignment_id, student_answer, rubric, max_score, school_id
  - [x] Use for passing grading context to job
- [x] Create `app/Dto/GradingResponse`:
  - [x] Properties: success, score, feedback, error_message, retry_count, timestamp
  - [x] Serializable for storing in database

## 3. Job Setup

- [x] Generate job: `php artisan make:job GradeSubmissionJob --no-interaction`
  - [x] Class: `app/Jobs/GradeSubmissionJob`
  - [x] Implement `Queueable`; constructor accepts `string $submissionId` (re-fetches fresh model in `handle()`, avoiding stale serialized state across retries)
  - [x] Constructor: accepts `submissionId` (simpler than a full `GradingRequest` DTO — job re-fetches Submission/Assignment fresh each attempt)
  - [x] `handle()` method:
    - [x] Set `CurrentSchool::setSchoolId(...)` for scoped queries (note: tasklist referenced `setTenantId`, but the actual method on `App\Support\CurrentSchool` is `setSchoolId`; school id is resolved via `submission->assignment->lesson->module->course->school_id` since Submission/Assignment have no direct `school_id` column)
    - [x] Fetch Submission with eager-loaded `assignment.lesson.module.course`; skip if missing or already terminal (graded/failed) — idempotent
    - [x] Update Submission: status='processing'
    - [x] Call `app(AiGradingProvider::class)->gradeEssay()` (resolves to the configured provider — Gemini or DeepSeek)
    - [x] On success:
      - [x] Update Submission: status='graded', ai_score, ai_feedback, graded_at
      - [x] Log via `Log::info` (no dedicated audit trail infrastructure exists in this codebase yet)
    - [x] On failure:
      - [x] Update Submission: error_message, retry_count++, status back to 'pending' for retry visibility
      - [x] Rethrow exception — Laravel's native `$tries`/`backoff()` queue retry mechanism re-dispatches automatically (idiomatic Laravel 13, avoids manual re-dispatch bookkeeping)
      - [x] After max tries: `failed()` marks Submission status='failed' (final), logs critical alert
  - [x] Exception handling:
    - [x] Provider-level HTTP/parsing errors are already caught and returned as structured `{success: false, error}` by `AbstractOpenAiCompatibleProvider`; job throws `RuntimeException` on that structured failure so the queue's retry/backoff applies uniformly
    - [x] Job's own catch block never lets an exception crash silently — always updates submission status/error_message first, then rethrows
    - [x] Log full exception message for debugging (`Log::error` per attempt, `Log::critical` on final failure)
- [x] Configure job middleware via job properties (simpler than `config/queue.php`/`config/foundation.php` since these are job-specific, not global):
  - [x] `public $timeout = 45;`
  - [x] `public $tries = 3;` + `backoff(): array { return [1, 5, 15]; }` — exponential backoff delays
  - [x] Timeout behavior: default Laravel queue worker behavior kills/retries jobs exceeding `$timeout`
- [x] Create job tests (see Testing section) — `tests/Feature/GradeSubmissionJobTest.php`, 6 tests: successful grading, tenant school id resolution, provider failure → retry_count/error_message, `failed()` → permanent failure, idempotency on already-graded submissions, graceful no-op on missing submission

## 4. Queue Configuration

- [x] Update `config/queue.php`:
  - [x] Default connection: redis — already env-driven (`QUEUE_CONNECTION=redis` in `.env`), `config/queue.php` needed no code change
  - [x] Redis cluster/connections: default localhost:6379 — already set via `REDIS_HOST`/`REDIS_PORT` in `.env`
  - [x] Job timeout: 45 seconds — already set per-job on `GradeSubmissionJob::$timeout` (Section 3), not globally in `config/queue.php`
- [ ] ~~Update `config/horizon.php` (for monitoring)~~ — **Blocked, skipped by decision:** `laravel/horizon` requires the `ext-pcntl`/`ext-posix` PHP extensions, which do not exist on Windows. `composer require laravel/horizon` fails to resolve on this dev machine. Revisit if/when development or deployment moves to Linux/WSL; until then, queue monitoring should use `php artisan queue:failed`, `php artisan queue:work` console output, or a custom `grading:monitor` command (Section 8) instead.
- [ ] ~~Update `app/Providers/HorizonServiceProvider.php`~~ — skipped, depends on Horizon above.

## 5. Prompting & Structured Output

- [ ] Create `app/Prompts/EssayGradingPrompt.php` (or inline in GeminiService):
  - [ ] System message: "You are an expert essay grader. Evaluate essays based on provided rubrics..."
  - [ ] User message template with placeholders for rubric, essay, max_score
  - [ ] Output format instruction (JSON schema):
    ```json
    {
      "score": 85.5,
      "feedback": [
        {
          "rubric_item": "Clarity",
          "points_earned": 18,
          "points_max": 20,
          "comment": "Essay is well-structured and easy to follow..."
        }
      ],
      "summary": "Overall strong essay...",
      "suggestions": ["Consider adding more examples", "...]
    }
    ```
  - [ ] Ensure prompt is deterministic (not random) for testing
- [ ] Example prompt structure:
  ```
  System: You are an expert essay grader...
  
  User: 
  Please grade the following essay based on this rubric:
  
  Rubric:
  - Clarity (20 points): Is the essay clear and well-organized?
  - Evidence (30 points): Does the essay provide strong evidence?
  - Argument (30 points): Is the main argument coherent and compelling?
  - Grammar (20 points): Are there grammatical errors?
  
  Essay:
  [STUDENT_ESSAY]
  
  Respond in JSON format: {score, feedback: [{rubric_item, points_earned, points_max, comment}], summary, suggestions}
  ```

## 6. Submission Controller Integration

- [ ] Ensure SubmissionController@store (Phase 1.3) is updated to:
  - [ ] After creating Submission with status='pending':
    - [ ] Dispatch `GradeSubmissionJob` onto redis queue
    - [ ] Use: `GradeSubmissionJob::dispatch($submission->id, $assignment->id, $rubric, $essay, $school_id)`
    - [ ] Or: create GradingRequest DTO and dispatch
  - [ ] Return HTTP 200 within 500ms (job runs async)
  - [ ] Example:
    ```php
    $submission = Submission::create([
      'assignment_id' => $assignment->id,
      'user_id' => $user->id,
      'student_answer' => $request->student_answer,
      'status' => SubmissionStatus::PENDING,
      'submitted_at' => now(),
    ]);

    GradeSubmissionJob::dispatch($submission);

    return response()->json($submission->toArray(), 201);
    ```

## 7. Error Handling & Resilience

- [ ] Create `FailedJobHandler`:
  - [ ] Track permanently failed submissions (retry_count >= MAX_RETRIES)
  - [ ] Send alert/notification to school admin (future integration)
  - [ ] Update Submission: status='failed' (final, no more retries)
- [ ] Implement exponential backoff:
  - [ ] Retry 1: 1 second delay
  - [ ] Retry 2: 5 seconds delay
  - [ ] Retry 3: 15 seconds delay
  - [ ] After: mark as failed, alert
  - [ ] Use Laravel's retry mechanism: `->delay(exponential delay)` in queue config
- [ ] Create healthcheck for grading queue:
  - [ ] Monitor Redis connection health
  - [ ] Alert if queue depth exceeds threshold (e.g., >1000 jobs)
  - [ ] Integrate with Horizon dashboard (visual monitoring)

## 8. Monitoring & Observability

- [ ] Create `GradingQueueMonitor` command:
  - [ ] `php artisan grading:monitor` — show queue stats
  - [ ] Display: pending submissions, processing count, failed count, avg grade time
  - [ ] Run on cron for alerts (future)
- [ ] Log all grading events:
  - [ ] Submission received, status=pending
  - [ ] Job started, status=processing
  - [ ] Job succeeded, status=graded (with score)
  - [ ] Job failed, status=failed (with error details)
  - [ ] Job retry attempted (with attempt #)
- [ ] Integrate with Horizon:
  - [ ] Dashboard shows queue depth, throughput, failures
  - [ ] Inspect individual job payloads and results
  - [ ] Retry failed jobs manually from dashboard

## 9. Testing

- [x] Create `GradeSubmissionJobTest` (feature) — `tests/Feature/GradeSubmissionJobTest.php`, 6 tests, mocks `AiGradingProvider` contract directly (provider-level JSON parsing/HTTP error mocking already covered by `GeminiServiceTest`/`DeepSeekServiceTest`):
  - [x] Test successful grading flow:
    - [x] Submission status → graded
    - [x] ai_score and ai_feedback populated
    - [x] graded_at timestamp set
  - [x] Test provider error handling:
    - [x] Provider returns `{success: false, error}` → job throws, retry_count incremented, error_message recorded
    - [x] After max retries → `failed()` sets status=failed, error_message logged
  - [x] Test tenant scoping:
    - [x] Job resolves and sets `CurrentSchool` school id from submission's assignment→lesson→module→course chain
  - [x] Test idempotency:
    - [x] Already-graded submission is skipped, no re-grading, no duplicate updates
    - [x] Missing submission id → graceful no-op
- [x] Create `GeminiServiceTest` (unit) (`tests/Unit/Services/GeminiServiceTest.php`, 7 tests):
  - [x] Mock HTTP client responses
  - [x] Test gradeEssay() with valid rubric
  - [x] Test parseGradingResponse() with various JSON structures (plain + markdown-wrapped)
  - [x] Test error responses (401, 500, malformed JSON)
  - [ ] Test timeout handling (deferred: not explicitly asserted with a simulated timeout)
- [x] Create `DeepSeekServiceTest` (unit) (`tests/Unit/Services/DeepSeekServiceTest.php`, 7 tests, mirrors GeminiServiceTest)
- [x] Create `AiGradingProviderFactoryTest` (unit) (`tests/Unit/Services/AiGradingProviderFactoryTest.php`, 4 tests):
  - [x] Defaults to Gemini from config
  - [x] Resolves DeepSeek from config
  - [x] Explicit argument overrides config
  - [x] Throws for unknown provider
- [ ] Create `SubmissionControllerTest` (feature):
  - [ ] Student POSTs essay to /submissions
  - [ ] Job is dispatched to queue
  - [ ] Response contains submission with status='pending'
  - [ ] Response time < 500ms (verify with performance assertion)
  - [ ] After job completes, submission status='graded'
- [ ] Create `PromptInjectionTest` (security):
  - [ ] Rubric with malicious JSON → gracefully rejected
  - [ ] Student answer with prompt injection → safely sanitized
  - [ ] No prompt leakage in feedback to student
- [ ] Create `QueueHealthTest`:
  - [ ] Verify Redis connection
  - [ ] Verify job can be pushed and popped from queue
  - [ ] Verify Horizon can track job execution
- [ ] Run: `php artisan test --compact --filter GradeSubmission`

## 10. Documentation & Verification

- [ ] Create docs/AI_GRADING.md:
  - [ ] Gemini + DeepSeek API key setup, and how to switch providers via `AI_GRADING_PROVIDER`
  - [ ] Grading prompt design and examples
  - [ ] Rubric JSON schema documentation
  - [ ] Error scenarios and retry behavior
  - [ ] How to troubleshoot failed submissions
  - [ ] Monitoring via Horizon dashboard
  - [ ] Cost estimation (tokens per submission)
- [ ] Create docs/PROMPTING.md:
  - [ ] Best practices for rubric design
  - [ ] Examples of effective vs poor rubrics
  - [ ] How to structure grading feedback
  - [ ] Prompt injection mitigation
- [ ] Update .env.example with all AI_* variables
- [ ] Verify locally:
  - [ ] Start Redis: `redis-cli ping`
  - [ ] Start queue worker: `php artisan queue:work --timeout=45`
  - [ ] Create assignment, submit essay
  - [ ] Check Horizon: `/horizon` (should see job processing)
  - [ ] Verify submission status transitions: pending → processing → graded
- [ ] Run `vendor/bin/pint --dirty --format agent`

---

## Resolved Decisions

### AI Provider Abstraction
**Decision:** `AiGradingProvider` interface + `AbstractOpenAiCompatibleProvider` shared base, with `GeminiService`/`DeepSeekService` as thin config-only subclasses, selected at runtime by `AiGradingProviderFactory` via `AI_GRADING_PROVIDER`.

**Rationale:**
- Gemini and DeepSeek both expose an OpenAI-compatible chat completions endpoint — request shape, JSON extraction (incl. markdown-fenced), error handling, and system prompt are identical; only `base_url`/`api_key`/`model` differ
- Interface lets calling code (job, tests) depend on the contract, not a concrete provider — swapping providers is a config change, not a code change
- Avoids duplicating ~150 lines of HTTP/parsing logic per provider (DRY); a fix or prompt change happens in one place
- Matches this codebase's existing `PaymentGateway` contract + `PaymentGatewayFactory` pattern (Phase 2.0C)

**Implementation:**
- `app/Contracts/AiGradingProvider.php`
- `app/Services/AiGrading/AbstractOpenAiCompatibleProvider.php`
- `app/Services/GeminiService.php`, `app/Services/DeepSeekService.php`
- `app/Services/AiGradingProviderFactory.php`
- Bound in `AppServiceProvider`: `AiGradingProvider::class` → factory-resolved active provider
- `config('services.ai_grading.provider')` / `AI_GRADING_PROVIDER` env var (default `gemini`)

### Queue Technology
**Decision:** Redis + Laravel Queue (vs database, vs other).

**Rationale:**
- Fast, reliable, widely-used in Laravel ecosystem
- Built-in retry support with exponential backoff
- Horizon provides beautiful dashboard for monitoring
- Simple horizontal scaling with multiple workers
- Atomic operations prevent race conditions

**Implementation:**
- `QUEUE_CONNECTION=redis` in .env
- Job timeout: 45 seconds (30s API + buffer)
- Retry: 3 attempts with exponential backoff
- Workers: `php artisan queue:work --timeout=45`

### Prompt Injection Mitigation
**Decision:** Never construct prompts with unvalidated user input; always sanitize rubric JSON.

**Rationale:**
- Rubric and essay are user-controlled inputs
- Malicious rubric could override grading behavior
- JSON parsing failure is graceful (retry job)
- Validate rubric structure before sending to API

**Implementation:**
- Validate rubric JSON schema in AssignmentFormRequest
- Never interpolate essay directly into prompt string; use structured format (e.g., XML tags)
- In AbstractOpenAiCompatibleProvider (shared by GeminiService/DeepSeekService), validate parsed JSON response
- Log suspicious inputs for security review

### Error Response Structure
**Decision:** Return structured error response with status_code, error message, retry details.

**Rationale:**
- Consistent error handling across components
- Enables smart retry logic (some errors retry, others don't)
- Clear logging and debugging information

**Implementation:**
- GradingResponse DTO with success flag, error fields
- On API error: `{ success: false, error_message: "Gemini API timeout", retry_count: 1 }`
- Store error in Submission.error_message for user visibility
- Log full exception separately for internal debugging

### Retry Strategy
**Decision:** Exponential backoff with max 3 retries; after that, mark as failed (final).

**Rationale:**
- Transient API errors (429, 503) benefit from retry
- Exponential backoff prevents overwhelming API
- 3 retries = up to ~30 seconds total wait (1s + 5s + 15s delays)
- After that: likely permanent error, human intervention needed

**Implementation:**
- Job payload includes retry_count (initial 0)
- On catch, increment retry_count
- If < MAX_RETRIES: re-dispatch with delay
- Else: update Submission status='failed', send alert

