# Product Requirements Document (PRD)

**Project Name:** AI-Powered Learning Management System (SaaS)
**Document Status:** Approved for Development
**Primary Stack:** Laravel 13, Livewire, PostgreSQL 16, Redis, Anthropic API
**Target Architecture:** Multi-School, Event-Driven, High Concurrency

---

## 1. Overview

**Feature / Project Name:** AI-Powered LMS (SaaS)

**Problem Statement:**
Educational institutions spend significant instructor time manually grading student essays, which is slow, inconsistent, and hard to scale across classes or institutions. Institutions also need strict data isolation when sharing a single platform (multi-school), along with flexible access control that doesn't depend on a developer to change roles/permissions.

**Proposed Solution:**
A multi-school LMS platform that combines tiered learning material management with automated LLM-based essay grading (Anthropic API), processed asynchronously to stay responsive under high load.

**AI Build Summary:**
> Build a multi-school Laravel 13 + Livewire + PostgreSQL 16 + Redis SaaS LMS. The UI is server-rendered via Livewire components (no separate SPA/API frontend) — Blade + Livewire handles forms, tables, and reactive state such as submission status updates. Core loop: instructors build Course > Module > Lesson hierarchies and JSON rubrics; students consume lessons and submit essay answers; a Redis-queued worker sends submissions to the Anthropic API and writes back `ai_score`/`ai_feedback` asynchronously, and the Livewire submission component polls/refreshes to reflect the new status without a full page reload. All primary keys are UUIDv4. School isolation is enforced via `tenant_id` + global scopes. RBAC (roles/permissions) is dynamic and school-configurable, not hardcoded. Every mutation is captured in a polymorphic audit log (`old_values`/`new_values` JSONB). No synchronous LLM calls — submission endpoint must respond in <500ms and hand off to a background worker.

---

## 2. Goals & Success Metrics

**Primary Goal:** Automate essay grading reliably at scale while guaranteeing strict data isolation between tenants.

**Success Metrics:**
- Submission endpoint response time stays under 500ms (p95), even under concurrent load.
- AI grading pipeline completes (pending → graded) with a bounded retry policy on `failed` states, with no cross-school data leakage.
- 100% of data mutations produce a corresponding audit log entry.

**Anti-goals:**
- Not building real-time collaborative editing or live classroom sessions.
- Not building a custom-domain / white-label experience beyond storing a `domain` field (reserved for future use).
- Not replacing instructor judgment — AI scores are advisory and overridable, not final.

---

## 3. Scope & Constraints

**In scope:**
- Multi-school organization management (Super Admin, School Admin).
- Dynamic RBAC (custom roles/permissions per school).
- Course > Module > Lesson content hierarchy with forced ordering and progress tracking.
- Assignment creation with dynamic JSON rubrics.
- Asynchronous AI-graded essay submissions with manual instructor override.
- Audit logging of all data mutations.
- Real-time observability dashboards (Pulse, Horizon) restricted to Super Admin.

**Out of scope:**
- Custom domain routing (schema field reserved, not implemented).
- Non-essay assessment types (quizzes, multiple choice) — not covered by this PRD.

**Technical constraints:**
- Platform: Web only.
- Auth: Laravel Fortify-based session auth, school-scoped. Demo access via secure token-based login (14-day expiry).
- All Primary Keys must be UUIDv4 (prevents IDOR/enumeration attacks).
- LLM calls to Anthropic API must never block the request/response cycle — must run in Redis-backed background workers.
- Submission endpoint rate-limited to 3 requests/minute per IP/user.
- Data isolation: every school-scoped query must be filtered by `tenant_id`, enforced via global scopes — no manual filtering allowed to leak across tenants.
- Compliance: polymorphic audit trail (`old_values`/`new_values` in JSONB) required on all mutating actions.
- Payment gateways: Support Midtrans and Xendit with webhook verification and transaction status polling.
- Pricing tiers: 4 default tiers (Basic, Plus, Pro, Max) with configurable features and limits per tier. Schools assigned tier at creation.

---

## 4. Jobs to Be Done (JTBD)

