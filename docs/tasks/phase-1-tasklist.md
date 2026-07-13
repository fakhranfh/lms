# Phase 1 — Data Architecture Foundation: Task List

**Goal:** Establish the multi-tenant data foundation (UUID primary keys, `Tenant` model, tenant-scoped `User`, and global scoping) that every later phase depends on. No later phase may begin until this one is stable.

Reference: [PRD.md](PRD.md) — Section 18 (Roadmap, Phase 1), Section 3 (Technical Constraints: UUIDv4 PKs, `tenant_id` isolation). [ERD.md](ERD.md) — Section 1 (Core & Multi-Tenancy).

---

## 1. UUID Primary Key Configuration

- [x] Enable the `uuid-ossp` (or `pgcrypto`) PostgreSQL extension via migration so `uuid_generate_v4()` is available.
- [x] Create a reusable `HasUuid` trait (`app/Traits/HasUuid.php`) that:
  - [x] Sets `$incrementing = false` and `$keyType = 'string'`.
  - [x] Auto-generates a UUIDv4 on the `creating` model event if `id` isn't already set.
- [x] Confirm migrations use `$table->uuid('id')->primary()` (not `id()`/auto-increment) for every tenant-scoped table introduced in this phase.
- [x] Write a unit test asserting a model using `HasUuid` receives a valid UUIDv4 `id` on creation without one being explicitly passed.

## 2. `tenants` Migration & Model

- [x] Generate migration: `php artisan make:migration create_tenants_table --no-interaction`.
  - [x] Columns per ERD: `id` (UUID PK), `name` (`VARCHAR(255)`, not null), `domain` (`VARCHAR(255)`, unique, nullable), timestamps.
- [x] Generate model: `php artisan make:model Tenant --no-interaction` (skip `-m`, migration already exists).
  - [x] Apply `HasUuid` trait.
  - [x] Add `$fillable` (`name`, `domain`).
  - [x] Define `hasMany` relationship to `User`.
- [x] Generate `TenantFactory` (`php artisan make:factory TenantFactory --no-interaction`) with realistic fake `name`/`domain`.
- [x] Write a feature/unit test covering: tenant creation, unique `domain` constraint violation.

## 3. `users` Table — Tenant Scoping

- [x] Modify the default `users` migration (or add a follow-up migration) to:
  - [x] Convert `id` to UUID PK (via `HasUuid` convention) — coordinate with Fortify/auth scaffolding so login/session code isn't broken.
  - [x] Add `tenant_id` (UUID, foreign key → `tenants.id`, indexed, `CASCADE ON DELETE`).
- [x] Update `User` model:
  - [x] Apply `HasUuid` trait.
  - [x] Add `belongsTo(Tenant::class)` relationship.
  - [x] Add `tenant_id` to `$fillable` (or set automatically — see Global Scope section).
- [x] Update `UserFactory` to associate a `Tenant` (via factory relationship) on creation.
- [x] Confirm existing Fortify auth flows (login/register) still function against the UUID-keyed, tenant-scoped `users` table — run the existing auth test suite.

## 4. Global Tenant Scope

- [ ] Create `app/Models/Scopes/TenantScope.php` implementing `Illuminate\Database\Eloquent\Scope`:
  - [ ] Applies a `where('tenant_id', ...)` constraint using the currently authenticated user's `tenant_id` (or resolved tenant context — see Resolved Decisions: Subdomain-based Tenant Resolution).
- [ ] Create a `BelongsToTenant` trait that:
  - [ ] Registers `TenantScope` in the model's `booted()` method.
  - [ ] Auto-fills `tenant_id` on `creating` from the current tenant context (`CurrentTenant` singleton).
- [ ] Apply `BelongsToTenant` to every tenant-scoped model introduced in this phase (`User`; later phases will apply it to `Course`, `Role`, etc.).
- [ ] Refactor `CreateNewUser` action to use `CurrentTenant` singleton instead of auto-creating a tenant. User will auto-join the tenant resolved from their request subdomain via `ResolveTenantFromDomain` middleware.
- [ ] Write tests proving:
  - [ ] A query for tenant-scoped models run as User A never returns rows belonging to Tenant B.
  - [ ] Creating a record without explicitly setting `tenant_id` still assigns the correct tenant automatically.
  - [ ] Super Admin / unauthenticated console context (e.g., queue workers, `php artisan tinker`) can still bypass the scope deliberately via `Model::withoutGlobalScope(TenantScope::class)` when required (e.g., cross-tenant admin operations) — document the escape hatch.

