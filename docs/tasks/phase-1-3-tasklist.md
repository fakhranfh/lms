# Phase 1.3 — Assessment & State Machine: Task List

**Goal:** Implement Assignment and Submission schema with dynamic JSON rubrics, submission state machine (pending → processing → graded/failed), and instructor override capabilities.

**Dependency:** Phase 1.0 (Data Architecture), Phase 1.1 (RBAC), Phase 1.2 (Content Engine) must be complete.

Reference: [PRD.md](../PRD.md) — Section 5 (US1, US3, US4), Section 8 (Core System Flow: AI Assessment Pipeline), Section 16 (Acceptance Criteria US1, US3, US4).

---

## 1. Database Schema — Assignments & Submissions

- [ ] Create migration: `php artisan make:migration create_assignments_submissions_tables --no-interaction`
  - [ ] `assignments` table:
    - [ ] `id` (UUID, PK)
    - [ ] `lesson_id` (UUID, FK → lessons.id, CASCADE)
    - [ ] `title` (VARCHAR 255, not null)
    - [ ] `prompt_question` (LONGTEXT, not null) — the question/prompt for students
    - [ ] `rubric` (JSON/JSONB, nullable) — dynamic grading criteria structure
    - [ ] `max_score` (DECIMAL 5,2, default 100.00) — typically 0-100
    - [ ] `passing_score` (DECIMAL 5,2, nullable) — optional threshold
    - [ ] `is_published` (BOOLEAN, default false)
    - [ ] `allow_multiple_submissions` (BOOLEAN, default false) — one vs multiple attempts
    - [ ] timestamps
  - [ ] `submissions` table:
    - [ ] `id` (UUID, PK)
    - [ ] `assignment_id` (UUID, FK → assignments.id, CASCADE)
    - [ ] `user_id` (UUID, FK → users.id, CASCADE)
    - [ ] `student_answer` (LONGTEXT, not null) — raw essay text
    - [ ] `status` (ENUM: 'pending', 'processing', 'graded', 'failed', default 'pending')
    - [ ] `ai_score` (DECIMAL 5,2, nullable) — AI-generated score
    - [ ] `ai_feedback` (JSON/JSONB, nullable) — structured AI response (e.g., {overall_score, feedback_per_rubric_item, suggestions})
    - [ ] `instructor_score` (DECIMAL 5,2, nullable) — manual override score
    - [ ] `instructor_feedback` (LONGTEXT, nullable) — manual override feedback
    - [ ] `instructor_reviewed_at` (TIMESTAMP, nullable) — when instructor graded/overrode
    - [ ] `reviewed_by` (UUID, FK → users.id, nullable, SET NULL) — which instructor reviewed
    - [ ] `submitted_at` (TIMESTAMP, not null, default NOW) — submission timestamp
    - [ ] `graded_at` (TIMESTAMP, nullable) — when AI/instructor finished grading
    - [ ] `retry_count` (UNSIGNED INT, default 0) — how many times job has retried
    - [ ] `error_message` (TEXT, nullable) — if status='failed', error details (from Anthropic API)
    - [ ] timestamps
    - [ ] **Unique constraint:** `(assignment_id, user_id)` if `allow_multiple_submissions = false`
    - [ ] **Index:** `(assignment_id, status)` for filtering by status
    - [ ] **Index:** `(user_id, status)` for student dashboard queries
- [ ] Apply `HasUuid` trait to `Assignment` and `Submission` models

## 2. Models & Relationships

- [ ] Generate `Assignment` model:
  - [ ] Apply `HasUuid` trait
  - [ ] Add `$fillable` (`lesson_id`, `title`, `prompt_question`, `rubric`, `max_score`, `passing_score`, `is_published`, `allow_multiple_submissions`)
  - [ ] Add `$casts` for JSON: `rubric` as array/collection
  - [ ] Define relationships:
    - [ ] `belongsTo(Lesson::class)` — parent lesson
    - [ ] `hasMany(Submission::class)` — student submissions
  - [ ] Add method: `getScore(Submission $submission): ?float` — returns instructor_score if set, else ai_score
  - [ ] Add method: `isPassing(Submission $submission): bool` — check if score >= passing_score
  - [ ] Add accessor: `rubricItems(): array` — parse rubric structure (e.g., [['key' => 'clarity', 'weight' => 0.5], ...])
