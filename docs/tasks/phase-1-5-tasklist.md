# Phase 1.5 — Compliance & Observability: Task List

**Goal:** Implement immutable audit logging of all data mutations, and integrate Laravel Horizon/Pulse dashboards for queue and system observability (Super Admin only).

**Dependency:** Phase 1.0-1.4 must be complete.

Reference: [PRD.md](../PRD.md) — Section 5 (US10), Section 9 (Non-Functional Requirements: Audit Logging), Section 10 (Observability: Pulse & Horizon).

---

## 1. Database Schema — Audit Logging

- [ ] Create migration: `php artisan make:migration create_audit_logs_table --no-interaction`
  - [ ] `audit_logs` table:
    - [ ] `id` (UUID, PK)
    - [ ] `school_id` (UUID, FK → schools.id, CASCADE, indexed) — which school's action
    - [ ] `user_id` (UUID, FK → users.id, nullable, SET NULL) — who made the change (null for system actions)
    - [ ] `event` (ENUM: 'created', 'updated', 'deleted', 'restored', default 'updated')
    - [ ] `auditable_type` (VARCHAR 255) — fully qualified model class name (e.g., `App\Models\Course`)
    - [ ] `auditable_id` (UUID) — the record's ID
    - [ ] `old_values` (JSON/JSONB, nullable) — pre-change state (null for create events)
    - [ ] `new_values` (JSON/JSONB, nullable) — post-change state (null for delete events)
    - [ ] `ip_address` (VARCHAR 45, nullable) — IPv4 or IPv6
    - [ ] `user_agent` (VARCHAR 500, nullable) — browser/client info
    - [ ] `description` (VARCHAR 255, nullable) — human-readable summary (e.g., "Created course 'Math 101'")
    - [ ] timestamps (created_at only, immutable log)
    - [ ] **Index:** `(school_id, auditable_type, auditable_id)` for querying audits per record
    - [ ] **Index:** `(user_id, created_at)` for user activity reports
    - [ ] **Index:** `(auditable_type, created_at)` for audit trail by model type
- [ ] Apply `HasUuid` trait to `AuditLog` model

## 2. AuditLog Model & Observer Pattern

- [ ] Generate `AuditLog` model:
  - [ ] Apply `HasUuid` trait
  - [ ] Add `$fillable` (`school_id`, `user_id`, `event`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `description`)
  - [ ] Add `$casts` for JSON: `old_values`, `new_values` as arrays
  - [ ] Define relationships:
    - [ ] `belongsTo(User::class)` — who made the change (nullable for system)
    - [ ] `morphTo('auditable')` — the audited model (polymorphic)
  - [ ] Add scope: `forModel($model)` — query audits for a specific model instance
  - [ ] Add scope: `forType($type)` — query audits for a model type
  - [ ] Add scope: `byUser($user)` — query audits by user
  - [ ] Add scope: `inTenant($school_id)` — query audits for a school (auto-applied via BelongsToSchool)
- [ ] Create `AuditableObserver.php`:
  - [ ] Listen to model events: `created`, `updated`, `deleted`, `restored`
  - [ ] On each event:
    - [ ] Determine event type
    - [ ] Capture old values (from `$model->getOriginal()` for updates/deletes)
    - [ ] Capture new values (from `$model->getAttributes()`)
    - [ ] Get current user and request context (IP, user-agent)
    - [ ] Create AuditLog record (school_id auto-filled via CurrentTenant)
  - [ ] Example:
    ```php
    public function updated(Model $model)
    {
      $changes = $model->getChanges();
      if (empty($changes)) return; // no actual changes
      
      AuditLog::create([
        'school_id' => CurrentTenant::getTenantId(),
        'user_id' => auth()->id(),
        'event' => 'updated',
        'auditable_type' => $model::class,
        'auditable_id' => $model->id,
        'old_values' => array_intersect_key($model->getOriginal(), $changes),
        'new_values' => $changes,
        'ip_address' => request()?->ip(),
        'user_agent' => request()?->userAgent(),
        'description' => "{$model::class} updated",
      ]);
    }
    ```
- [ ] Register observer in `AuditLogServiceProvider`:
  - [ ] Register observers for all school-scoped models:
    - [ ] Course, Module, Lesson, Assignment, Submission
    - [ ] Role, Permission (global), User
    - [ ] PricingTier, SchoolTier, TierChange, PaymentTransaction (from Phase 2)
  - [ ] Example in `boot()`: `Course::observe(AuditableObserver::class)`

## 3. Audit Trail Management

- [ ] Create `AuditLogController`:
  - [ ] `index()` — GET /admin/audit-logs
    - [ ] Return Livewire component (AuditLogTable)
    - [ ] Super Admin only
    - [ ] Show all audits, filtered by tenant/model/user/date