### 4b. Middleware & Domain Resolution
- [ ] Create `ResolveTenantFromDomain` middleware (see Resolved Decisions above).
- [ ] Create `app/Support/TenantDomainResolver.php` utility class:
  - [ ] Extract subdomain from request (e.g., `"school1"` from `"school1.lms.local"`).
  - [ ] Query `tenants` table by `domain` field.
  - [ ] Return `Tenant` object or null.
- [ ] Register `ResolveTenantFromDomain` in `app/Http/Kernel.php` → `$middleware` array (runs on every request).

### 4c. School Registration & Landing Page (lms.local)
- [ ] Create `StoreTenantRequest` form request with validation:
  - [ ] `name`: required, string, max 255.
  - [ ] `domain`: required, unique on `tenants` table, valid hostname format (no spaces, no protocol, no path).
- [ ] Create `TenantController@store` action (on lms.local routes):
  - [ ] Accept validated form data from `StoreTenantRequest`.
  - [ ] Create Tenant record in database with provided domain.
  - [ ] Redirect to the new school's subdomain (`schoolN.lms.local/register`) with success flash.
- [ ] Create public landing/registration page (lms.local/register-school):
  - [ ] Public route (no authentication required).
  - [ ] Display form: "Register your school" with fields: name, domain.
  - [ ] Form posts to `TenantController@store` (POST /register-school, routed on lms.local).
  - [ ] After successful registration, show message: "School registered! Go to [schoolN.lms.local/register](schoolN.lms.local/register) to create your account."

### 4d. Admin Panel Routes
- [ ] Create admin-only route group on `admin.lms.local`:
  - [ ] Routes require `middleware(['auth:web', 'role:admin'])`.
  - [ ] Middleware must verify: authenticated, role='admin', tenant_id=null (opsi C state).
  - [ ] Example routes: `/admin/dashboard`, `/admin/settings`, `/admin/logs`.
- [ ] Create admin login page (admin.lms.local/login):
  - [ ] Standard login form (email + password).
  - [ ] On auth: check if user.role='admin' && user.tenant_id=null, else deny.
  - [ ] Redirect to admin.lms.local/dashboard on success.
- [ ] Prevent non-admin users from accessing admin.lms.local:
  - [ ] Middleware check in `ResolveTenantFromDomain`: if admin.lms.local && !role('admin') → 403 Forbidden.

### 4e. Refactor Registration Flow
- [ ] Refactor `CreateNewUser` to use `CurrentTenant::getTenantId()` instead of creating tenant (remove `Tenant::create()` call).
- [ ] Ensure `CreateNewUser` throws error if `CurrentTenant::getTenantId()` is null when creating a regular user (guards against unscoped signup on lms.local).
- [ ] Admin account creation: only via seeding in initial migration (no self-service signup for admin).

- [ ] Write tests proving:

## 5. Verification & Wrap-Up

- [ ] Run `php artisan migrate:fresh --no-interaction` locally and confirm schema matches [ERD.md](ERD.md) Section 1 exactly (`database-schema` Boost tool).
- [ ] Run the full test suite: `php artisan test --compact`.
- [ ] Run `vendor/bin/pint --dirty --format agent` and fix any style issues.
- [ ] Confirm no other model/table in the codebase still uses auto-incrementing integer PKs where UUID is required by PRD Section 3.

---

## Resolved Decisions

### Subdomain-based Tenant Resolution
**Decision:** Extract tenant from request subdomain/domain via middleware, store in singleton, apply to all contexts.

**Architecture:**
Three-tier domain routing:
1. **lms.local** (root/landing) — Public area (no tenant). School registration form, landing page.
2. **admin.lms.local** — Admin panel only (CurrentTenant=null, role='admin', tenant_id=null). Strictly admin-exclusive.
3. **schoolN.lms.local** (school subdomains) — School app (CurrentTenant=schoolN_tenant_id). School users login/register here.

Admin state constraint (invariant):
- **Admin = role('admin') AND tenant_id IS NULL** (opsi C: invalid state forbidden).
- Cannot: have role='admin' with tenant_id != NULL (data corruption error).
- Non-admin accessing admin.lms.local → 403 Forbidden.
- Admin-only routes: require role('admin') && middleware verified tenant_id=null.

