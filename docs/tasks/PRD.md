# Product Requirements Document (PRD)

**Project Name:** AI-Powered Learning Management System (SaaS)
**Document Status:** Approved for Development
**Primary Stack:** Laravel 13, Livewire, PostgreSQL 16, Redis, Anthropic API
**Target Architecture:** Multi-Tenant, Event-Driven, High Concurrency

---

## 1. Overview

**Feature / Project Name:** AI-Powered LMS (SaaS)

**Problem Statement:**
Educational institutions spend significant instructor time manually grading student essays, which is slow, inconsistent, and hard to scale across classes or institutions. Institutions also need strict data isolation when sharing a single platform (multi-tenant), along with flexible access control that doesn't depend on a developer to change roles/permissions.

**Proposed Solution:**
A multi-tenant LMS platform that combines tiered learning material management with automated LLM-based essay grading (Anthropic API), processed asynchronously to stay responsive under high load.

**AI Build Summary:**
> Build a multi-tenant Laravel 13 + Livewire + PostgreSQL 16 + Redis SaaS LMS. The UI is server-rendered via Livewire components (no separate SPA/API frontend) — Blade + Livewire handles forms, tables, and reactive state such as submission status updates. Core loop: instructors build Course > Module > Lesson hierarchies and JSON rubrics; students consume lessons and submit essay answers; a Redis-queued worker sends submissions to the Anthropic API and writes back `ai_score`/`ai_feedback` asynchronously, and the Livewire submission component polls/refreshes to reflect the new status without a full page reload. All primary keys are UUIDv4. Tenant isolation is enforced via `tenant_id` + global scopes. RBAC (roles/permissions) is dynamic and tenant-configurable, not hardcoded. Every mutation is captured in a polymorphic audit log (`old_values`/`new_values` JSONB). No synchronous LLM calls — submission endpoint must respond in <500ms and hand off to a background worker.

---

## 2. Goals & Success Metrics

**Primary Goal:** Automate essay grading reliably at scale while guaranteeing strict data isolation between tenants.

**Success Metrics:**
- Submission endpoint response time stays under 500ms (p95), even under concurrent load.
- AI grading pipeline completes (pending → graded) with a bounded retry policy on `failed` states, with no cross-tenant data leakage.
- 100% of data mutations produce a corresponding audit log entry.

**Anti-goals:**
- Not building real-time collaborative editing or live classroom sessions.
- Not building a custom-domain / white-label experience beyond storing a `domain` field (reserved for future use).
- Not replacing instructor judgment — AI scores are advisory and overridable, not final.

---

## 3. Scope & Constraints

**In scope:**
- Multi-tenant organization management (Super Admin, Tenant Admin).
- Dynamic RBAC (custom roles/permissions per tenant).
- Course > Module > Lesson content hierarchy with forced ordering and progress tracking.
- Assignment creation with dynamic JSON rubrics.
- Asynchronous AI-graded essay submissions with manual instructor override.
- Audit logging of all data mutations.
- Real-time observability dashboards (Pulse, Horizon) restricted to Super Admin.

**Out of scope:**
- Custom domain routing (schema field reserved, not implemented).
- Non-essay assessment types (quizzes, multiple choice) — not covered by this PRD.
- Payments/billing for tenant subscriptions.

**Technical constraints:**
- Platform: Web only.
- Auth: Laravel Fortify-based session auth, tenant-scoped.
- All Primary Keys must be UUIDv4 (prevents IDOR/enumeration attacks).
- LLM calls to Anthropic API must never block the request/response cycle — must run in Redis-backed background workers.
- Submission endpoint rate-limited to 3 requests/minute per IP/user.
- Data isolation: every tenant-scoped query must be filtered by `tenant_id`, enforced via global scopes — no manual filtering allowed to leak across tenants.
- Compliance: polymorphic audit trail (`old_values`/`new_values` in JSONB) required on all mutating actions.

---

## 4. Jobs to Be Done (JTBD)