- [ ] Generate `Submission` model:
  - [ ] Apply `HasUuid` trait
  - [ ] Add `$fillable` (`assignment_id`, `user_id`, `student_answer`, `status`, `ai_score`, `ai_feedback`, `instructor_score`, `instructor_feedback`, `submitted_at`)
  - [ ] Add `$casts` for JSON: `ai_feedback` as array/collection
  - [ ] Add `$casts` for ENUM: `status` as SubmissionStatus enum (see Phase 2.1.1 for enum pattern)
  - [ ] Define relationships:
    - [ ] `belongsTo(Assignment::class)` — parent assignment
    - [ ] `belongsTo(User::class)` — student who submitted
    - [ ] `belongsTo(User::class, 'reviewed_by')` — instructor who graded
  - [ ] Add method: `isPending(): bool` — status === 'pending'
  - [ ] Add method: `isProcessing(): bool` — status === 'processing'
  - [ ] Add method: `isGraded(): bool` — status === 'graded'
  - [ ] Add method: `isFailed(): bool` — status === 'failed'
  - [ ] Add method: `overrideScore(float $score, string $feedback, User $instructor): void`
    - [ ] Set instructor_score, instructor_feedback, reviewed_by, instructor_reviewed_at
    - [ ] Log to audit trail (Phase 1.5)
  - [ ] Add method: `getDisplayScore(): ?float` — returns max(ai_score, instructor_score) or instructor_score if set
  - [ ] Add method: `getDisplayFeedback(): ?string` — returns instructor_feedback if set, else ai_feedback

## 3. Enums

- [ ] Create `SubmissionStatus` enum (PHP 8.1 backed enum):
  - [ ] Values: `pending`, `processing`, `graded`, `failed`
  - [ ] Add method: `label(): string` — human-readable label ("Pending", "Processing", etc.)
  - [ ] Add method: `isTerminal(): bool` — true if status is graded or failed
- [ ] Add to Submission model casting: `'status' => SubmissionStatus::class`

## 4. Validation & Constraints

- [ ] Create `AssignmentFormRequest`:
  - [ ] Validate: title required, prompt_question required, max_score numeric 0-100
  - [ ] Validate: rubric is valid JSON if provided
  - [ ] Validate: passing_score <= max_score if both provided
- [ ] Create `SubmissionFormRequest`:
  - [ ] Validate: student_answer required, non-empty
  - [ ] Validate: user_id belongs to same school (school_id match)
  - [ ] Check: assignment allows multiple submissions if user already has graded submission
- [ ] Create `OverrideScoreFormRequest`:
  - [ ] Validate: instructor_score numeric 0-100
  - [ ] Validate: instructor_feedback required (optional, but good practice)
  - [ ] Validate: user is instructor with permission:override-grade

## 5. Livewire Components — Submission & Grading

- [ ] Create `AssignmentForm` Livewire component (modal):
  - [ ] Fields: title, prompt_question (textarea), rubric (JSON editor), max_score, passing_score
  - [ ] Rich text editor for prompt (TinyMCE or similar)
  - [ ] JSON schema validation for rubric
  - [ ] Preview rubric structure
  - [ ] On submit: create/update assignment
- [ ] Create `RubricBuilder` Livewire component:
  - [ ] Visual JSON form builder for rubric (not raw textarea)
  - [ ] Add/remove rubric items dynamically
  - [ ] Fields per item: criterion name, weight/percentage, description, max points
  - [ ] Calculate total weight (should sum to 100)
  - [ ] Export/import rubric as JSON
  - [ ] Validate structure before saving
- [ ] Create `EssaySubmissionForm` Livewire component:
  - [ ] Show assignment title, prompt, rubric (read-only)
  - [ ] Large textarea for essay answer
  - [ ] Character count (optional)
  - [ ] Submit button (disabled if empty)
  - [ ] On submit:
    - [ ] POST /submissions (see Controller)
    - [ ] Disable form after submit
    - [ ] Transition to SubmissionStatusChip (pending)
  - [ ] Rate limit handling: show error toast if 3/min exceeded
- [ ] Create `SubmissionStatusChip` Livewire component:
  - [ ] Display status badge (pending/processing/graded/failed)
  - [ ] Use wire:poll to auto-refresh every 2-3 seconds while pending/processing
  - [ ] Show timestamp (submitted, graded, failed retry attempts)
  - [ ] Icons + text (not color-only)
  - [ ] On transition to graded: show toast, collapse form, expand result
  - [ ] Show retry info if failed (e.g., "Retrying... attempt 2/5")
