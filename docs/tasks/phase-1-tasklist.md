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

- [ ] Modify the default `users` migration (or add a follow-up migration) to:
  - [ ] Convert `id` to UUID PK (via `HasUuid` convention) — coordinate with Fortify/auth scaffolding so login/session code isn't broken.
  - [ ] Add `tenant_id` (UUID, foreign key → `tenants.id`, indexed, `CASCADE ON DELETE`).
- [ ] Update `User` model:
  - [ ] Apply `HasUuid` trait.
  - [ ] Add `belongsTo(Tenant::class)` relationship.
  - [ ] Add `tenant_id` to `$fillable` (or set automatically — see Global Scope section).
- [ ] Update `UserFactory` to associate a `Tenant` (via factory relationship) on creation.
- [ ] Confirm existing Fortify auth flows (login/register) still function against the UUID-keyed, tenant-scoped `users` table — run the existing auth test suite.

## 4. Global Tenant Scope

- [ ] Create `app/Models/Scopes/TenantScope.php` implementing `Illuminate\Database\Eloquent\Scope`:
  - [ ] Applies a `where('tenant_id', ...)` constraint using the currently authenticated user's `tenant_id` (or resolved tenant context — see below).
- [ ] Decide and document how "current tenant" is resolved (e.g., `auth()->user()->tenant_id`, a bound singleton, or middleware-set context). Record the decision in this file's Open Questions.
- [ ] Create a `BelongsToTenant` trait that:
  - [ ] Registers `TenantScope` in the model's `booted()` method.
  - [ ] Auto-fills `tenant_id` on `creating` from the current tenant context.
- [ ] Apply `BelongsToTenant` to every tenant-scoped model introduced in this phase (`User`; later phases will apply it to `Course`, `Role`, etc.).
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

### Tenant context resolution
**Decision:** Middleware + app-bound singleton.
- [ ] Create a `CurrentTenant` class (e.g., `app/Support/CurrentTenant.php`) holding the resolved `tenant_id`, bound as a singleton in a service provider.
- [ ] Add middleware (e.g., `SetTenantContext`) that reads `auth()->user()->tenant_id` on each authenticated HTTP request and sets it on the `CurrentTenant` singleton; register it in the web middleware group.
- [ ] `TenantScope` and `BelongsToTenant` read from `CurrentTenant` (not directly from `auth()->user()`) so behavior is identical across HTTP, console, and queue contexts.
- [ ] Queue jobs must serialize `tenant_id` explicitly in their payload/constructor and set it on `CurrentTenant` at the start of `handle()`, before touching any tenant-scoped model.
- [ ] Console commands and Super Admin cross-tenant actions bypass scoping explicitly via `Model::withoutGlobalScope(TenantScope::class)` — never by leaving `CurrentTenant` unset.
- [ ] Write a test proving a queued job correctly scopes queries to the tenant_id passed in its payload, independent of any authenticated session.

### Tenant.domain validation
**Decision:** Add basic format validation now, even though custom-domain routing is not implemented yet (PRD Section 3 marks it reserved for future use).
- [ ] Add a `StoreTenantRequest`/`UpdateTenantRequest` form request with a `domain` rule: nullable, valid hostname format (e.g., `regex` for a domain-like string, no protocol/path), plus the existing DB `unique` constraint.
- [ ] Write a test asserting an invalid `domain` value (e.g., containing spaces, protocol, or path) is rejected at the validation layer.
