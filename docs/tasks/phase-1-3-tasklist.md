# Phase 1.3 — Assessment & State Machine: Task List

**Goal:** Implement Assignment and Submission schema with dynamic JSON rubrics, submission state machine (pending → processing → graded/failed), and instructor override capabilities.

**Dependency:** Phase 1.0 (Data Architecture), Phase 1.1 (RBAC), Phase 1.2 (Content Engine) must be complete.

Reference: [PRD.md](../PRD.md) — Section 5 (US1, US3, US4), Section 8 (Core System Flow: AI Assessment Pipeline), Section 16 (Acceptance Criteria US1, US3, US4).

---

## 1. Database Schema — Assignments & Submissions

- [x] Create migration: `php artisan make:migration create_assignments_submissions_tables --no-interaction`
  - [x] `assignments` table:
    - [x] `id` (UUID, PK)
    - [x] `lesson_id` (UUID, FK → lessons.id, CASCADE)
    - [x] `title` (VARCHAR 255, not null)
    - [x] `prompt_question` (LONGTEXT, not null) — the question/prompt for students
    - [x] `rubric` (JSON/JSONB, nullable) — dynamic grading criteria structure
    - [x] `max_score` (DECIMAL 5,2, default 100.00) — typically 0-100
    - [x] `passing_score` (DECIMAL 5,2, nullable) — optional threshold
    - [x] `is_published` (BOOLEAN, default false)
    - [x] `allow_multiple_submissions` (BOOLEAN, default false) — one vs multiple attempts
    - [x] timestamps
  - [x] `submissions` table:
    - [x] `id` (UUID, PK)
    - [x] `assignment_id` (UUID, FK → assignments.id, CASCADE)
    - [x] `user_id` (UUID, FK → users.id, CASCADE)
    - [x] `student_answer` (LONGTEXT, not null) — raw essay text
    - [x] `status` (ENUM: 'pending', 'processing', 'graded', 'failed', default 'pending')
    - [x] `ai_score` (DECIMAL 5,2, nullable) — AI-generated score
    - [x] `ai_feedback` (JSON/JSONB, nullable) — structured AI response (e.g., {overall_score, feedback_per_rubric_item, suggestions})
    - [x] `instructor_score` (DECIMAL 5,2, nullable) — manual override score
    - [x] `instructor_feedback` (LONGTEXT, nullable) — manual override feedback
    - [x] `instructor_reviewed_at` (TIMESTAMP, nullable) — when instructor graded/overrode
    - [x] `reviewed_by` (UUID, FK → users.id, nullable, SET NULL) — which instructor reviewed
    - [x] `submitted_at` (TIMESTAMP, not null, default NOW) — submission timestamp
    - [x] `graded_at` (TIMESTAMP, nullable) — when AI/instructor finished grading
    - [x] `retry_count` (UNSIGNED INT, default 0) — how many times job has retried
    - [x] `error_message` (TEXT, nullable) — if status='failed', error details (from Gemini API)
    - [x] timestamps
    - [x] **Unique constraint:** `(assignment_id, user_id)` if `allow_multiple_submissions = false` — enforced at the application layer (`SubmissionFormRequest`/`SubmissionService`), not as a DB constraint, since it's conditional
    - [x] **Index:** `(assignment_id, status)` for filtering by status
    - [x] **Index:** `(user_id, status)` for student dashboard queries
- [x] Apply `HasUuid` trait to `Assignment` and `Submission` models

## 2. Models & Relationships

- [x] Update `Lesson` model (from Phase 1.2):
  - [x] Add relationship: `hasMany(Assignment::class)` — assignments in this lesson