| Priority | Job Statement |
|----------|---------------|
| 1 | When a student submits an essay answer, I want it evaluated automatically and consistently, so I can get fast, fair feedback without waiting on an instructor's queue. |
| 2 | When an instructor designs an assignment, I want to define a custom grading rubric, so I can ensure the AI evaluates against my specific criteria instead of a generic standard. |
| 3 | When a school admin onboards their institution, I want to define custom roles and permissions, so I can match the platform to our existing organizational structure without code changes. |
| 4 | When a Super Admin operates the platform, I want real-time visibility into queue load and system health, so I can catch grading pipeline failures before they affect multiple tenants. |
| 5 | When an instructor disagrees with an AI-generated score, I want to override it manually, so the final grade reflects human judgment as the source of truth. |
| 6 | When a school selects a pricing tier, I want the system to automatically enforce feature limits (student capacity, storage, API access) based on their subscription level, so we can scale sustainably. |
| 7 | When a school wants to try the platform risk-free, I want secure demo access with 14-day trial, so I can evaluate all features before committing to a paid plan. |

---

## 5. User Stories

| ID  | Role | Action | Benefit | JTBD Ref |
|-----|------|--------|---------|----------|
| US1 | Student | submit an essay answer to an assignment | receive an automated score and detailed feedback | J1 |
| US2 | Student | track my lesson completion progress | see how far along I am in a course | J1 |
| US3 | Instructor | define a JSON-based grading rubric per assignment | control exactly how the AI evaluates submissions | J2 |
| US4 | Instructor | override an AI-generated score | ensure the final grade reflects my professional judgment | J5 |
| US5 | Instructor | build a Course > Module > Lesson hierarchy with forced ordering | structure learning material the way I intend students to consume it | J1 |
| US6 | School Admin | create custom roles and assign permissions | tailor access control to our institution without engineering support | J3 |
| US7 | School Admin | view analytics across my institution | understand engagement and outcomes at the school level | J3 |
| US8 | Super Admin | register new tenants | onboard new institutions onto the platform | J3 |
| US9 | Super Admin | monitor system load and queue health via Pulse/Horizon | detect and respond to grading pipeline issues before they escalate | J4 |
| US10 | Compliance/Security stakeholder | review an immutable audit trail of data changes | investigate incidents and satisfy audit requirements | — |
| US11 | School Admin | select and manage my school's pricing tier | control feature access and capacity based on our subscription level | J6 |
| US12 | School Admin | upgrade/downgrade my tier mid-cycle | adjust capacity as my institution's needs change | J6 |
| US13 | Super Admin | configure global pricing tiers and features | define what each tier includes (student capacity, storage, live sessions, API access) | J6 |
| US14 | Prospective School Admin | generate and use a demo access token | try all platform features for 14 days before purchasing | J7 |
| US15 | Billing stakeholder | receive payment notifications via webhook | reconcile transactions and update subscription status automatically | J6 |

---

## 6. Proposed Experience

**Design Direction:**
Two mental models coexist: a **content-authoring workspace** (Instructor building Course > Module > Lesson trees, similar to a document/outline editor) and a **consumption + feedback flow** (Student progressing through lessons and getting essay feedback that reads like a written review, not just a number). Admin surfaces (School Admin, Super Admin) are dashboard-first — tables, filters, and status indicators over decorative UI. The AI grading result should feel like "waiting on a person," not a blocking spinner — asynchronous status with a visible progression (pending → processing → graded).

**Key Screens / States:**
- **Course Builder** (Instructor): tree/outline view of Course > Module > Lesson with drag-to-reorder (writes `order`), inline rubric editor (structured JSON form, not raw textarea) per Assignment.
- **Lesson Viewer** (Student): content/video pane + a persistent progress indicator for the current Module; "Mark complete" action writes `completed_at`.
- **Assignment Submission** (Student): essay textarea, submit button, and a status chip that updates pending → processing → graded without a page reload.
- **Submission Result** (Student): AI score, structured feedback (rendered from `ai_feedback` JSONB, not raw JSON), and an "instructor override" badge when `ai_score` has been manually changed.
- **Grading Queue** (Instructor): list of submissions filterable by status (pending/processing/graded/failed), with an override action inline.
- **Role & Permission Manager** (School Admin): role list, permission matrix (checkbox grid), create-role flow.
- **School Registry** (Super Admin): school list, create-school form, link out to Pulse/Horizon.
- **Pulse/Horizon Dashboards** (Super Admin): embedded/linked, not rebuilt — use Laravel's native dashboards.
- **Empty state:** new Course with no Modules shows a prompt to add the first Module, not a blank table. New Student dashboard with no enrolled courses shows a "browse courses" prompt.
- **Error state:** failed submission (`status: failed`) shows a plain-language message ("Grading failed, retrying automatically") rather than exposing the raw API error; instructor grading queue surfaces failed items distinctly so they aren't mistaken for still-pending.
- **Loading state:** submission status chip uses a progressive label change (Submitted → Grading in progress → Graded), not a generic spinner, since the wait can span seconds to minutes.