| Priority | Job Statement |
|----------|---------------|
| 1 | When a student submits an essay answer, I want it evaluated automatically and consistently, so I can get fast, fair feedback without waiting on an instructor's queue. |
| 2 | When an instructor designs an assignment, I want to define a custom grading rubric, so I can ensure the AI evaluates against my specific criteria instead of a generic standard. |
| 3 | When a tenant admin onboards their institution, I want to define custom roles and permissions, so I can match the platform to our existing organizational structure without code changes. |
| 4 | When a Super Admin operates the platform, I want real-time visibility into queue load and system health, so I can catch grading pipeline failures before they affect multiple tenants. |
| 5 | When an instructor disagrees with an AI-generated score, I want to override it manually, so the final grade reflects human judgment as the source of truth. |

---

## 5. User Stories

| ID  | Role | Action | Benefit | JTBD Ref |
|-----|------|--------|---------|----------|
| US1 | Student | submit an essay answer to an assignment | receive an automated score and detailed feedback | J1 |
| US2 | Student | track my lesson completion progress | see how far along I am in a course | J1 |
| US3 | Instructor | define a JSON-based grading rubric per assignment | control exactly how the AI evaluates submissions | J2 |
| US4 | Instructor | override an AI-generated score | ensure the final grade reflects my professional judgment | J5 |
| US5 | Instructor | build a Course > Module > Lesson hierarchy with forced ordering | structure learning material the way I intend students to consume it | J1 |
| US6 | Tenant Admin | create custom roles and assign permissions | tailor access control to our institution without engineering support | J3 |
| US7 | Tenant Admin | view analytics across my institution | understand engagement and outcomes at the school level | J3 |
| US8 | Super Admin | register new tenants | onboard new institutions onto the platform | J3 |
| US9 | Super Admin | monitor system load and queue health via Pulse/Horizon | detect and respond to grading pipeline issues before they escalate | J4 |
| US10 | Compliance/Security stakeholder | review an immutable audit trail of data changes | investigate incidents and satisfy audit requirements | — |

---

## 6. Proposed Experience

**Design Direction:**
Two mental models coexist: a **content-authoring workspace** (Instructor building Course > Module > Lesson trees, similar to a document/outline editor) and a **consumption + feedback flow** (Student progressing through lessons and getting essay feedback that reads like a written review, not just a number). Admin surfaces (Tenant Admin, Super Admin) are dashboard-first — tables, filters, and status indicators over decorative UI. The AI grading result should feel like "waiting on a person," not a blocking spinner — asynchronous status with a visible progression (pending → processing → graded).

**Key Screens / States:**
- **Course Builder** (Instructor): tree/outline view of Course > Module > Lesson with drag-to-reorder (writes `order`), inline rubric editor (structured JSON form, not raw textarea) per Assignment.
- **Lesson Viewer** (Student): content/video pane + a persistent progress indicator for the current Module; "Mark complete" action writes `completed_at`.
- **Assignment Submission** (Student): essay textarea, submit button, and a status chip that updates pending → processing → graded without a page reload.
- **Submission Result** (Student): AI score, structured feedback (rendered from `ai_feedback` JSONB, not raw JSON), and an "instructor override" badge when `ai_score` has been manually changed.
- **Grading Queue** (Instructor): list of submissions filterable by status (pending/processing/graded/failed), with an override action inline.
- **Role & Permission Manager** (Tenant Admin): role list, permission matrix (checkbox grid), create-role flow.
- **Tenant Registry** (Super Admin): tenant list, create-tenant form, link out to Pulse/Horizon.
- **Pulse/Horizon Dashboards** (Super Admin): embedded/linked, not rebuilt — use Laravel's native dashboards.
- **Empty state:** new Course with no Modules shows a prompt to add the first Module, not a blank table. New Student dashboard with no enrolled courses shows a "browse courses" prompt.
- **Error state:** failed submission (`status: failed`) shows a plain-language message ("Grading failed, retrying automatically") rather than exposing the raw API error; instructor grading queue surfaces failed items distinctly so they aren't mistaken for still-pending.
- **Loading state:** submission status chip uses a progressive label change (Submitted → Grading in progress → Graded), not a generic spinner, since the wait can span seconds to minutes.

