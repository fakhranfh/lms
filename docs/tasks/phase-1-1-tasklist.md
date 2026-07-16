# Phase 1.1 — Advanced Security & Auth: Task List

**Goal:** Implement dynamic, school-scoped Role-Based Access Control (RBAC) system that allows each school to define custom roles and permissions without code changes.

**Status:** ✅ **FOUNDATION COMPLETE** — Database schema & models done. Authorization UI/middleware deferred to Phase 1.3.

**Milestone (2026-07-16):**
- ✅ Database Schema (4 migrations)
- ✅ Role & Permission models (school-scoped)
- ✅ 14 comprehensive tests (all passing)
- 🔜 Authorization (Phase 1.3)

**Dependency:** Phase 1.0 (Data Architecture) must be complete and stable.

Reference: [PRD.md](../PRD.md) — Section 5 (US6 & US13), Section 8 (Component Inventory), Section 16 (Acceptance Criteria US6).

---

## 1. Database Schema — Roles & Permissions

- [x] Create migrations for school-scoped roles & permissions:
  - [x] Add `school_id` (UUID, FK → schools.id, CASCADE) to existing `roles` table
  - [x] Add `slug` (VARCHAR 255, unique per school) to `roles` table
  - [x] Add `slug` (VARCHAR 255, unique globally) to existing `permissions` table
  - [x] Update unique constraint: `UNIQUE(name, guard_name, school_id)` on roles
  - [x] Populate slugs from names (kebab-case) via data migration
  - [x] Used Spatie's existing `roles`, `permissions`, `role_has_permissions`, `model_has_roles` tables
    - Note: `model_has_roles` is Spatie's polymorphic pivot; allows direct role assignment to users
  - [x] Applied to existing tables without full rewrite (leverages Spatie foundation)

## 2. Models & Relationships

- [x] Created `Role` model (extends Spatie\Permission\Models\Role):
  - [x] Fillable: `name`, `guard_name`, `school_id`, `slug`
  - [x] Relationships:
    - [x] `school()` — BelongsTo relationship
    - [x] `permissions()` — BelongsToMany via `role_has_permissions` pivot
  - [x] Methods:
    - [x] `scopeForSchool($schoolId)` — query roles by school
    - [x] `isGlobal()` — check if school_id is null
    - [x] Overridden `findByName()` & `findById()` — support school scoping with Spatie
- [x] Created `Permission` model (extends Spatie\Permission\Models\Permission):
  - [x] Fillable: `name`, `guard_name`, `label`, `group`, `slug`
  - [x] Global permissions (no school scoping)
  - [x] Relationships:
    - [x] `roles()` — BelongsToMany via `role_has_permissions` pivot
- [x] Updated `User` model:
  - [x] Already uses Spatie's `HasRoles` trait (polymorphic assignment via `model_has_roles`)
  - [x] Inherits: `hasRole()`, `assignRole()`, `removeRole()`, `syncRoles()`, `hasPermissionTo()`

## 3. RBAC Tasks (Moved to Respective Phases)

- ✅ **Permission Seeding** → Moved to [Phase 1.2 Section 3](../tasks/phase-1-2-tasklist.md)
- ✅ **Authorization Policies & Middleware** → Moved to [Phase 1.3 Section 7](../tasks/phase-1-3-tasklist.md)
- ✅ **School Admin Panel (RBAC UI)** → Moved to [Phase 1.3 Section 8](../tasks/phase-1-3-tasklist.md)
- ✅ **Role Management Routes** → Moved to [Phase 1.3 Section 9](../tasks/phase-1-3-tasklist.md)

## 4. Testing

- [x] Created `Phase1_1_RbacTest` (feature test suite):
  - [x] Database Schema tests
    - [x] Has school_id column on roles table
    - [x] Has slug column on roles table
    - [x] Has slug column on permissions table
  - [x] School Scoping tests
    - [x] Allows role scoping by school (scopeForSchool method)
    - [x] Identifies global roles (isGlobal method)
    - [x] Isolates roles between schools (different schools can't see each other's roles)
  - [x] Permissions tests
    - [x] Creates permissions with slug
    - [x] Assigns permissions to roles (givePermissionTo)
  - [x] User Assignment tests
    - [x] Assigns school-scoped roles to users
    - [x] Checks user permissions through roles
    - [x] Uses Spatie's polymorphic model_has_roles table
  - [x] School Relationships tests
    - [x] Gets roles through school relationship
    - [x] Cascades delete roles when school is deleted
  - [x] Data Integrity tests
    - [x] Maintains unique constraint on slug per role
    - [x] Stores permission slugs correctly
- [x] Test Results: **14/14 tests passing**
- [x] Full test suite: `php artisan test --compact` → **253/254 tests passing** (all existing tests still pass)

## 5. Documentation & Verification

- [x] Code committed with caveman-commit style: `feat(rbac): school-scoped roles and permissions`
- [x] Updated memory at `.claude/projects/d--Projek-lms/memory/phase-1-1-rbac.md`
- [x] All code formatted with Pint (vendor/bin/pint --dirty)
- [x] No breaking changes — all 253 existing tests still pass
- ✅ **Deferred Tasks Moved:**
  - → `Create docs/RBAC.md` moved to [Phase 1.3 Section 13](../tasks/phase-1-3-tasklist.md)
  - → `Run php artisan migrate:fresh --seed` moved to [Phase 1.2 Section 10](../tasks/phase-1-2-tasklist.md)

---

## Resolved Decisions

### Permission Scope (Global vs School-Scoped)
**Decision:** Permissions are **global** (not school-scoped); roles are **school-scoped**.

**Rationale:**
- Permission set is static and platform-wide (create-course, view-analytics, etc.)
- Roles are customizable per school (e.g., one school has "TA" role, another doesn't)
- Reduces data duplication and simplifies permission lookup
- Each school's Role model references the same Permission pool

**Implementation:**
- `permissions` table has no `school_id` column
- `Permission` model does NOT have `BelongsToSchool` trait
- `Role` model HAS `BelongsToSchool` trait
- `role_permission` pivot ties school-scoped Role to global Permission

### Default Roles as System Roles
**Decision:** Seed three default roles (Admin, Instructor, Student) with `is_system_role = true`; prevent deletion.

**Rationale:**
- Every school needs a minimum role hierarchy
- System roles cannot be accidentally deleted by school admin
- School can still create custom roles alongside defaults
- Clear distinction in UI between immutable and customizable roles

**Implementation:**
- `is_system_role` boolean flag on roles table
- `delete()` policy checks flag and returns 403 if true
- Livewire UI disables delete button for system roles