**Interaction Model:**
1. Student opens Lesson Viewer → reads/watches content → marks complete → progress updates immediately (optimistic UI, reconciled against `lesson_user.completed_at`).
2. Student opens Assignment → writes essay → submits via a Livewire component → sees `pending` chip immediately (HTTP 200 in <500ms) → the component uses `wire:poll` to refresh status to `processing` → `graded` without a full page reload or hand-written JS.
3. Instructor reviews graded submissions in the Grading Queue → optionally overrides a score → override is logged to `audit_logs`.
4. School Admin creates a Role → assigns Permissions via matrix → assigns Role to Users.
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
| RoleManagerTable | Display | List of school-scoped roles | US6 |
| PermissionMatrix | Form | Checkbox grid mapping roles to permissions | US6 |
| CreateRoleModal | Modal | Form to create a new school-scoped role | US6 |
| TenantAnalyticsDashboard | Display | Institution-level engagement/outcome charts | US7 |
| TenantRegistryTable | Display | Super Admin list of registered tenants | US8 |
| CreateTenantForm | Form | Super Admin form to onboard a new institution | US8 |
| AuditLogTable | Display | Filterable, read-only list of audit entries | US10 |
| EmptyStatePrompt | Display | Reusable "no data yet" prompt with a primary CTA | US2, US5 |
| RateLimitToast | Display | Notifies student when submission rate limit (3/min) is hit | US1 |
| PricingTierManager | Display/Form | Super Admin UI for creating/editing pricing tiers and tier features/limits | US13 |
| TierSelectorForm | Form | School selection of tier during registration | US11 |
| SchoolTierDashboard | Display | School Admin view of current tier, usage, and upgrade/downgrade options | US11, US12 |
| TierFeatureGate | Logic | Middleware/helper to check if feature is available for school's tier | US11, J6 |
| DemoAccessGenerator | Action | Generate/display demo token and credentials for trial access | US14 |
| PaymentGatewayHandler | Service | Process webhook callbacks from Midtrans/Xendit and update subscription status | US15 |

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
| **Billing** | Pricing Tier System | 4 default tiers (Basic, Plus, Pro, Max) with per-tier features and capacity limits; schools assigned tier at creation and can upgrade/downgrade mid-cycle. |
| **Billing** | Payment Gateway Integration | Support Midtrans and Xendit with invoice creation, webhook verification, and transaction status polling for tier payments. |
| **Billing** | Feature Gating | Enforce tier-based feature availability (e.g., analytics, live sessions, API access) and capacity limits (student count per course, storage, live duration) at runtime. |
| **Trial** | Demo LMS Access | Generate secure 14-day demo tokens for prospective schools, allowing full platform trial without payment. |

---

## 10. Non-Functional / Engineering Requirements

