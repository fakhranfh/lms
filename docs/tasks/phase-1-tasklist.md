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
- Tenant domains are stored in `tenants.domain` (e.g., `school1.lms.local`, `school2.lms.local`).
- Each school accesses the app via its own subdomain: `https://school1.lms.local/`.
- Admin panel accessed via reserved subdomain (e.g., `admin.lms.local`) or root domain.

**Implementation:**
- [ ] Create `CurrentTenant` class (e.g., `app/Support/CurrentTenant.php`) holding resolved `tenant_id` as a singleton.
- [ ] Create `ResolveTenantFromDomain` middleware that:
  - [ ] Extracts subdomain/domain from the current request.
  - [ ] Queries `tenants` table to find matching `domain` record.
  - [ ] Sets resolved tenant on `CurrentTenant` singleton.
  - [ ] Redirects to admin panel or throws 404 if domain not found.
  - [ ] Register in app middleware group so it runs on every request.
- [ ] `TenantScope` and `BelongsToTenant` read from `CurrentTenant` so behavior is identical across HTTP, console, and queue contexts.
- [ ] Refactor `CreateNewUser` to use `CurrentTenant::getTenantId()` — user auto-joins the tenant resolved from the subdomain they're registering on.
- [ ] Queue jobs must serialize `tenant_id` explicitly and set it on `CurrentTenant` at start of `handle()`, before touching any tenant-scoped model.
- [ ] Console commands and Super Admin cross-tenant actions bypass scoping via `Model::withoutGlobalScope(TenantScope::class)` — never by leaving `CurrentTenant` unset.
- [ ] Write a test proving a queued job correctly scopes queries to the tenant_id passed in its payload.

**Domain Management:**
- [ ] Add unique constraint on `tenants.domain` field.
- [ ] Seed a "Default Tenant" with domain (e.g., `admin.lms.local` or `localhost`).
- [ ] Create admin panel command/controller to register new schools with their subdomain (deferred to later phase, but scaffold the migration/model field now).

### Tenant domain validation
**Decision:** Enforce valid hostname format for `tenants.domain` since it's now critical for subdomain resolution (not just a future feature).
- [ ] Add a `StoreTenantRequest`/`UpdateTenantRequest` form request with a `domain` rule: unique, valid hostname format (e.g., `regex` for a domain-like string, no protocol/path, no trailing slash).
- [ ] Examples of valid domains: `school1.lms.local`, `myschool.lms.com`, `admin.lms.local`.
- [ ] Write a test asserting an invalid `domain` value (e.g., containing spaces, `http://`, path, trailing slash) is rejected at validation layer.
