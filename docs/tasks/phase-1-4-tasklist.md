# Phase 1.4 — AI Integration: Task List

**Goal:** Implement asynchronous Redis-based job queue for Gemini API essay grading, with retry policy and error handling.

**Dependency:** Phase 1.0 (Data Architecture), Phase 1.1 (RBAC), Phase 1.2 (Content Engine), Phase 1.3 (Assessment & State Machine) must be complete. Phase 2.0C (Payment Gateways) for reference (similar pattern).

Reference: [PRD.md](../PRD.md) — Section 8 (Core System Flow: AI Assessment Pipeline), Section 9 (Functional Requirements: Asynchronous Grader).

---

## 1. Environment & Configuration

- [x] Ensure Redis is running locally (or configured via `REDIS_URL`):
  - [x] Test: `memurai-cli PING` → `PONG`
- [x] Update `.env`:
  - [x] `QUEUE_CONNECTION=redis`
  - [x] `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379` (or use `REDIS_URL`)
  - [x] `GEMINI_API_KEY=AIza...` (from .env.example)
  - [x] `GEMINI_MODEL=gemini-flash-latest` (or latest)
  - [x] `GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai` (OpenAI-compatible endpoint)
  - [x] `AI_GRADING_ENABLED=true` (feature flag, default true)
  - [x] `AI_GRADING_MAX_RETRIES=3`
  - [x] `AI_GRADING_TIMEOUT_SECONDS=30`
- [x] Create `.env.example` entries for new vars

## 2. Gemini API Integration

- [x] Create `app/Services/GeminiService.php`:
  - [x] Wrapper around Gemini API (OpenAI-compatible HTTP client, e.g. Laravel `Http` facade)
  - [x] Methods:
    - [x] `gradeEssay(string $essay, array $rubric, string $prompt): array`
      - [x] Build prompt with rubric and essay
      - [x] Call Gemini Chat Completions API (gemini-flash-latest or later)
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
    - [x] Log errors for debugging
  - [x] Timeout: 30 seconds max per request
- [x] Create `app/Dto/GradingRequest` (immutable DTO):
  - [x] Properties: submission_id, assignment_id, student_answer, rubric, max_score, school_id
  - [x] Use for passing grading context to job
- [x] Create `app/Dto/GradingResponse`:
  - [x] Properties: success, score, feedback, error_message, retry_count, timestamp
  - [x] Serializable for storing in database

## 3. Job Setup

- [ ] Generate job: `php artisan make:job GradeSubmissionJob --no-interaction`
  - [ ] Class: `app/Jobs/GradeSubmissionJob`
  - [ ] Implement `Queueable`, `SerializesModels` (if needed for model references)
  - [ ] Constructor: accept `GradingRequest $request` (or pass submission_id + data)
  - [ ] `handle()` method:
    - [ ] Set `CurrentSchool::setTenantId($submission->school_id)` for scoped queries
    - [ ] Fetch Submission, Assignment, verify not already graded
    - [ ] Update Submission: status='processing'
    - [ ] Call `GeminiService->gradeEssay()`
    - [ ] On success:
      - [ ] Update Submission: status='graded', ai_score, ai_feedback, graded_at
      - [ ] Log to audit trail
    - [ ] On failure:
      - [ ] Update Submission: status='failed', error_message, retry_count++
      - [ ] If retry_count < MAX_RETRIES: re-dispatch job with retry delay
      - [ ] Else: mark as failed permanently, log alert
  - [ ] Exception handling:
    - [ ] Catch Gemini API errors and application errors separately
    - [ ] Never let exception crash the job without updating submission status
    - [ ] Log full exception for debugging
- [ ] Configure job middleware in `config/queue.php` or `config/foundation.php` (Laravel 13):
  - [ ] Timeout: 45 seconds (5s buffer + 30s API call)
  - [ ] Retry: 3 attempts, exponential backoff (1s, 5s, 15s delays)
  - [ ] Timeout behavior: if job exceeds 45s, kill it and retry
- [ ] Create job tests (see Testing section)

## 4. Queue Configuration

- [ ] Update `config/queue.php`:
  - [ ] Default connection: redis
  - [ ] Redis cluster/connections: default localhost:6379
  - [ ] Job timeout: 45 seconds
- [ ] Update `config/horizon.php` (for monitoring):
  - [ ] Enable Horizon in `config/horizon.php`
  - [ ] Dashboard path: `/horizon` (Super Admin only)
  - [ ] Set balance strategy: `simple` or `auto` (default fine)
  - [ ] Retention: 24 hours
- [ ] Update `app/Providers/HorizonServiceProvider.php`:
  - [ ] Gate: only Super Admin can view Horizon
  - [ ] `Gate::define('viewHorizon', fn ($user) => $user->hasRole('admin'))`

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

- [ ] Create `GradeSubmissionJobTest` (feature/unit):
  - [ ] Mock Gemini API responses
  - [ ] Test successful grading flow:
    - [ ] Submission status: pending → processing → graded
    - [ ] ai_score and ai_feedback populated
    - [ ] graded_at timestamp set
  - [ ] Test API error handling:
    - [ ] API returns 500 → job retries
    - [ ] API timeout → job retries
    - [ ] After max retries → status=failed, error_message logged
  - [ ] Test JSON parsing:
    - [ ] Valid JSON response → parsed correctly
    - [ ] Invalid JSON → graceful error, retry
  - [ ] Test tenant scoping:
    - [ ] Job uses CurrentSchool correctly
    - [ ] No cross-tenant data leakage
  - [ ] Test idempotency:
    - [ ] Job can be retried safely (no duplicate updates)
- [ ] Create `GeminiServiceTest` (unit):
  - [ ] Mock HTTP client responses
  - [ ] Test gradeEssay() with valid rubric
  - [ ] Test parseGradingResponse() with various JSON structures
  - [ ] Test error responses (401, 429, 500)
  - [ ] Test timeout handling
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
  - [ ] Gemini API key setup
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
- In GeminiService, validate parsed JSON response
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