- [ ] Create `SubmissionResultPanel` Livewire component:
  - [ ] Display score (AI or instructor override)
  - [ ] Render structured feedback (not raw JSON)
  - [ ] Show rubric item feedback if available
  - [ ] Highlight instructor override (badge: "Instructor Override")
  - [ ] If instructor override: show both AI score and override score
  - [ ] Allow multiple submissions if assignment allows (show all attempts in tabs)
- [ ] Create `GradingQueueTable` Livewire component:
  - [ ] Table of submissions for assignments created by logged-in instructor
  - [ ] Columns: student name, assignment, status, AI score, submitted date, actions
  - [ ] Filters: status (pending/processing/graded/failed), assignment, date range
  - [ ] Actions per submission: View Details, Override Score, Mark as Reviewed
  - [ ] Batch actions: Mark as Reviewed (multiple), Resend Failed
  - [ ] Pagination (50 per page)
- [ ] Create `OverrideScoreModal` Livewire component:
  - [ ] Show submission details (student name, original AI score, feedback)
  - [ ] Form: new score (numeric), instructor feedback (textarea)
  - [ ] On submit: call Submission->overrideScore(), refresh GradingQueueTable
  - [ ] Show confirmation toast

## 6. Routes & Controllers

- [ ] Create `AssignmentController`:
  - [ ] `store($lesson)` — create assignment (POST /lessons/{lesson_id}/assignments)
  - [ ] `update($assignment)` — update assignment (PATCH /assignments/{id})
  - [ ] `destroy($assignment)` — delete assignment (DELETE /assignments/{id})
  - [ ] `publish($assignment)` — toggle publish (POST /assignments/{id}/publish)
  - [ ] Require `permission:create-assignment`, `permission:edit-assignment`, etc.
- [ ] Create `SubmissionController`:
  - [ ] `store(SubmissionFormRequest $request)` — create submission
    - [ ] POST /submissions
    - [ ] Accept: assignment_id, student_answer
    - [ ] Validate: assignment exists, is published, assignment allows this student, rate limit check
    - [ ] Create Submission record with status='pending'
    - [ ] Dispatch `GradeSubmissionJob` (Phase 1.4) to queue
    - [ ] Return: HTTP 200 with Submission (status=pending) as JSON
    - [ ] **Must complete in <500ms** (job runs async in Phase 1.4)
  - [ ] `show($submission)` — GET /submissions/{id} (for polling)
    - [ ] Return Submission with ai_score, ai_feedback, status
    - [ ] Respects auth: student can view own, instructor can view all in course
  - [ ] `override($submission, OverrideScoreFormRequest $request)` — PATCH /submissions/{id}/override
    - [ ] Require `permission:override-grade`
    - [ ] Call $submission->overrideScore(...)
    - [ ] Return: HTTP 200 with updated Submission
  - [ ] `retry($submission)` — POST /submissions/{id}/retry (admin only)
    - [ ] If status='failed', reset to pending and redispatch job
- [ ] Create `GradingQueueController`:
  - [ ] `index()` — GET /grading-queue
    - [ ] Return Livewire component (GradingQueueTable)
    - [ ] Filter by instructor, apply current teacher's assignments

## 7. RBAC: Authorization Policies & Middleware

**From Phase 1.1 Deferred Tasks:**

- [ ] Create base `BasePolicy` class:
  - [ ] Add protected method `userHasPermission($user, $permission_slug): bool`
  - [ ] Add protected method `userHasRole($user, $role_slug): bool`
  - [ ] All policies inherit from BasePolicy
- [ ] Generate model policies:
  - [ ] `RolePolicy` (who can create/view/edit/delete roles)
    - [ ] `viewAny`: role:admin
    - [ ] `view`: role:admin
    - [ ] `create`: role:admin + permission:create-role
    - [ ] `update`: role:admin + permission:edit-role + author/owner check
    - [ ] `delete`: role:admin + permission:delete-role + cannot delete system roles
  - [ ] `UserPolicy` (who can view/assign roles to users)
    - [ ] `assignRole`: role:admin + permission:assign-roles
    - [ ] `removeRole`: role:admin + permission:assign-roles
  - [ ] `AssignmentPolicy` (use permission-based checks)
    - [ ] `create`: permission:create-assignment
    - [ ] `update`: permission:edit-assignment
    - [ ] `delete`: permission:delete-assignment
  - [ ] `SubmissionPolicy` (use permission-based checks)
    - [ ] `view`: permission:view-submissions
    - [ ] `override`: permission:override-grade
  - [ ] Register policies in `AuthServiceProvider` (or auto-discovery if using Laravel 13)