- [x] Generate `Assignment` model:
  - [x] Apply `HasUuid` trait
  - [x] Add `$fillable` (`lesson_id`, `title`, `prompt_question`, `rubric`, `max_score`, `passing_score`, `is_published`, `allow_multiple_submissions`)
  - [x] Add `$casts` for JSON: `rubric` as array/collection
  - [x] Define relationships:
    - [x] `belongsTo(Lesson::class)` — parent lesson
    - [x] `hasMany(Submission::class)` — student submissions
  - [x] Add method: `getScore(Submission $submission): ?float` — returns instructor_score if set, else ai_score
  - [x] Add method: `isPassing(Submission $submission): bool` — check if score >= passing_score
  - [x] Add accessor: `rubricItems(): array` — parse rubric structure (e.g., [['key' => 'clarity', 'weight' => 0.5], ...])
- [x] Generate `Submission` model:
  - [x] Apply `HasUuid` trait
  - [x] Add `$fillable` (`assignment_id`, `user_id`, `student_answer`, `status`, `ai_score`, `ai_feedback`, `instructor_score`, `instructor_feedback`, `submitted_at`) — plus `reviewed_by`, `instructor_reviewed_at`, `graded_at`, `retry_count`, `error_message` (required for `overrideScore()`/grading pipeline mass-assignment)
  - [x] Add `$casts` for JSON: `ai_feedback` as array/collection
  - [x] Add `$casts` for ENUM: `status` as SubmissionStatus enum (see Phase 2.1.1 for enum pattern)
  - [x] Define relationships:
    - [x] `belongsTo(Assignment::class)` — parent assignment
    - [x] `belongsTo(User::class)` — student who submitted
    - [x] `belongsTo(User::class, 'reviewed_by')` — instructor who graded
  - [x] Add method: `isPending(): bool` — status === 'pending'
  - [x] Add method: `isProcessing(): bool` — status === 'processing'
  - [x] Add method: `isGraded(): bool` — status === 'graded'
  - [x] Add method: `isFailed(): bool` — status === 'failed'
  - [x] Add method: `overrideScore(float $score, string $feedback, User $instructor): void`
    - [x] Set instructor_score, instructor_feedback, reviewed_by, instructor_reviewed_at
    - [ ] Log to audit trail (Phase 1.5) — deferred, no audit_logs infra yet
  - [x] Add method: `getDisplayScore(): ?float` — returns instructor_score if set, else ai_score
  - [x] Add method: `getDisplayFeedback(): ?string` — returns instructor_feedback if set, else ai_feedback

## 3. Enums

- [x] Create `SubmissionStatus` enum (PHP 8.1 backed enum):
  - [x] Values: `pending`, `processing`, `graded`, `failed`
  - [x] Add method: `label(): string` — human-readable label ("Pending", "Processing", etc.)
  - [x] Add method: `isTerminal(): bool` — true if status is graded or failed
- [x] Add to Submission model casting: `'status' => SubmissionStatus::class`

## 4. Validation & Constraints

- [x] Create `AssignmentFormRequest`:
  - [x] Validate: title required, prompt_question required, max_score numeric 0-100
  - [x] Validate: rubric is valid JSON if provided (`array` rule; JSON-decoded by the request)
  - [x] Validate: passing_score <= max_score if both provided
- [x] Create `SubmissionFormRequest`:
  - [x] Validate: student_answer required, non-empty
  - [x] Validate: user_id belongs to same school (school_id match)
  - [x] Check: assignment allows multiple submissions if user already has graded submission
- [x] Create `OverrideScoreFormRequest`:
  - [x] Validate: instructor_score numeric 0-100
  - [x] Validate: instructor_feedback required (optional, but good practice)
  - [x] Validate: user is instructor with permission:override-grade (`submissions.override-grade` permission slug)

## Repository & Service Layer (added beyond original task list)

- [x] `AssignmentRepository` / `AssignmentRepositoryInterface` — bound in `AppServiceProvider`
- [x] `SubmissionRepository` / `SubmissionRepositoryInterface` — bound in `AppServiceProvider`
- [x] `AssignmentService` (CRUD passthrough + `publish()`/`unpublish()`)
- [x] `SubmissionService` (CRUD passthrough + `submit()` enforcing publish-state and multiple-submission rules + `overrideScore()`)
- [x] `AssignmentFactory` / `SubmissionFactory`
- [x] Tests: `AssignmentRepositoryTest`, `SubmissionRepositoryTest`, `AssignmentServiceTest`, `SubmissionServiceTest` (26 tests, all passing)