**Implementation:**
- [ ] Create `CurrentTenant` class (e.g., `app/Support/CurrentTenant.php`) holding resolved `tenant_id` as a singleton.
- [ ] Create `ResolveTenantFromDomain` middleware with three-tier logic:
  - [ ] Extracts subdomain from request (e.g., `school1` from `school1.lms.local`, root domain → `null` subdomain).
  - [ ] **Case 1: admin.lms.local** → `CurrentTenant::setTenantId(null)`. Check middleware: if authenticated && !role('admin') → throw 403.
  - [ ] **Case 2: lms.local (root)** → `CurrentTenant::setTenantId(null)`. Public area (no tenant required).
  - [ ] **Case 3: schoolN.lms.local** → Query `tenants` by domain. If found: `CurrentTenant::setTenantId(tenant.id)`. If not found: throw 404.
  - [ ] Data corruption check: if authenticated user has role('admin') && tenant_id != null → throw error (invalid state, opsi C).
  - [ ] Register in app middleware group so it runs on every request (before all route handling).
  - [ ] Ensure `CurrentTenant::getTenantId()` is available to all subsequent layers.
- [ ] `TenantScope` and `BelongsToTenant` read from `CurrentTenant` so behavior is identical across HTTP, console, and queue contexts.
- [ ] Refactor `CreateNewUser` to use `CurrentTenant::getTenantId()` — user auto-joins the tenant resolved from the subdomain they're registering on (remove the `Tenant::create()` call added in Section 3).
- [ ] Queue jobs must serialize `tenant_id` explicitly and set it on `CurrentTenant` at start of `handle()`, before touching any tenant-scoped model.
- [ ] Console commands and Super Admin cross-tenant actions bypass scoping via `Model::withoutGlobalScope(TenantScope::class)` — never by leaving `CurrentTenant` unset.
- [ ] Write a test proving a queued job correctly scopes queries to the tenant_id passed in its payload.

**Domain Management & School Registration (lms.local):**
- [ ] Add unique constraint on `tenants.domain` field.
- [ ] Seed a "Default Tenant" with domain `admin.lms.local` (for admin panel routing; no users assigned).
- [ ] Create `StoreTenantRequest` form request with domain validation (unique, valid hostname format).
- [ ] Create `TenantController` with:
  - [ ] `@store` (POST /register-school) — Create new school tenant from form.
  - [ ] Redirect to schoolN.lms.local/register after creation.
- [ ] Create routes on lms.local (root domain):
  - [ ] `GET /register-school` → Show registration form.
  - [ ] `POST /register-school` → TenantController@store.
- [ ] Seed admin user in initial migration:
  - [ ] Email: admin@lms.local or similar.
  - [ ] Role: 'admin' (Spatie).
  - [ ] tenant_id: NULL (invariant: admin must have null tenant).
  - [ ] Can login on admin.lms.local/login.

**Development Environment Setup:**
- [ ] Document `/etc/hosts` configuration for local testing:
  ```
  127.0.0.1 lms.local
  127.0.0.1 admin.lms.local
  127.0.0.1 school1.lms.local
  127.0.0.1 school2.lms.local
  ```
- [ ] Seed tenants in DatabaseSeeder:
  - `admin.lms.local` → Default Tenant (admin panel access; no regular users).
  - `test-school.lms.local` → Test Tenant (for feature tests).
- [ ] Seed admin user:
  - Email: `admin@lms.local`, password: `password` (docs).
  - Role: 'admin', tenant_id: NULL.
  - Can login at admin.lms.local/login.
- [ ] Update `.env` / `.env.example` with `APP_DOMAIN=lms.local` for reference.
- [ ] Add note in docs: "For local development, add hosts entries in `/etc/hosts`. Admin panel: admin.lms.local. School apps: schoolN.lms.local. Landing/registration: lms.local."

### Tenant domain validation
**Decision:** Enforce valid hostname format for `tenants.domain` since it's now critical for subdomain resolution (not just a future feature).
- [ ] Add a `StoreTenantRequest`/`UpdateTenantRequest` form request with a `domain` rule: unique, valid hostname format (e.g., `regex` for a domain-like string, no protocol/path, no trailing slash).
- [ ] Examples of valid domains: `school1.lms.local`, `myschool.lms.com`, `admin.lms.local`.
- [ ] Write a test asserting an invalid `domain` value (e.g., containing spaces, `http://`, path, trailing slash) is rejected at validation layer.