**Interaction Model:**
1. Student opens Lesson Viewer → reads/watches content → marks complete → progress updates immediately (optimistic UI, reconciled against `lesson_user.completed_at`).
2. Student opens Assignment → writes essay → submits via a Livewire component → sees `pending` chip immediately (HTTP 200 in <500ms) → the component uses `wire:poll` to refresh status to `processing` → `graded` without a full page reload or hand-written JS.
3. Instructor reviews graded submissions in the Grading Queue → optionally overrides a score → override is logged to `audit_logs`.
4. Tenant Admin creates a Role → assigns Permissions via matrix → assigns Role to Users.
- No destructive undo is required for MVP; overrides are corrections, not deletions, and are preserved in the audit trail rather than reversed in place.

**Accessibility Notes:**
- WCAG AA minimum across all surfaces.
- Course Builder drag-to-reorder must have a keyboard-operable alternative (e.g., move up/down buttons) — drag-only reordering is not accessible.
- Status chips (pending/processing/graded/failed) must not rely on color alone — pair with text label and/or icon.
- Rubric/permission matrix forms must have properly associated labels for screen readers, given their grid-like structure.

**Figma / Design Link:** [placeholder — add link when available]

---

## 7. Component Inventory

| Component | Type | Description | Linked Stories |
|-----------|------|-------------|-----------------|
| CourseTree | Layout | Collapsible Course > Module > Lesson outline with reorder controls | US5 |
| LessonEditor | Form | Rich text + video-embed-URL fields for authoring lesson content | US5 |
| RubricBuilder | Form | Structured editor for assignment JSON rubric (key/weight/criteria rows) | US3 |
| LessonViewer | Display | Renders lesson content/video for students | US2 |
| ProgressTracker | Display | Shows completion state across a module/course | US2 |
| MarkCompleteButton | Action | Writes `completed_at` for the current lesson | US2 |
| EssaySubmissionForm | Form | Textarea + submit for assignment answers | US1 |
| SubmissionStatusChip | Display | Pending/Processing/Graded/Failed indicator, non-color-reliant | US1 |
| SubmissionResultPanel | Display | Renders `ai_score` + structured `ai_feedback` | US1 |
| OverrideScoreModal | Modal | Instructor form to manually set a submission's score | US4 |
| GradingQueueTable | Display | Filterable table of submissions by status | US4 |
| RoleManagerTable | Display | List of tenant-scoped roles | US6 |
| PermissionMatrix | Form | Checkbox grid mapping roles to permissions | US6 |
| CreateRoleModal | Modal | Form to create a new tenant-scoped role | US6 |
| TenantAnalyticsDashboard | Display | Institution-level engagement/outcome charts | US7 |
| TenantRegistryTable | Display | Super Admin list of registered tenants | US8 |
| CreateTenantForm | Form | Super Admin form to onboard a new institution | US8 |
| AuditLogTable | Display | Filterable, read-only list of audit entries | US10 |
| EmptyStatePrompt | Display | Reusable "no data yet" prompt with a primary CTA | US2, US5 |
| RateLimitToast | Display | Notifies student when submission rate limit (3/min) is hit | US1 |

---

## 8. Core System Flow: AI Assessment Pipeline

State machine for the asynchronous AI grading feature:

1. **Ingestion:** Student submits essay text via HTTP POST.
2. **State Initialization:** Controller persists the submission to PostgreSQL with status `pending` and immediately returns HTTP 200 — no blocking connection held.
3. **Job Dispatching:** Controller dispatches a job (payload: submission UUID, essay text, rubric JSON) onto the Redis queue.
4. **Processing:** Redis worker picks up the job, sets status to `processing`, and assembles the prompt for the Anthropic API.
5. **Fulfillment:** Worker receives the LLM's JSON response, parses it, sets status to `graded`, and writes `ai_score` + `ai_feedback` back to the submission record.
6. **Failure Handling:** If the Anthropic API call times out or errors, status transitions to `failed` and the job is retried per the configured retry policy.

---

## 9. Functional Requirements