- [ ] Create `AuditableObserver` exclude list:
  - [ ] Some models should NOT be audited (e.g., session, cache, temporary data)
  - [ ] Soft-delete fields are ignored (not considered "changes")
  - [ ] Updated_at changes alone don't trigger audit (unless other changes)
- [ ] Create retention policy:
  - [ ] Keep audit logs for 1 year (or regulatory requirement)
  - [ ] Create `AuditLogCleanupCommand`: `php artisan audit-logs:cleanup --older-than=1-year`
  - [ ] Run on cron monthly

## 4. Livewire Components — Audit Dashboard

- [ ] Create `AuditLogTable` Livewire component:
  - [ ] Table of audit log entries (paginated, 100 per page)
  - [ ] Columns: timestamp, user, action (created/updated/deleted), model type, description, changes
  - [ ] Filters: date range, user, model type, event type
  - [ ] Expand row to see old_values vs new_values in detail view
  - [ ] Export to CSV (all selected audits)
  - [ ] Search: by description, user email, model ID
  - [ ] Responsive table (scrollable on mobile)
- [ ] Create `AuditDetailModal`:
  - [ ] Show full AuditLog record
  - [ ] Display old_values and new_values side-by-side (JSON diff)
  - [ ] Highlight what changed (red/green for removed/added)
  - [ ] Link to audited model (if still exists)
  - [ ] Show user, IP, timestamp, user-agent
- [ ] Create `AuditSummaryDashboard` (optional, for analytics):
  - [ ] Chart: events per day/week (created, updated, deleted)
  - [ ] Top users by action count
  - [ ] Models most frequently changed
  - [ ] Most active time periods

## 5. Horizon Installation & Configuration

- [ ] Install Horizon (if not already):
  - [ ] `composer require laravel/horizon`
  - [ ] `php artisan horizon:install`
- [ ] Publish Horizon assets:
  - [ ] `php artisan vendor:publish --tag=horizon-assets`
- [ ] Register Horizon routes in `routes/web.php`:
  - [ ] Already handled by Horizon service provider
- [ ] Configure `config/horizon.php`:
  - [ ] Set timezone to match app
  - [ ] Set balance strategy: 'simple' or 'auto'
  - [ ] Set retention: 24 hours (keep 24h of job history)
  - [ ] Enable/disable blocking feature
- [ ] Create `HorizonServiceProvider` authorization:
  - [ ] Gate: `Gate::define('viewHorizon', fn ($user) => $user->hasRole('admin'))`
  - [ ] Only Super Admin can access `/horizon`
- [ ] Add Horizon to navigation/menu (admin only)

## 6. Pulse Installation & Configuration

- [ ] Install Pulse (if not already):
  - [ ] `composer require laravel/pulse`
  - [ ] `php artisan pulse:install`
- [ ] Publish Pulse configuration:
  - [ ] `php artisan vendor:publish --tag=pulse-config`
- [ ] Configure `config/pulse.php`:
  - [ ] Set retention: 7 days (keep 7 days of metrics)
  - [ ] Enable recorders: requests, jobs, exceptions, queues, database queries
  - [ ] Disable non-essential recorders to reduce overhead
- [ ] Configure Pulse authorization:
  - [ ] Gate: `Gate::define('viewPulse', fn ($user) => $user->hasRole('admin'))`
  - [ ] Only Super Admin can access `/pulse`
- [ ] Update `app/Providers/PulseServiceProvider.php`:
  - [ ] Register gate check
- [ ] Add Pulse dashboard link to admin panel

## 7. Queue Monitoring

- [ ] Ensure Horizon is running with queue worker:
  - [ ] `php artisan horizon` — starts Horizon supervisor
  - [ ] Alternatively: `php artisan queue:work --timeout=45` (basic worker, no Horizon UI)
- [ ] In Horizon dashboard:
  - [ ] Monitor GradeSubmissionJob queue
  - [ ] Watch job throughput (submissions/minute)
  - [ ] Check failure rate
  - [ ] Manually retry failed jobs
  - [ ] Inspect job payloads and results
- [ ] Create `QueueHealthCommand`:
  - [ ] `php artisan queue:health`
  - [ ] Check: Redis connection, pending jobs count, failed jobs count
  - [ ] Return: exit code 0 (healthy) or 1 (unhealthy)
  - [ ] Use for monitoring/alerting

## 8. Logging Configuration

- [ ] Update `config/logging.php`:
  - [ ] Create custom channel for audit logs (separate from app logs)
  - [ ] Log all audit events to file: `storage/logs/audit.log`
  - [ ] Rotate audit logs daily (keep 90 days)
  - [ ] Format: JSON for easy parsing
- [ ] Update audit logging in AuditableObserver:
  - [ ] Log to 'audit' channel when creating AuditLog
  - [ ] Include: timestamp, user, action, model, changes
  - [ ] Example:
    ```php
    Log::channel('audit')->info('Model audited', [
      'event' => 'updated',
      'model' => $model::class,
      'user_id' => auth()->id(),
      'changes' => $changes,
    ]);
    ```