| Category | Architecture Spec | Metrics & Standards |
| :--- | :--- | :--- |
| **Data Integrity** | UUID Implementation | Every Primary Key must be UUID v4 to prevent IDOR. |
| **Concurrency** | Redis Queue | API response time on essay submission must stay under 500ms. Communication with the LLM must run in a background worker. |
| **Resilience** | State Machine & Retry | If the Anthropic API times out, status changes to `failed` with a retry mechanism. |
| **Security** | Rate Limiting | The submission endpoint blocks more than 3 requests per minute from the same IP/user. |
| **Compliance** | Audit Logging | Every data modification is logged via Polymorphic Relations (`old_values`/`new_values` JSONB). Tier changes are tracked in `tier_changes` table. |
| **Observability** | Pulse & Horizon | Real-time metrics dashboard active, secured exclusively at the routing level for Super Admin only. |
| **Payment** | Webhook Security | Payment gateway webhooks verified via cryptographic signature (SHA-512 for Midtrans, token header for Xendit) before processing. |
| **Payment** | Transaction Idempotency | Webhook handlers check for duplicate payments (by gateway transaction_id) to prevent double-charging on retry. |
| **Billing** | Feature Gate Enforcement | Every feature-limited action (e.g., add student, create live session) checks tier features and limits before allowing; returns 403 if quota exceeded. |
| **Trial** | Demo Token Security | 32-character secure random token per generation, verified on login, 14-day expiry enforced at runtime. |

---

## 11. Data Models

Reflects `docs/ERD.md`. UUID v4 primary keys throughout; PostgreSQL 16 with JSONB for unstructured/audit data.