| Category | Feature | Acceptance Criteria |
| :--- | :--- | :--- |
| **Organization** | Multi-Tenancy | A single database holds many institutions. Data is automatically filtered by `tenant_id` on every session. |
| **Organization** | Dynamic RBAC | Institutions can create custom role names and assign specific permissions without changing source code. |
| **Content** | Course Engine | Supports building tiered material with forced ordering for the UI. |
| **Content** | Progress Tracking | Records the exact timestamp a student completes each piece of material, for learning-duration analytics. |
| **Assessment** | Dynamic Rubric | Instructors can define custom grading criteria per assignment using a dynamic JSON structure. |
| **AI Core** | Asynchronous Grader | Students can submit an essay. The system accepts it, sets status to `pending`, and the AI returns a decimal score plus detailed feedback asynchronously. |

---

## 10. Non-Functional / Engineering Requirements

| Category | Architecture Spec | Metrics & Standards |
| :--- | :--- | :--- |
| **Data Integrity** | UUID Implementation | Every Primary Key must be UUID v4 to prevent IDOR. |
| **Concurrency** | Redis Queue | API response time on essay submission must stay under 500ms. Communication with the LLM must run in a background worker. |
| **Resilience** | State Machine & Retry | If the Anthropic API times out, status changes to `failed` with a retry mechanism. |
| **Security** | Rate Limiting | The submission endpoint blocks more than 3 requests per minute from the same IP/user. |
| **Compliance** | Audit Logging | Every data modification is logged via Polymorphic Relations (`old_values`/`new_values` JSONB). |
| **Observability** | Pulse & Horizon | Real-time metrics dashboard active, secured exclusively at the routing level for Super Admin only. |

---

## 11. Data Models

Reflects `docs/ERD.md`. UUID v4 primary keys throughout; PostgreSQL 16 with JSONB for unstructured/audit data.

```typescript
interface Tenant {
  id: string;                 // UUID
  name: string;
  domain?: string;            // reserved for future custom-domain routing
  createdAt: string;
  updatedAt: string;
}

interface User {
  id: string;
  tenantId: string;           // FK -> Tenant, CASCADE
  name: string;
  email: string;               // unique
  password: string;            // hashed
  createdAt: string;
  updatedAt: string;
}

interface Role {
  id: string;
  tenantId: string;            // FK -> Tenant; roles are tenant-scoped
  name: string;
  slug: string;
}

interface Permission {
  id: string;
  name: string;
  slug: string;                 // unique, e.g. "grade-submissions"
}

// permission_role, role_user: pivot tables (role_id/permission_id, user_id/role_id), CASCADE on delete

interface Course {
  id: string;
  tenantId: string;
  title: string;
  description?: string;
  createdAt: string;
  updatedAt: string;
}

interface Module {
  id: string;
  courseId: string;             // FK -> Course, CASCADE
  title: string;
  order: number;                 // enforces UI sorting
  createdAt: string;
  updatedAt: string;
}

interface Lesson {
  id: string;
  moduleId: string;              // FK -> Module, CASCADE
  title: string;
  content?: string;              // text-based material
  videoEmbedUrl?: string;        // external video (YT/Vimeo)
  createdAt: string;
  updatedAt: string;
}

// lesson_user pivot: (user_id, lesson_id, completed_at) — unique composite key on (user_id, lesson_id)

interface Assignment {
  id: string;
  lessonId: string;              // FK -> Lesson, CASCADE
  title: string;
  promptQuestion: string;        // question for student / LLM context
  rubric?: Record<string, unknown>; // JSONB, dynamic grading rules
  maxScore: number;              // default 100
  createdAt: string;
  updatedAt: string;
}

interface Submission {
  id: string;
  assignmentId: string;          // FK -> Assignment, CASCADE
  userId: string;                // FK -> User, CASCADE
  studentAnswer: string;         // raw text sent to Anthropic API
  status: 'pending' | 'processing' | 'graded' | 'failed';
  aiScore?: number;               // DECIMAL(5,2)
  aiFeedback?: Record<string, unknown>; // JSONB, structured LLM response
  createdAt: string;
  updatedAt: string;
}

interface AuditLog {
  id: string;
  tenantId: string;               // isolates logs per tenant
  userId: string;                  // actor
  event: 'created' | 'updated' | 'deleted';
  auditableType: string;           // polymorphic model class
  auditableId: string;             // polymorphic model id
  oldValues?: Record<string, unknown>; // JSONB
  newValues?: Record<string, unknown>; // JSONB
  ipAddress?: string;
  createdAt: string;
}
```