- [ ] Create `CheckPermission` middleware:
  - [ ] Accepts `$permission` parameter (e.g., `middleware('auth', 'permission:edit-course')`)
  - [ ] Checks: `auth()->user()->hasPermissionTo($permission)` for current school
  - [ ] Returns 403 if denied
- [ ] Create `CheckRole` middleware:
  - [ ] Accepts `$role` parameter (e.g., `middleware('auth', 'role:instructor')`)
  - [ ] Checks: `auth()->user()->hasRole($role)` for current school
  - [ ] Returns 403 if denied
- [ ] Register gates in `AuthServiceProvider`:
  - [ ] `Gate::define('permission', fn ($user, $permission) => $user->hasPermissionTo($permission))`
  - [ ] `Gate::define('role', fn ($user, $role) => $user->hasRole($role))`
  - [ ] Use: `@can('permission', 'edit-course')` in Blade templates

## 8. RBAC: School Admin Panel (Role & User Management)

**From Phase 1.1 Deferred Tasks:**

- [ ] Create `RoleManagerTable` Livewire component:
  - [ ] Display paginated table of school's roles
  - [ ] Show role name, slug, permission count, system flag
  - [ ] Actions: Edit, Delete (disabled for system roles), Assign Permissions
  - [ ] Link to `CreateRoleModal` for new role creation
- [ ] Create `CreateRoleModal` Livewire component:
  - [ ] Form fields: Role name, slug (auto-generated from name)
  - [ ] Submit creates role in database (BelongsToSchool ensures school_id)
  - [ ] Validation: name required, slug unique per school
  - [ ] On success: refresh RoleManagerTable, show toast
- [ ] Create `EditRoleModal` Livewire component:
  - [ ] Pre-populate with existing role data
  - [ ] Disable slug editing (immutable)
  - [ ] Disable editing if `is_system_role = true`
  - [ ] Submit updates role
- [ ] Create `PermissionMatrix` Livewire component:
  - [ ] Display permission grid: rows = permissions (grouped by category), columns = selected role(s)
  - [ ] Checkbox for each (role, permission) pair
  - [ ] "Check All" and "Check by Category" quick actions
  - [ ] Save button syncs role_permission pivot
  - [ ] Use wire:model to track checked permissions client-side
  - [ ] Show toast on save success
- [ ] Create `UserRoleAssigner` Livewire component:
  - [ ] Display: user email, current roles, role selector
  - [ ] Dropdown/multi-select for available roles
  - [ ] Sync button assigns roles via `$user->syncRoles($role_ids)`
  - [ ] Validation: at least one role required per user

## 9. RBAC: Routes & Controller (Role Management)

**From Phase 1.1 Deferred Tasks:**

- [ ] Create `RoleController` with CRUD actions:
  - [ ] `index()` — list roles (route: GET /admin/roles) → returns `RoleManagerTable` Livewire component
  - [ ] `store()` — create role (route: POST /admin/roles) → called by `CreateRoleModal`
  - [ ] `update($role)` — update role (route: PATCH /admin/roles/{id}) → called by `EditRoleModal`
  - [ ] `destroy($role)` — soft delete role (route: DELETE /admin/roles/{id}) → prevent system role deletion
  - [ ] All routes require `middleware('auth', 'permission:manage-roles')`
- [ ] Create role management routes:
  - [ ] `GET /admin/roles` — role list page
  - [ ] `GET /admin/roles/{id}/permissions` — show permission matrix modal
  - [ ] `PATCH /admin/roles/{id}/permissions` — sync permissions
- [ ] Create `UserRoleController` for user role assignment:
  - [ ] `assignRole()` — PATCH /admin/users/{id}/roles — assign roles to user
  - [ ] Require `permission:assign-roles`

## 10. Rate Limiting

- [ ] Apply rate limit to submission endpoint:
  - [ ] Middleware or gate: `3 submissions per 1 minute per user + IP`
  - [ ] Route middleware: `middleware('throttle:3,1')`
  - [ ] On limit exceeded: HTTP 429 with error message
  - [ ] Livewire component should show error toast
- [ ] Test rate limit behavior in tests

## 11. Audit Logging

- [ ] Log to `audit_logs` table when:
  - [ ] Submission created
  - [ ] Score overridden by instructor
  - [ ] Status changes (pending → processing, processing → graded/failed)
- [ ] Capture old_values and new_values in JSONB (Phase 1.5)