```typescript
interface School {
  id: string;                 // UUID
  name: string;
  domain?: string;            // reserved for future custom-domain routing
  createdAt: string;
  updatedAt: string;
}

interface User {
  id: string;
  tenantId: string;           // FK -> School, CASCADE
  name: string;
  email: string;               // unique
  password: string;            // hashed
  createdAt: string;
  updatedAt: string;
}

interface Role {
  id: string;
  tenantId: string;            // FK -> School; roles are school-scoped
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
  tenantId: string;               // isolates logs per school
  userId: string;                  // actor
  event: 'created' | 'updated' | 'deleted';
  auditableType: string;           // polymorphic model class
  auditableId: string;             // polymorphic model id
  oldValues?: Record<string, unknown>; // JSONB
  newValues?: Record<string, unknown>; // JSONB
  ipAddress?: string;
  createdAt: string;
}

interface PricingTier {
  id: string;
  name: string;                   // e.g., 'Basic', 'Plus', 'Pro', 'Max'
  slug: string;                   // unique identifier
  priceInCents: number;           // price in smallest currency unit (e.g., cents/paise)
  billingCycle: 'monthly' | 'yearly';
  description?: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
  // relations: features(), limits()
}

interface TierFeature {
  id: string;
  pricingTierId: string;          // FK -> PricingTier, CASCADE
  featureKey: string;             // e.g., 'analytics', 'live_session', 'api_access'
  isEnabled: boolean;
  createdAt: string;
  updatedAt: string;
  // unique constraint: (pricing_tier_id, feature_key)
}

interface TierLimit {
  id: string;
  pricingTierId: string;          // FK -> PricingTier, CASCADE
  limitKey: string;               // e.g., 'max_students_per_course', 'storage_gb', 'live_session_duration_min'
  limitValue?: number;            // NULL = unlimited
  createdAt: string;
  updatedAt: string;
  // unique constraint: (pricing_tier_id, limit_key)
}

interface SchoolTier {
  id: string;
  schoolId: string;               // FK -> School, CASCADE
  pricingTierId: string;          // FK -> PricingTier
  status: 'pending' | 'active' | 'expired';
  startDate: string;
  endDate?: string;               // NULL for indefinite subscriptions
  createdAt: string;
  updatedAt: string;
  // relations: school(), pricingTier(), tierChanges()
}

interface TierChange {
  id: string;
  schoolTierId: string;           // FK -> SchoolTier
  changeType: 'initial' | 'upgrade' | 'downgrade';
  fromTierId?: string;            // previous tier (FK -> PricingTier)
  toTierId: string;               // new tier (FK -> PricingTier)
  reason?: string;
  createdAt: string;
  // audit trail for tier transitions
}

interface DemoLmsAccess {
  id: string;
  schoolId: string;               // FK -> School, CASCADE
  userId?: string;                // FK -> User (demo admin user)
  accessToken: string;            // unique, 32-char secure token
  expiresAt: string;              // 14 days from generation
  accessedAt?: string;            // last login timestamp
  createdAt: string;
  // relations: school(), user()
}

interface PaymentTransaction {
  id: string;
  schoolTierId: string;           // FK -> SchoolTier
  gatewayName: string;            // 'midtrans' | 'xendit'
  transactionId: string;          // gateway-specific ID
  orderId: string;
  amount: number;                 // in smallest currency unit
  currency: string;               // e.g., 'IDR'
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  paymentUrl?: string;            // redirect URL for payment
  webhookData?: Record<string, unknown>; // JSONB, raw webhook payload
  createdAt: string;
  updatedAt: string;
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
| POST | /api/tenants | Super Admin registers a new school | Yes (Super Admin only) | `School` |
| POST | /api/roles | School Admin creates a custom role | Yes (School Admin) | `Role` |
| PATCH | /api/roles/:id/permissions | School Admin assigns permissions to a role | Yes (School Admin) | `Role` with permissions |
| GET | /pulse, /horizon | Real-time system/queue observability dashboards | Yes (Super Admin only, route-level restricted) | HTML dashboard |
| GET | /api/pricing-tiers | List all active pricing tiers | Yes (for UI tier selection) | `PricingTier[]` with features and limits |
| POST | /api/pricing-tiers | Super Admin creates a pricing tier | Yes (Super Admin only) | `PricingTier` |
| PATCH | /api/pricing-tiers/:id | Super Admin updates a pricing tier | Yes (Super Admin only) | `PricingTier` |
| POST | /api/school-tiers | Change school's subscription tier | Yes (School Admin) | `SchoolTier` with tier change audit |
| GET | /api/school-tiers/current | Fetch current school tier and usage | Yes | `SchoolTier` with features/limits and current usage |
| POST | /api/payments | Initiate payment via selected gateway | Yes (School Admin) | `PaymentTransaction` with payment_url |
| POST | /webhooks/payment/:gateway | Handle payment gateway webhook | No (verified by gateway signature) | HTTP 200/400 |
| GET | /demo-lms | Display demo access status and controls | Yes (School Admin) | demo credentials and status UI |
| POST | /demo-lms/generate | Generate new demo access token | Yes (School Admin) | `DemoLmsAccess` with token and credentials |
| GET | /demo-lms/login/:token | Auto-login with demo token | No (public, token-gated) | HTTP 302 redirect to dashboard |

**External integrations:**
- Anthropic API: essay grading. Called exclusively from Redis background workers, never synchronously from a controller.
- Redis: queue backend for async job dispatching (submission grading pipeline).
- Midtrans: Payment gateway for school tier subscriptions. Webhook verification via SHA-512 hash.
- Xendit: Payment gateway for school tier subscriptions. Webhook verification via X-Callback-Token header.

---

## 13. State Management Map

| State | Location | Persistence | Notes |
|-------|----------|-------------|-------|
| `submission.status` | Server (PostgreSQL) | Persistent | Drives the pending → processing → graded/failed state machine; source of truth polled by the Livewire component via `wire:poll` |
| `tenant_id` scope | Auth/session context | Session | Applied via global scope on every school-scoped query; never trusted from client input |
| RBAC roles/permissions | Server (PostgreSQL), cached per request | Persistent | Evaluated per-request via middleware; school-specific |
| Lesson completion (`lesson_user.completed_at`) | Server (PostgreSQL) | Persistent | Written on completion event; unique per (user, lesson) |
| Queue job state | Redis | Transient (until processed) | Monitored via Horizon; retried per policy on failure |
| `school_tier.status` | Server (PostgreSQL) | Persistent | Active/pending/expired; source of truth for feature gating checks |
| Feature availability per tier | Server (PostgreSQL) + cache | Persistent + cached | `tier_features` table, cached in memory per request to avoid N+1 queries |
| Tier capacity limits per school | Server (PostgreSQL) | Persistent | `tier_limits` table; checked before allowing resource creation (e.g., adding students to a course) |
| Payment transaction status | Server (PostgreSQL) | Persistent | Updated via webhook callbacks from Midtrans/Xendit; triggers tier activation on successful payment |
| Demo access token validity | Server (PostgreSQL) | Persistent | `demo_lms_accesses.expires_at` checked on every demo login; 14-day expiry per generation |

---

## 14. Tech Stack Recommendation

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Backend Framework | Laravel 13 (PHP 8.3) | Existing project stack; mature ecosystem for multi-school, queue-driven apps |
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
│   ├── School.php
│   ├── Role.php
│   ├── Permission.php
│   ├── Course.php
│   ├── Module.php
│   ├── Lesson.php
│   ├── Assignment.php
│   ├── Submission.php
│   ├── AuditLog.php
│   ├── PricingTier.php            # [Phase 2.1] Subscription tier definitions
│   ├── TierFeature.php            # [Phase 2.1] Feature availability per tier
│   ├── TierLimit.php              # [Phase 2.1] Capacity limits per tier
│   ├── SchoolTier.php             # [Phase 2.1] School's active subscription
│   ├── TierChange.php             # [Phase 2.1] Tier upgrade/downgrade audit
│   ├── PaymentTransaction.php     # [Phase 2.0C] Payment gateway transactions
│   └── DemoLmsAccess.php          # [Phase 2.2] Demo trial access tokens
├── Jobs/
│   └── GradeSubmissionJob.php     # Redis worker: calls Anthropic API
├── Services/
│   ├── PaymentGateways/
│   │   ├── PaymentGatewayContract.php  # [Phase 2.0C] Interface
│   │   ├── MidtransGateway.php         # [Phase 2.0C] Midtrans implementation
│   │   └── XenditGateway.php           # [Phase 2.0C] Xendit implementation
│   ├── PaymentGatewayFactory.php       # [Phase 2.0C] Gateway instantiation
│   ├── DemoLmsAccessService.php        # [Phase 2.2] Demo token generation
│   └── TierFeatureGateService.php      # [Phase 2.1.4] Feature availability checks
├── Observers/
│   └── AuditableObserver.php      # writes to audit_logs on mutations
├── Http/Controllers/
│   ├── PaymentWebhookController.php    # [Phase 2.0C] Webhook handlers
│   └── DemoLmsController.php           # [Phase 2.2] Demo access endpoints
├── Livewire/
│   ├── CourseBuilder.php
│   ├── EssaySubmissionForm.php    # wire:poll for pending → processing → graded
│   ├── GradingQueueTable.php
│   ├── RoleManager.php
│   ├── TenantRegistry.php
│   ├── PricingTierManager.php      # [Phase 2.1.2] Super Admin tier CRUD
│   ├── SchoolTierDashboard.php     # [Phase 2.1.3] School's tier & usage view
│   └── DemoAccessGenerator.php     # [Phase 2.2] Generate demo credentials
└── Policies/
    └── ...                        # per-model authorization

resources/views/livewire/
└── ... (Blade views for each Livewire component above)

database/migrations/
├── *_create_pricing_tiers_table.php       # [Phase 2.1.1]
├── *_create_tier_features_table.php       # [Phase 2.1.1]
├── *_create_tier_limits_table.php         # [Phase 2.1.1]
├── *_create_school_tiers_table.php        # [Phase 2.1.1]
├── *_create_tier_changes_table.php        # [Phase 2.1.1]
├── *_create_payment_transactions_table.php # [Phase 2.0C]
└── *_create_demo_lms_accesses_table.php   # [Phase 2.2]

database/seeders/
└── PricingTierSeeder.php          # [Phase 2.1.1] 4 default tiers (Basic, Plus, Pro, Max)
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
- [ ] School Admin can create a role scoped to their school only.
- [ ] School Admin can assign permissions to that role without code deployment.
- [ ] Edge case handled: roles created by one school are invisible to other tenants.

**US9 — Monitor system health**
- [ ] /pulse and /horizon routes return 403 for any non-Super-Admin user.
- [ ] Queue failures are visible in Horizon in real time.

**US10 — Audit trail**
- [ ] Every create/update/delete on an audited model produces an `audit_logs` row.
- [ ] `old_values` and `new_values` accurately reflect the pre/post state as JSONB.

**US11 — School tier management**
- [ ] School is assigned a tier at creation (default: Basic tier).
- [ ] School Admin can view current tier and feature/limit breakdown.
- [ ] Feature list per tier is accurate (analytics, live_session, api_access, etc.).
- [ ] Capacity limits are correctly displayed (max students per course, storage GB, live duration).

**US12 — Tier upgrade/downgrade**
- [ ] School Admin can change tier mid-cycle; change is logged to `tier_changes` table.
- [ ] Upgrade/downgrade is tracked with changeType ('upgrade' | 'downgrade').
- [ ] Payment is initiated for upgrades; refund calculated for downgrades (proration handled).

**US13 — Configure global pricing tiers**
- [ ] Super Admin can create new pricing tiers with name, price, billing cycle.
- [ ] Super Admin can assign features (feature_key) and limits (limit_key + limit_value) to each tier.
- [ ] Tier is marked active/inactive; inactive tiers do not appear in school tier selection.
- [ ] 4 default tiers seeded on fresh install (Basic free, Plus 299K IDR, Pro 799K IDR, Max 1999K IDR).

**US14 — Demo access trial**
- [ ] School Admin can generate a demo access token (32-char secure random).
- [ ] Token is unique, stored in `demo_lms_accesses`, and expires after 14 days.
- [ ] Demo user account is created (email: `demo-{school_id}@demo.{domain}`) with Admin role.
- [ ] Public demo login endpoint (`/demo-lms/login/{token}`) accepts token and auto-logs in user if not expired.
- [ ] Edge case: expired token returns 403 Forbidden, not a server error.

**US15 — Payment webhook handling**
- [ ] Webhook endpoint receives and verifies signature from Midtrans (SHA-512) or Xendit (token header).
- [ ] Duplicate transactions (same gateway transaction_id) are idempotent; no double-charge.
- [ ] On successful payment, `payment_transaction.status` set to 'completed' and `school_tier.status` set to 'active'.
- [ ] On failed payment, `payment_transaction.status` set to 'failed' and school remains on previous tier (no downgrade).

**Feature Gating (Cross-functional)**
- [ ] Accessing a tier-limited feature (e.g., analytics dashboard, live sessions, API) returns 403 if school tier does not have that feature enabled.
- [ ] Adding students to a course enforces max_students_per_course limit for that tier; returns 422 Unprocessable Entity if quota exceeded.
- [ ] Creating a live session checks live_session_duration_min limit; returns error if school's tier does not allow live sessions or exceeds max duration.
- [ ] Edge case: Basic tier (no analytics) shows read-only analytics stub with upgrade CTA, not an error.

---

## 17. Default Pricing Tiers (Phase 2.1)

| Tier | Price | Billing | Students/Course | Storage | Live Sessions | Analytics | API Access | Features |
|------|-------|---------|-----------------|---------|---|----------|-----------|-----------|
| **Basic** | Free | — | 500 | 100 GB | ❌ | ❌ | ❌ | Core LMS only |
| **Plus** | 299K IDR | Monthly | 1,000 | 500 GB | ✓ (120 min) | ✓ | ❌ | Analytics, Live Sessions (limited) |
| **Pro** | 799K IDR | Monthly | 5,000 | 2 TB | ✓ (Unlimited) | ✓ (Advanced) | ✓ | Recording, Advanced Analytics, API |
| **Max** | 1,999K IDR | Monthly | Unlimited | Unlimited | ✓ (Unlimited) | ✓ (Advanced) | ✓ | Custom Branding, SSO, Priority Support |

All prices in Indonesian Rupiah (IDR). Tiers seeded via `PricingTierSeeder` on fresh install.

---

## 18. Open Questions & Risks

- **Q:** Retry policy for failed AI grading jobs? — *Owner: Eng* — *Status: Implemented in Phase 1.4*
- **Q:** When/how to handle mid-cycle tier downgrades with proration? — *Owner: Eng* — *Deferred to Phase 5*
- **Risk:** Anthropic API cost/rate limits at scale with many concurrent tenants submitting essays. — *Mitigation: queue throttling, per-school rate limits*
- **Risk:** Dynamic JSON rubrics are unvalidated free-form input to the LLM prompt — potential for prompt injection via rubric or student answer. — *Mitigation: sanitize/validate rubric structure, review prompt construction*
- **Risk:** Payment webhook race condition if duplicate webhook is received during tier transition. — *Mitigation: idempotent handlers, check transaction_id before updating*
- **Tradeoff:** UUID primary keys over auto-increment integers — better security (no enumeration), slightly larger index size and marginally slower joins at scale.

---

## 18. Rollout & Next Steps

**MVP scope:**
- **Completed (Phase 1.0 + Phase 2):** Data Architecture foundation, Payment Gateways, Pricing Tiers, Demo LMS access
- **In Progress (Phase 1.1-1.5):** RBAC, Content Engine, Assessment & AI Grading, Compliance & Observability
- **Future (Phase 3+):** Analytics dashboards, Live Sessions, API access, Custom branding, SSO, Priority support

**Current MVP Status:** 60% complete (Phase 1.0 + 2.0C + 2.1 + 2.2 done; Phase 1.1-1.5 needed to reach core LMS feature parity).

**Development Roadmap (Completed & In Progress):**

### Phase 1 — Core LMS Foundation
1. **Phase 1.0** — Data Architecture: UUID configuration, School model, Global Scopes. *COMPLETE*
2. **Phase 1.1** — Advanced Security & Auth: Dynamic RBAC (Roles, Permissions, Middleware). *TODO*
3. **Phase 1.2** — Content Engine: Course > Module > Lesson hierarchy with progress tracking. *TODO*
4. **Phase 1.3** — Assessment & State Machine: Assignment, Submission schema with JSON rubrics. *TODO*
5. **Phase 1.4** — AI Integration: Redis queue, Anthropic API job, async grading pipeline. *TODO*
6. **Phase 1.5** — Compliance & Observability: Audit Log observer, Horizon/Pulse dashboards. *TODO*

### ✅ Phase 2 — Billing & Monetization (Built in parallel with Phase 1.0)
1. **Phase 2.0C** — Payment Gateway Implementation: Midtrans & Xendit integration with webhook handling. *COMPLETE*
   - Standardized response format, transaction verification, error handling
   - 20 comprehensive feature tests

2. **Phase 2.1** — Pricing Tier System: *COMPLETE*
   - **2.1.1** Core Data Structure: pricing_tiers, tier_features, tier_limits, school_tiers, tier_changes tables. *COMPLETE*
   - **2.1.2** Admin Panel: Super Admin CRUD UI for tier management. *COMPLETE*
   - **2.1.3** School Tier Assignment: Auto-assign tier on school creation, admin override UI. *COMPLETE*
   - **2.1.4** Feature Gating: M  iddleware/service to enforce tier-based feature availability. *COMPLETE*
   - **2.1.6** Testing & Finalization: 61/61 tests passing. *COMPLETE*

3. **Phase 2.2** — Demo LMS Setup: *COMPLETE*
   - Token generation, 14-day access, demo user creation, auto-login.
   - 16 comprehensive feature tests

### 🔜 Phase 3+ — Future Enhancements (Post-MVP)
1. **Phase 3** — Advanced Features: Analytics dashboards, Live sessions, API access (Pro/Max only).
2. **Phase 4** — Enterprise: Custom branding, SSO, Priority support.
3. **Phase 5** — Payment Flow: Tier change workflows, proration logic, renewal automation.

**Current Status:**
- **Phase 1.0** ✅ Complete (Data architecture foundation)
- **Phase 1.1-1.5** ⏳ TODO (RBAC, Content Engine, Assessment, AI Integration, Compliance)
- **Phase 2.0C, 2.1, 2.2** ✅ Complete (Payment gateways, Pricing tiers, Demo LMS)
- Payment gateways (Midtrans/Xendit) ready for production
- Pricing tiers (Basic, Plus, Pro, Max) seeded with defaults
- Demo LMS access functional and tested
- 240+ tests passing on completed phases

**Priority Decision Needed:**
1. **Build Phase 1.1-1.5** to enable core LMS functionality (content creation, submissions, grading)
2. **Skip Phase 1 and go straight to Phase 3** if billing/demo features are sufficient MVP without content engine

**Recommended Path:**
Build Phase 1.1-1.5 first (dependency: core LMS features must work before Phase 3 analytics/live sessions make sense).

**Next steps:**
1. Decide Phase 1.1-1.5 priority (MUST HAVE or DEFER?)
2. Lock timeline and resource allocation for Phase 1.1-1.5
3. Define Phase 1.1 scope (role/permission hierarchy, admin panel)
4. Plan Phase 3 feature prioritization (Analytics vs Live Sessions vs API)