---

## 12. API / Integration Surface

> Primary user-facing flows are implemented as Livewire components (server-rendered, no separate JSON API needed for the main app). The endpoints below represent the underlying actions/behavior; each is expected to be backed by a Livewire component method rather than a standalone REST controller, except where noted.

| Method | Path | Description | Auth Required | Response Shape |
|--------|------|-------------|---------------|----------------|
| POST | /api/submissions | Student submits essay answer for an assignment | Yes | `Submission` (status: `pending`), HTTP 200, non-blocking |
| GET | /api/submissions/:id | Poll submission status/result | Yes | `Submission` |
| POST | /api/assignments | Instructor creates assignment with rubric | Yes | `Assignment` |
| PATCH | /api/submissions/:id/override | Instructor overrides AI score | Yes | `Submission` |
| POST | /api/lessons/:id/complete | Mark lesson as completed for progress tracking | Yes | `{ completedAt: string }` |
| POST | /api/tenants | Super Admin registers a new tenant | Yes (Super Admin only) | `Tenant` |
| POST | /api/roles | Tenant Admin creates a custom role | Yes (Tenant Admin) | `Role` |
| PATCH | /api/roles/:id/permissions | Tenant Admin assigns permissions to a role | Yes (Tenant Admin) | `Role` with permissions |
| GET | /pulse, /horizon | Real-time system/queue observability dashboards | Yes (Super Admin only, route-level restricted) | HTML dashboard |

**External integrations:**
- Anthropic API: essay grading. Called exclusively from Redis background workers, never synchronously from a controller.
- Redis: queue backend for async job dispatching (submission grading pipeline).

---

## 13. State Management Map

| State | Location | Persistence | Notes |
|-------|----------|-------------|-------|
| `submission.status` | Server (PostgreSQL) | Persistent | Drives the pending → processing → graded/failed state machine; source of truth polled by the Livewire component via `wire:poll` |
| `tenant_id` scope | Auth/session context | Session | Applied via global scope on every tenant-scoped query; never trusted from client input |
| RBAC roles/permissions | Server (PostgreSQL), cached per request | Persistent | Evaluated per-request via middleware; tenant-specific |
| Lesson completion (`lesson_user.completed_at`) | Server (PostgreSQL) | Persistent | Written on completion event; unique per (user, lesson) |
| Queue job state | Redis | Transient (until processed) | Monitored via Horizon; retried per policy on failure |

---

## 14. Tech Stack Recommendation

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Backend Framework | Laravel 13 (PHP 8.3) | Existing project stack; mature ecosystem for multi-tenant, queue-driven apps |
| Frontend | Livewire | Server-driven reactive UI (forms, tables, submission status) without a separate SPA/API layer; already a project dependency |
| Database | PostgreSQL 16 | Native UUID + JSONB support needed for audit logs and rubric/feedback storage |
| Queue / Async | Redis + Laravel Horizon | Required for non-blocking submission handling and job observability |
| Auth | Laravel Fortify | Frontend-agnostic session auth; already in use per project conventions |
| AI Provider | Anthropic API | Specified LLM provider for essay grading |
| Observability | Laravel Pulse + Horizon | Real-time metrics, restricted to Super Admin routing |
| Styling | Tailwind CSS v4 | Existing project convention |

---

## 15. Suggested File Structure

Follows existing Laravel conventions in this codebase — no new base folders.

```
app/
├── Models/
│   ├── Tenant.php
│   ├── Role.php
│   ├── Permission.php
│   ├── Course.php
│   ├── Module.php
│   ├── Lesson.php
│   ├── Assignment.php
│   ├── Submission.php
│   └── AuditLog.php
├── Jobs/
│   └── GradeSubmissionJob.php     # Redis worker: calls Anthropic API
├── Observers/
│   └── AuditableObserver.php      # writes to audit_logs on mutations
├── Livewire/
│   ├── CourseBuilder.php
│   ├── EssaySubmissionForm.php    # wire:poll for pending → processing → graded
│   ├── GradingQueueTable.php
│   ├── RoleManager.php
│   └── TenantRegistry.php
└── Policies/
    └── ...                        # per-model authorization

resources/views/livewire/
└── ... (Blade views for each Livewire component above)

database/migrations/
└── ... (per ERD.md phases)
```