- [ ] Create `AuditLogSubscriber` for event-based logging:
  - [ ] Alternative to observer if more control needed
  - [ ] Listen to model lifecycle events

## 9. Testing

- [ ] Create `AuditLogTest` (feature):
  - [ ] Create Course → AuditLog with event='created'
  - [ ] Update Course → AuditLog with event='updated', old/new values
  - [ ] Delete Course → AuditLog with event='deleted'
  - [ ] AuditLog.school_id matches course's school
  - [ ] old_values/new_values are JSON objects
  - [ ] user_id and ip_address are captured
- [ ] Create `AuditObserverTest`:
  - [ ] Observer correctly identifies changes
  - [ ] Only changed fields are logged (not static fields)
  - [ ] Soft deletes are properly logged
  - [ ] Multiple changes in single update are logged as single audit
- [ ] Create `AuditTableTest` (Livewire):
  - [ ] Super Admin can view audit log table
  - [ ] Non-admin gets 403 Forbidden
  - [ ] Filter by date range works
  - [ ] Filter by user works
  - [ ] Expand detail modal works
- [ ] Create `HorizonAuthorizationTest`:
  - [ ] /horizon accessible by Super Admin (role='admin', school_id=null)
  - [ ] Non-admin gets 403 or redirect
  - [ ] Unauthenticated gets redirect to login
- [ ] Create `PulseAuthorizationTest`:
  - [ ] /pulse accessible by Super Admin
  - [ ] Non-admin gets 403
- [ ] Run: `php artisan test --compact`

## 10. Documentation & Verification

- [ ] Create docs/AUDIT_LOGGING.md:
  - [ ] How audit logging works (observer pattern)
  - [ ] What events are logged (CRUD operations)
  - [ ] How to query audit logs programmatically
  - [ ] Audit log retention policy
  - [ ] Example queries: "all changes to a course", "all user actions", "changes made by specific user"
  - [ ] Performance considerations (don't query 10M audit logs)
- [ ] Create docs/MONITORING.md:
  - [ ] How to access Horizon dashboard (/horizon)
  - [ ] How to access Pulse dashboard (/pulse)
  - [ ] Queue job status interpretation
  - [ ] Troubleshooting queue failures
  - [ ] How to manually retry failed jobs
  - [ ] Performance metrics to watch
- [ ] Update `.env.example`:
  - [ ] QUEUE_CONNECTION=redis
  - [ ] HORIZON_PREFIX=laravel_queue:
  - [ ] PULSE_ENABLED=true
- [ ] Verification steps:
  - [ ] `php artisan migrate:fresh --seed`
  - [ ] Create a Course via UI
  - [ ] Check `audit_logs` table for entry
  - [ ] Access /admin/audit-logs (if route exists)
  - [ ] Start Horizon: `php artisan horizon`
  - [ ] Submit an essay (triggers job)
  - [ ] Check Horizon dashboard: job should appear
  - [ ] Check Pulse dashboard: request should appear
- [ ] Run `vendor/bin/pint --dirty --format agent`

---

## Resolved Decisions

### Audit Observer Pattern
**Decision:** Use Laravel's Eloquent observers (vs event subscribers) for audit logging.

**Rationale:**
- Simpler API for model lifecycle hooks (created, updated, deleted, restored)
- Automatically scoped to registered models
- Easy to enable/disable per model
- Minimal performance overhead

**Implementation:**
- Single `AuditableObserver` class listening to multiple models
- Register all models in `AuditLogServiceProvider`
- Can add `@auditble` attribute or config array if need per-model control

### AuditLog Immutability
**Decision:** AuditLog records are immutable (never updated or deleted except cleanup).

**Rationale:**
- Compliance requirement (audit trail must not be tampered with)
- Prevents accidental modifications
- Clear separation: data tables (mutable), audit tables (append-only)

**Implementation:**
- AuditLog model has no `update()` or `delete()` methods (override to throw)
- Cleanup only via scheduled command with retention policy
- Encryption at rest (future enhancement for GDPR)

### Horizon vs Queue:work
**Decision:** Use Horizon for production, local queue:work for development.

**Rationale:**
- Horizon provides beautiful UI, monitoring, manual job management
- For development, queue:work is simpler (fewer dependencies)
- Production needs observability

**Implementation:**
- In production: `php artisan horizon` (supervises workers)
- In development: `php artisan queue:work --timeout=45` (basic worker)
- Both use same Redis backend, code is identical

### Audit Detail Levels
**Decision:** Log all field changes, not just subset (full old/new values).

**Rationale:**
- Compliance often requires complete change history
- Users might need to trace unexpected changes
- Storage is cheap; storage failures are expensive

**Implementation:**
- old_values: entire record state before update (but only changed fields are included)
- new_values: entire record state after update
- Query: can see exactly what changed and what stayed the same

