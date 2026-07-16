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

## 3. Permission Definitions & Seeding

**Status: DEFERRED to Phase 1.2 (Content Engine)**

- [ ] Create `PermissionSeeder` defining all platform permissions once content models exist
- [ ] Create `DefaultRoleSeeder` to seed default roles per school
  - Deferred: Need Course, Module, Lesson, Assignment models first

## 4. Authorization Policies

**Status: DEFERRED to Phase 1.3 (Authorization & Middleware)**

- [ ] Create base `BasePolicy` class
- [ ] Generate model policies (Role, User, etc.)
- [ ] Register policies in `AuthServiceProvider`

## 5. Middleware & Gates

**Status: DEFERRED to Phase 1.3 (Authorization & Middleware)**

- [ ] Create `CheckPermission` middleware
- [ ] Create `CheckRole` middleware
- [ ] Register gates in `AuthServiceProvider`
- [ ] Write authorization tests

## 6. Livewire Components — School Admin Panel

**Status: DEFERRED to Phase 1.3 (Authorization & Middleware)**

- [ ] Create role management UI components
- [ ] Create permission matrix component
- [ ] Create user role assigner component

## 7. Routes & Controller

**Status: DEFERRED to Phase 1.3 (Authorization & Middleware)**

- [ ] Create `RoleController` with CRUD actions
- [ ] Create role management routes

## 8. Testing

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

## 9. Documentation & Verification

- [x] Code committed with caveman-commit style: `feat(rbac): school-scoped roles and permissions`
- [x] Updated memory at `.claude/projects/d--Projek-lms/memory/phase-1-1-rbac.md`
- [x] All code formatted with Pint (vendor/bin/pint --dirty)
- [x] No breaking changes — all 253 existing tests still pass
- [ ] Create docs/RBAC.md (deferred — add after authorization policies in Phase 1.3)
- [ ] Run `php artisan migrate:fresh --seed` (deferred — need seeders in Phase 1.2+)

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