## 5. Livewire Components — Submission & Grading

- [x] Create `AssignmentForm` Livewire component (full-page, not modal — matches this codebase's convention, see `ModuleForm`):
  - [x] Fields: title, prompt_question (textarea), rubric (visual builder), max_score, passing_score
  - [ ] Rich text editor for prompt (TinyMCE or similar) (deferred: no rich text editor used elsewhere in this codebase; plain textarea matches existing conventions)
  - [x] JSON schema validation for rubric (weights must total 100, blocks save with visible error)
  - [x] Preview rubric structure (rubric shown read-only on EssaySubmissionForm)
  - [x] On submit: create/update assignment
- [x] Create `RubricBuilder` (folded into `AssignmentForm` rather than a separate component, per task-instruction guidance for this run):
  - [x] Visual JSON form builder for rubric (not raw textarea)
  - [x] Add/remove rubric items dynamically
  - [x] Fields per item: criterion name, weight/percentage, description, max points
  - [x] Calculate total weight (should sum to 100)
  - [x] Export/import rubric as JSON (serializes rubricItems to `rubric` JSON column; hydrates on edit)
  - [x] Validate structure before saving
- [x] Create `EssaySubmissionForm` Livewire component:
  - [x] Show assignment title, prompt, rubric (read-only)
  - [x] Large textarea for essay answer
  - [ ] Character count (optional) (deferred: optional, not implemented)
  - [ ] Submit button (disabled if empty) (partial: disabled while wire:loading/submitting, not on empty)
  - [x] On submit:
    - [x] Calls `SubmissionService::submit()` (POST /submissions endpoint also exists separately for JSON API use, see Controller)
    - [x] Disable form after submit (via wire:loading during redirect)
    - [x] Transition to SubmissionStatusChip (pending) (via redirect to SubmissionShow which embeds it)
  - [ ] Rate limit handling: show error toast if 3/min exceeded (deferred: rate limiting implemented only on the JSON `/submissions` POST controller endpoint with `throttle:3,1`, not on this Livewire form's direct service call)
- [x] Create `SubmissionStatusChip` Livewire component:
  - [x] Display status badge (pending/processing/graded/failed)
  - [x] Use wire:poll to auto-refresh every 3 seconds while pending/processing
  - [x] Show timestamp (submitted, graded, failed retry attempts) (retry attempt count shown; submitted/graded timestamps shown on SubmissionShow parent view)
  - [x] Icons + text (not color-only)
  - [ ] On transition to graded: show toast, collapse form, expand result (deferred: no toast library in this codebase; SubmissionResultPanel already always renders results section, "collapse/expand" not implemented)
  - [x] Show retry info if failed (e.g., "Retrying... attempt 2/5")
- [x] Create `SubmissionResultPanel` Livewire component:
  - [x] Display score (AI or instructor override)
  - [x] Render structured feedback (not raw JSON)
  - [x] Show rubric item feedback if available
  - [x] Highlight instructor override (badge: "Instructor Override")
  - [x] If instructor override: show both AI score and override score
  - [x] Allow multiple submissions if assignment allows (shown as simple links list on `SubmissionShow`, not tabs — per this run's plan)
- [x] Create `GradingQueueTable` Livewire component:
  - [x] Table of submissions scoped to assignments in the current school (via lesson→module→course→school_id, not solely "created by logged-in instructor" — matches this codebase's multi-instructor-per-school model)
  - [x] Columns: student name, assignment, status, AI score, submitted date, actions
  - [x] Filters: status (pending/processing/graded/failed), assignment, date range
  - [x] Actions per submission: View Details, Override Score (deferred: "Mark as Reviewed" — no such state/field on Submission)
  - [ ] Batch actions: Mark as Reviewed (multiple), Resend Failed (deferred: `SubmissionService` has no batch-update or resend support; noted in code comment in `GradingQueueTable`)
  - [x] Pagination (50 per page)
- [x] Create `OverrideScoreModal` Livewire component (implemented as a full page at `/submissions/{submission}/override`, not a JS modal — matches this codebase's convention):
  - [x] Show submission details (student name, original AI score, feedback)
  - [x] Form: new score (numeric), instructor feedback (textarea)
  - [x] On submit: call `SubmissionService::overrideScore()`, redirect to `SubmissionShow` with success banner
  - [ ] Show confirmation toast (deferred: no toast library; uses session-flash success banner instead)

## 6. Routes & Controllers

- [ ] Create `AssignmentController` (deferred: this codebase does not use controllers for CRUD — Livewire components ARE the routes, per this run's plan and matching `ModuleForm`/`CourseForm` convention. `AssignmentForm` Livewire component handles create/update/publish/unpublish/delete directly):
  - [x] `store` equivalent — `AssignmentForm::save()`, routed at `GET /lessons/{lesson}/assignments/create`
  - [x] `update` equivalent — `AssignmentForm::save()`, routed at `GET /assignments/{assignment}/edit`
  - [x] `destroy` equivalent — `AssignmentForm::delete()` via delete-confirm dispatch pattern
  - [x] `publish` equivalent — `AssignmentForm::publish()` / `unpublish()`
  - [x] Require `permission:assignments.create`, `permission:assignments.edit`, `permission:assignments.delete` (enforced via route middleware + `abort_unless` in component)
- [x] Wire assignment CRUD into the lesson editor UI (beyond the original task list — makes `AssignmentForm` reachable in practice):
  - [x] `LessonForm` lists the lesson's assignments (title, publish state, max score) with "+ New Assignment" link
  - [x] Edit/Delete actions per assignment (delete via existing global Alpine confirm-dialog pattern, `#[On('delete-confirmed')]`)
  - [x] Prompts to save the lesson first before assignments can be added (mirrors existing materials-upload UX)
- [x] Wire the student submission workflow into `LessonViewer` (beyond the original task list — `EssaySubmissionForm`/`SubmissionShow` had no discoverable entry point otherwise):
  - [x] Published assignments listed on the lesson page (unpublished/draft assignments hidden from students)
  - [x] "Start Assignment" link → `submissions.create`, or "View Submission" with status/score → `submissions.show` if already submitted
  - [x] "Submit Another Attempt" shown when `allow_multiple_submissions` and the existing submission is terminal
  - [x] Logged-out visitors see a "Login to Submit" prompt instead
- [x] Sidebar navigation (beyond the original task list):
  - [x] "Assignments" entry for instructors (`assignments.view`, hidden from students) → new `AssignmentsIndex` page (list across school's courses, since none existed for the Grading Queue / edit links to point to)
  - [x] "Grading Queue" entry for instructors (`submissions.grade`)
  - [x] "My Submissions" entry for students (`submissions.view`, hidden from instructors) → new `MySubmissions` page (students had no way to see their own submission history)
- [x] Create `SubmissionController`:
  - [x] `store(SubmissionFormRequest $request)` — create submission
    - [x] POST /submissions
    - [x] Accept: assignment_id, user_id, student_answer (per existing `SubmissionFormRequest` rules)
    - [x] Validate: assignment exists, is published, assignment allows this student, rate limit check (`throttle:3,1` route middleware)
    - [x] Create Submission record with status='pending' (via `SubmissionService::submit()`)
    - [ ] Dispatch `GradeSubmissionJob` (Phase 1.4) to queue (deferred: job class does not exist yet, left `// TODO` comment per task instructions)
    - [x] Return: HTTP 201 with Submission (status=pending) as JSON
    - [x] **Must complete in <500ms** (no blocking work; synchronous DB insert only)
  - [x] `show($submission)` — GET /submissions/{id}/status (for polling)
    - [x] Return Submission with ai_score, ai_feedback, status
    - [x] Respects auth: student can view own, instructor can view all in school (via `submissions.grade`/`submissions.view` permission)
  - [x] `override($submission, OverrideScoreFormRequest $request)` — PATCH /submissions/{id}/override
    - [x] Require `permission:submissions.override-grade` (enforced by `OverrideScoreFormRequest::authorize()` + route middleware)
    - [x] Call `SubmissionService::overrideScore(...)`
    - [x] Return: HTTP 200 with updated Submission
  - [x] `retry($submission)` — POST /submissions/{id}/retry (`permission:submissions.grade` required)
    - [x] If status='failed', reset to pending (deferred: redispatch of grading job left as `// TODO`, same reason as `store()`)
- [ ] Create `GradingQueueController` (deferred: `GradingQueueTable` Livewire component is registered directly as the route action at `GET /grading-queue`, matching this codebase's controller-less CRUD convention):
  - [x] `index()` equivalent — `GradingQueueTable` render()
    - [x] Return Livewire component (GradingQueueTable)
    - [x] Filter by current school's submissions (all instructors with `submissions.grade` in the school, not filtered to a single "logged-in instructor's own assignments" — matches this codebase's shared-school-permission model rather than per-instructor ownership)

## 7. RBAC: Authorization Policies & Middleware

**From Phase 1.1 Deferred Tasks:**

- [x] Create base `BasePolicy` class (`app/Policies/BasePolicy.php`):
  - [x] Add protected method `userHasPermission($user, $permission_slug): bool`
  - [x] Add protected method `userHasRole($user, $role_slug): bool`
  - [x] All policies inherit from BasePolicy (except `UserPolicy`, pre-existing from before this task and left as-is to avoid touching already-tested behavior)
- [x] Generate model policies:
  - [x] `RolePolicy` (who can create/view/edit/delete roles) — permission slugs use this codebase's actual naming (`roles.create`/`roles.edit`/`roles.delete`, not the task list's `create-role`/`edit-role`/`delete-role`)
    - [x] `viewAny`: role:Admin + permission:roles.view
    - [x] `view`: role:Admin + permission:roles.view
    - [x] `create`: role:Admin + permission:roles.create
    - [x] `update`: role:Admin + permission:roles.edit + same-school check
    - [x] `delete`: role:Admin + permission:roles.delete + cannot delete `protected` roles (this codebase's flag name for "system role")
  - [x] `UserPolicy` — already existed prior to this task (`app/Policies/UserPolicy.php`, `create`/`update`/`delete` gated on `hasRole('Admin')` + same-school); role-assignment itself is guarded inline in `UserRoles::updateRoles()` via `permission:users.assign-roles`, not a `UserPolicy::assignRole()` method — left as-is since it's exercised by existing passing tests
  - [x] `AssignmentPolicy` (permission-based checks; slugs match seeded `assignments.*` permissions)
    - [x] `create`: permission:assignments.create
    - [x] `update`: permission:assignments.edit
    - [x] `delete`: permission:assignments.delete
  - [x] `SubmissionPolicy` (permission-based checks)
    - [x] `view`: permission:submissions.view (own submission) or permission:submissions.grade (others')
    - [x] `override`: permission:submissions.override-grade
  - [x] Register policies in `AppServiceProvider::boot()` via `Gate::policy(...)` (no `AuthServiceProvider` exists in this Laravel 13 app — `AppServiceProvider` is the convention already used for `UserPolicy`)
- [x] Custom `CheckPermission`/`CheckRole` middleware — **not created**: Spatie Permission's `PermissionMiddleware`/`RoleMiddleware` are already aliased as `permission`/`role` in `bootstrap/app.php` and used throughout `routes/web/*.php` (`middleware('permission:roles.view')`, `middleware(['auth', 'role:Admin'])`); they do exactly what the task-list middleware would do, so a duplicate wrapper was skipped as redundant.
- [x] Register gates in `AppServiceProvider::boot()`:
  - [x] `Gate::define('permission', fn ($user, $permission) => $user->hasPermissionTo($permission))`
  - [x] `Gate::define('role', fn ($user, $role) => $user->hasRole($role))`
  - [x] Usable as `@can('permission', 'roles.view')` in Blade templates
- [x] Tests: `tests/Feature/PolicyRegistrationTest.php` (6 tests) covering RolePolicy/AssignmentPolicy/SubmissionPolicy authorization outcomes and the `permission`/`role` gates

## 8. RBAC: School Admin Panel (Role & User Management)

**From Phase 1.1 Deferred Tasks — already implemented (prior to this task run) using this codebase's established convention of full-page Livewire components instead of modals, matching `ModuleForm`/`PricingTierCreate`/`PricingTierEdit`. Component names below map 1:1 to the task list's modal-named equivalents:**

- [x] `RoleManagerTable` → `App\Livewire\Roles\RoleIndex` (`app/Livewire/Roles/RoleIndex.php`, `resources/views/livewire/roles/role-index.blade.php`):
  - [x] Paginated table of the school's roles
  - [x] Shows role name, permission count, `protected` flag (this codebase's "system role" flag)
  - [x] Actions: Edit link, Delete (disabled/hidden for `protected` roles via Alpine confirm-modal, not `wire:confirm`, per this codebase's established delete-modal pattern)
  - [x] "+ New Role" links to `RoleCreate` (full page, not a modal)
- [x] `CreateRoleModal` → `App\Livewire\Roles\RoleCreate` (`app/Livewire/Roles/RoleCreate.php`):
  - [x] Form field: Role name (no separate slug field — this codebase's `Role` model has no user-facing `slug`; Spatie's `name` is the unique identifier)
  - [x] Creates role scoped to current school via `RoleService`
  - [x] Validation: name required, unique per school
  - [x] On success: flash success banner + redirect to `RoleIndex` (no toast library in this codebase, see §5 deviation note)
- [x] `EditRoleModal` → `App\Livewire\Roles\RoleEdit` (`app/Livewire/Roles/RoleEdit.php`):
  - [x] Pre-populated with existing role + permissions
  - [x] Name editing blocked for the `Admin` role (`if ($this->role->name === 'Admin') { $validated['name'] = $this->role->name; }`)
  - [x] Submits update via `RoleService`
- [x] `PermissionMatrix` → folded into `RoleCreate`/`RoleEdit` (checkbox grid of `groupedPermissions` from `PermissionService::getAllGrouped()`, one role at a time) rather than a separate multi-role matrix component — this codebase edits permissions per-role, not per-permission-across-roles; a read-only cross-role view exists at `App\Http\Controllers\PermissionController::index` (`resources/views/app/permission/index.blade.php`)
  - [x] Checkbox per (role, permission) pair, grouped by category
  - [ ] "Check All" / "Check by Category" quick actions (deferred: not implemented, checkboxes are set individually)
  - [x] Save syncs `role_has_permissions` pivot via `RoleService::update()`
  - [x] `wire:model` tracks checked permission IDs client-side (via `permissionsJson`)
- [x] `UserRoleAssigner` → `App\Livewire\Users\UserRoles` (`app/Livewire/Users/UserRoles.php`):
  - [x] Displays user + current roles + all available roles as checkboxes
  - [x] Sync via `UserService::syncRoles()` (wraps `$user->syncRoles(...)`)
  - [x] "At least one role required" validation (added `min:1` to the `roles` rule in `UserRoles::rules()`)

## 9. RBAC: Routes & Controller (Role Management)

**From Phase 1.1 Deferred Tasks — already implemented (prior to this task run) using this codebase's controller-less convention: Livewire components are registered directly as route actions (same pattern as `CourseForm`/`ModuleForm`/`AssignmentForm`, see §6). A dedicated `RoleController`/`UserRoleController` was intentionally not (re)created since it would just proxy to the same Livewire `save()`/`update()`/`updateRoles()` methods already wired below:**

- [x] `RoleController` equivalent — role CRUD is exercised via `RoleIndex::destroy()`, `RoleCreate::store()`, `RoleEdit::update()`:
  - [x] `index()` — `GET /roles` (tenant, `routes/web/authenticated.php`) and `GET /admin/roles` (platform, `routes/web/admin.php`) → `RoleIndex`
  - [x] `store()` — handled by `RoleCreate::store()` at `GET/POST /roles/create` (Livewire full-page form, not a separate POST endpoint)
  - [x] `update($role)` — handled by `RoleEdit::update()` at `GET /roles/{role}/edit`
  - [x] `destroy($role)` — handled by `RoleIndex::destroy()`, blocked for `protected` roles via `Role::delete()` override (throws)
  - [x] Routes require `middleware('permission:roles.view')` / `'permission:roles.create'` / `'permission:roles.edit'` (tenant) or the `role:Admin` route-group guard (platform admin) — not a single `manage-roles` permission, since this codebase splits role management into `roles.view/create/edit/delete` per §6/§7's actual seeded slugs
- [x] Role management routes registered in `routes/web/authenticated.php` (tenant) and `routes/web/admin.php` (platform):
  - [x] `GET /roles` / `GET /admin/roles` — role list page
  - [x] Permission editing happens inline on `RoleEdit` (`GET /roles/{role}/edit`) rather than a separate `/permissions` sub-route + modal, per §8's `PermissionMatrix` deviation note
- [x] `UserRoleController` equivalent — `App\Livewire\Users\UserRoles::updateRoles()`:
  - [x] `GET /users/{id}/roles` renders the assigner; submitting calls `syncRoles()` in place (no separate PATCH endpoint needed since Livewire posts to itself)
  - [x] Requires `permission:users.assign-roles` (enforced both by route middleware and an `abort_unless` inside the component)

## 10. Rate Limiting

- [x] Apply rate limit to submission endpoint:
  - [x] Middleware or gate: `3 submissions per 1 minute per user + IP` (Laravel's default `throttle` middleware keys by authenticated user id, not by user+IP composite; matches this codebase's existing throttle usage elsewhere, e.g. `webhooks.php`)
  - [x] Route middleware: `middleware('throttle:3,1')` (`routes/web/authenticated.php:80`)
  - [x] On limit exceeded: HTTP 429 with error message (default Laravel `ThrottleRequestsException` response)
  - [ ] Livewire component should show error toast (deferred: no toast library in this codebase, see §5 deviation note; `EssaySubmissionForm` calls `SubmissionService::submit()` directly, bypassing the throttled JSON endpoint, so it isn't rate-limited today)
- [x] Test rate limit behavior in tests (`SubmissionControllerTest::rate limit triggers on the 4th rapid submission request`)

## 11. Audit Logging

- [ ] Log to `audit_logs` table when:
  - [ ] Submission created
  - [ ] Score overridden by instructor
  - [ ] Status changes (pending → processing, processing → graded/failed)
- [ ] Capture old_values and new_values in JSONB (Phase 1.5)

## 12. Testing

- [x] Create `AssignmentTest` (feature) (covered by `tests/Feature/Services/AssignmentServiceTest.php` rather than a dedicated controller-style test, since this codebase has no AssignmentController — CRUD is exercised at the service layer that `AssignmentForm` calls into):
  - [x] Instructor can create/edit/delete assignment (`can create an assignment`, `can delete an assignment`)
  - [x] Assignment can be published/unpublished (`can publish and unpublish an assignment`)
  - [x] Rubric structure is validated (`AssignmentForm::save()` blocks on weights ≠ 100; not covered by a dedicated automated test yet — deferred)
  - [x] Max score and passing score are enforced (`tests/Feature/AssignmentFormRequestTest.php`: `assignment rejects passing_score greater than max_score`, `assignment accepts passing_score less than or equal to max_score`)
- [x] Create `SubmissionTest` (feature) (covered across `tests/Feature/Services/SubmissionServiceTest.php`, `tests/Feature/ContentEngineSeederTest.php`, and `tests/Feature/Livewire/Courses/LessonViewerTest.php`):
  - [x] Student can submit essay (`can submit an answer for a published assignment`)
  - [x] Submission status starts as 'pending' (asserted in `SubmissionFactory` default + service test)
  - [ ] Status transitions work (pending → processing → graded) (deferred: no grading job exists yet — Phase 1.4 — so processing/graded transitions aren't triggered anywhere to test)
  - [x] Multiple submissions enforced if allow_multiple=false (`cannot resubmit when assignment disallows multiple submissions and a graded submission exists`)
  - [x] Submission respects assignment.allow_multiple_submissions flag (`can resubmit when assignment allows multiple submissions`; also `LessonViewerTest` asserts "Submit Another Attempt" visibility)
- [x] Create `SubmissionFormRequestTest` (folded into `tests/Feature/SubmissionControllerTest.php` rather than a standalone FormRequest test, since the request is exercised end-to-end through the controller):
  - [x] Valid submission passes validation (`store creates a pending submission quickly`)
  - [ ] Empty student_answer fails validation (deferred: not explicitly asserted, though the `required` rule exists in `SubmissionFormRequest`)
  - [x] Assignment publish check works (`cannot submit to an unpublished assignment` in `SubmissionServiceTest`)
  - [x] Rate limit test (submit 4 times, 4th fails) (`rate limit triggers on the 4th rapid submission request`)
- [x] Create `OverrideScoreTest` (covered by `SubmissionServiceTest::can override a submission score` + `SubmissionControllerTest::override requires submissions.override-grade permission`):
  - [x] Instructor can override score
  - [ ] Override is logged to audit trail (deferred: no audit_logs infra yet, see §2/§11)
  - [x] Student cannot override their own score (enforced via permission check, asserted in controller test)
  - [x] getDisplayScore() returns instructor score if set (asserted in model/service tests)
- [ ] Create `SubmissionStatusChipTest` (Livewire) (deferred: component exists and is exercised indirectly via `SubmissionShow`, but has no isolated Livewire test — polling/terminal-state behavior untested in isolation)
- [ ] Create `GradingQueueTableTest` (Livewire) (deferred: component exists with status/assignment/date filters and pagination per §5, but has no dedicated test file yet)
  - [ ] Instructor sees only their own assignments (note: this codebase scopes by *school*, not by individual instructor ownership — see §6 deviation note)
  - [ ] Filter by status works
  - [ ] Override score modal opens and saves
  - [ ] Batch actions available (out of scope — see §5 `GradingQueueTable` deferral)

## 13. Documentation & Verification

- [x] Create docs/RBAC.md (from Phase 1.1 deferred):
  - [x] System roles vs custom roles distinction
  - [x] Permission categories (courses, modules, lessons, assignments, submissions, roles, users, analytics, settings)
  - [x] School-scoped roles: how they work and why
  - [x] How to programmatically check permissions (hasPermissionTo, hasRole)
  - [x] How to use middleware and gates in routes/views
  - [x] Permission assignment matrix explanation
  - [x] Default roles reference (Admin, Instructor, Student)
- [x] Create docs/ASSESSMENT.md:
  - [x] Submission state machine diagram (pending → processing → graded/failed)
  - [x] Rubric JSON schema (examples)
  - [x] Rate limiting explanation (3/min per user, see docs/ASSESSMENT.md for actual key mechanism)
  - [x] Override behavior (instructor score takes precedence)
  - [x] Error handling (failed submissions with retry details)
- [x] Run `php artisan migrate:fresh --seed` and verify:
  - [x] Permissions and default roles seeded (from Phase 1.2)
  - [x] RBAC schema correct
- [x] Test submission flow end-to-end (create assignment, submit, verify status) — covered by `SubmissionControllerTest`/`SubmissionServiceTest`
- [x] Run `vendor/bin/pint --dirty --format agent`

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