---

## 16. Acceptance Criteria

**US1 — Submit essay for AI grading**
- [ ] POST /api/submissions returns HTTP 200 within 500ms with status `pending`.
- [ ] Submission is never graded synchronously within the request lifecycle.
- [ ] Job is dispatched to Redis with submission UUID, essay text, and rubric JSON.
- [ ] Edge case handled: student exceeding 3 requests/minute receives a rate-limit error (429).
- [ ] Error state: Anthropic API timeout sets status to `failed` and triggers retry per policy.

**US3 — Define JSON grading rubric**
- [ ] Instructor can save an arbitrary JSON rubric structure on an assignment.
- [ ] Rubric is injected into the AI prompt at grading time.
- [ ] Edge case handled: assignment with no rubric still grades using default criteria.

**US4 — Override AI score**
- [ ] Instructor can PATCH a submission to set a manual score, overriding `ai_score`.
- [ ] Override action is captured in the audit log with old and new values.

**US6 — Create custom roles**
- [ ] Tenant Admin can create a role scoped to their tenant only.
- [ ] Tenant Admin can assign permissions to that role without code deployment.
- [ ] Edge case handled: roles created by one tenant are invisible to other tenants.

**US9 — Monitor system health**
- [ ] /pulse and /horizon routes return 403 for any non-Super-Admin user.
- [ ] Queue failures are visible in Horizon in real time.

**US10 — Audit trail**
- [ ] Every create/update/delete on an audited model produces an `audit_logs` row.
- [ ] `old_values` and `new_values` accurately reflect the pre/post state as JSONB.

---

## 17. Open Questions & Risks

- **Q:** What retry policy (max attempts, backoff strategy) applies to failed AI grading jobs? — *Owner: Eng*
- **Q:** Is `domain` field on `tenants` intended for future custom-domain routing, or should it be removed until implemented? — *Owner: PM*
- **Risk:** Anthropic API cost/rate limits at scale with many concurrent tenants submitting essays. — *Mitigation: queue throttling, per-tenant rate limits*
- **Risk:** Dynamic JSON rubrics are unvalidated free-form input to the LLM prompt — potential for prompt injection via rubric or student answer. — *Mitigation: sanitize/validate rubric structure, review prompt construction*
- **Tradeoff:** UUID primary keys over auto-increment integers — better security (no enumeration), slightly larger index size and marginally slower joins at scale.

---

## 18. Rollout & Next Steps

**MVP scope:**
- Includes: Phases 1–5 (Data Architecture, RBAC, Content Engine, Assessment schema, AI Integration).
- Excludes: Phase 6 (Compliance & Observability) can ship as fast-follow if timeline is constrained, though audit logging is considered core to compliance requirements.

**Development Roadmap (Strict Sequence):**
Modules must not be built before their foundation is stable.
1. **Phase 1 — Data Architecture Foundation:** UUID configuration, Tenant model, Global Scopes.
2. **Phase 2 — Advanced Security & Auth:** Dynamic RBAC system (Roles, Permissions, Middleware).
3. **Phase 3 — Content Engine:** CRUD for Course/Module/Lesson hierarchy, progress-tracking pivot.
4. **Phase 4 — Assessment & State Machine:** Assignment & Submission schema with JSONB fields.
5. **Phase 5 — AI Integration:** Redis queue configuration, Anthropic API job, prompt injection.
6. **Phase 6 — Compliance & Observability:** Audit Log observer, Laravel Horizon, Pulse installation.

**Sign-off needed from:**
- [ ] PM
- [ ] Engineering lead
- [ ] Design

**Next steps:**
1. Confirm retry policy and rate-limiting thresholds — *Owner: Eng*
2. Begin Phase 1 implementation per roadmap sequence — *Owner: Eng*