## 12. Testing

- [ ] Create `AssignmentTest` (feature):
  - [ ] Instructor can create/edit/delete assignment
  - [ ] Assignment can be published/unpublished
  - [ ] Rubric structure is validated
  - [ ] Max score and passing score are enforced
- [ ] Create `SubmissionTest` (feature):
  - [ ] Student can submit essay
  - [ ] Submission status starts as 'pending'
  - [ ] Status transitions work (pending → processing → graded)
  - [ ] Multiple submissions enforced if allow_multiple=false
  - [ ] Submission respects assignment.allow_multiple_submissions flag
- [ ] Create `SubmissionFormRequestTest`:
  - [ ] Valid submission passes validation
  - [ ] Empty student_answer fails validation
  - [ ] Assignment publish check works
  - [ ] Rate limit test (submit 4 times, 4th fails)
- [ ] Create `OverrideScoreTest`:
  - [ ] Instructor can override score
  - [ ] Override is logged to audit trail
  - [ ] Student cannot override their own score
  - [ ] getDisplayScore() returns instructor score if set
- [ ] Create `SubmissionStatusChipTest` (Livewire):
  - [ ] Component renders with pending status
  - [ ] wire:poll refreshes submission status
  - [ ] Status badge changes color/text on grading
  - [ ] No JS errors on rapid polling
- [ ] Create `GradingQueueTableTest` (Livewire):
  - [ ] Instructor sees only their own assignments
  - [ ] Filter by status works
  - [ ] Override score modal opens and saves
  - [ ] Batch actions available

## 13. Documentation & Verification

- [ ] Create docs/RBAC.md (from Phase 1.1 deferred):
  - [ ] System roles vs custom roles distinction
  - [ ] Permission categories (courses, modules, lessons, assignments, submissions, roles, users, analytics, settings)
  - [ ] School-scoped roles: how they work and why
  - [ ] How to programmatically check permissions (hasPermissionTo, hasRole)
  - [ ] How to use middleware and gates in routes/views
  - [ ] Permission assignment matrix explanation
  - [ ] Default roles reference (Admin, Instructor, Student)
- [ ] Create docs/ASSESSMENT.md:
  - [ ] Submission state machine diagram (pending → processing → graded/failed)
  - [ ] Rubric JSON schema (examples)
  - [ ] Rate limiting explanation (3/min per user+IP)
  - [ ] Override behavior (instructor score takes precedence)
  - [ ] Error handling (failed submissions with retry details)
- [ ] Run `php artisan migrate:fresh --seed` and verify:
  - [ ] Permissions and default roles seeded (from Phase 1.2)
  - [ ] RBAC schema correct
- [ ] Test submission flow end-to-end (create assignment, submit, verify status)
- [ ] Run `vendor/bin/pint --dirty --format agent`

---

## Resolved Decisions

### Submission Status Enum
**Decision:** Use PHP 8.1 backed enum for status (pending, processing, graded, failed).

**Rationale:**
- Type-safe status values (compile-time check)
- Prevents invalid status strings
- Easy to add helper methods (isTerminal(), label())
- Database stored as string for readability

**Implementation:**
- Enum values: `case PENDING = 'pending'`, `case PROCESSING = 'processing'`, etc.
- Submission model casts to enum: `'status' => SubmissionStatus::class`
- Queries: can still use string (e.g., `where('status', 'pending')`) or enum (e.g., `where('status', SubmissionStatus::PENDING)`)

### Score Display Logic
**Decision:** `getDisplayScore()` returns instructor override if set, else AI score; `getDisplayFeedback()` same pattern.

**Rationale:**
- Instructor override is always final
- Clear precedence: human judgment > AI score
- Makes templates simpler (one method, not conditional)

**Implementation:**
- `getDisplayScore()` → `$this->instructor_score ?? $this->ai_score ?? null`
- `getDisplayFeedback()` → `$this->instructor_feedback ?? ($this->ai_feedback ? format($this->ai_feedback) : null)`

### Async Job Dispatch
**Decision:** Submission endpoint returns HTTP 200 immediately; grading job runs in background.

**Rationale:**
- Keeps response time <500ms (hard requirement)
- Job can take 10-30s without blocking user
- If job fails, user sees status=failed and can retry
- Better UX with polling status updates

**Implementation:**
- SubmissionController@store: create Submission, dispatch job, return 200
- Job runs in Phase 1.4 (see Phase 1.4 tasklist)
- Submission status transitions happen in job, not in controller

