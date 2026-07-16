# Phase 1.1 — Advanced Security & Auth: Task List

**Goal:** Implement dynamic, school-scoped Role-Based Access Control (RBAC) system that allows each school to define custom roles and permissions without code changes.

**Dependency:** Phase 1.0 (Data Architecture) must be complete and stable.

Reference: [PRD.md](../PRD.md) — Section 5 (US6 & US13), Section 8 (Component Inventory), Section 16 (Acceptance Criteria US6).

---

## 1. Database Schema — Roles & Permissions

- [ ] Create migration: `php artisan make:migration create_roles_permissions_tables --no-interaction`
  - [ ] `roles` table:
    - [ ] `id` (UUID, PK)
    - [ ] `school_id` (UUID, FK → schools.id, CASCADE, indexed) — **school-scoped**
    - [ ] `name` (VARCHAR 255, not null) — e.g., "Instructor", "Student", "Department Head"
    - [ ] `slug` (VARCHAR 255, not null, unique per school via compound unique(school_id, slug))
    - [ ] `description` (TEXT, nullable)
    - [ ] `is_system_role` (BOOLEAN, default false) — marks seeded roles (e.g., "Admin", "Student", "Instructor") that cannot be deleted
    - [ ] timestamps
  - [ ] `permissions` table (global, not school-scoped):
    - [ ] `id` (UUID, PK)
    - [ ] `name` (VARCHAR 255, not null) — e.g., "create-course", "view-submissions"
    - [ ] `slug` (VARCHAR 255, not null, unique) — machine-friendly identifier
    - [ ] `description` (TEXT, nullable)
    - [ ] `category` (VARCHAR 100, nullable) — e.g., "courses", "submissions", "roles" (for UI grouping)
    - [ ] timestamps
  - [ ] `role_permission` pivot table:
    - [ ] `role_id` (UUID, FK → roles.id, CASCADE)
    - [ ] `permission_id` (UUID, FK → permissions.id, CASCADE)
    - [ ] Primary key: `(role_id, permission_id)`
  - [ ] `role_user` pivot table:
    - [ ] `role_id` (UUID, FK → roles.id, CASCADE)
    - [ ] `user_id` (UUID, FK → users.id, CASCADE)
    - [ ] Primary key: `(role_id, user_id)`
- [ ] Apply `HasUuid` trait to both `Role` model

## 2. Models & Relationships

- [ ] Generate `Role` model (`php artisan make:model Role --no-interaction`):
  - [ ] Apply `HasUuid` trait
  - [ ] Apply `BelongsToSchool` trait (school-scoped)
  - [ ] Add `$fillable` (`name`, `slug`, `description`, `is_system_role`)
  - [ ] Define relationships:
    - [ ] `belongsToMany(Permission::class)` via `role_permission` pivot
    - [ ] `belongsToMany(User::class)` via `role_user` pivot
  - [ ] Add accessor: `hasPermission($permission_slug): bool` — checks if role has this permission
- [ ] Generate `Permission` model:
  - [ ] Apply `HasUuid` trait (global, NOT BelongsToSchool)
  - [ ] Add `$fillable` (`name`, `slug`, `description`, `category`)
  - [ ] Define relationship: `belongsToMany(Role::class)` via `role_permission` pivot
- [ ] Update `User` model:
  - [ ] Add relationship: `belongsToMany(Role::class)` via `role_user` pivot
  - [ ] Add method: `hasRole($role_slug): bool` — case-insensitive
  - [ ] Add method: `hasPermission($permission_slug): bool` — check all roles for permission
  - [ ] Add method: `assignRole($role_or_slug): void` — assign role to user
  - [ ] Add method: `removeRole($role_or_slug): void` — remove role from user
  - [ ] Add method: `syncRoles($role_slugs): void` — replace all roles

## 3. Permission Definitions & Seeding

- [ ] Create `PermissionSeeder` defining all platform permissions:
  - [ ] **Course Management:** `create-course`, `view-course`, `edit-course`, `delete-course`
  - [ ] **Module Management:** `create-module`, `view-module`, `edit-module`, `delete-module`
  - [ ] **Lesson Management:** `create-lesson`, `view-lesson`, `edit-lesson`, `delete-lesson`
  - [ ] **Assignment Management:** `create-assignment`, `view-assignment`, `edit-assignment`, `delete-assignment`
  - [ ] **Submission Grading:** `view-submissions`, `grade-submissions`, `override-grade`
  - [ ] **Role Management:** `create-role`, `edit-role`, `delete-role`, `assign-permissions`
  - [ ] **User Management:** `manage-users`, `assign-roles`
  - [ ] **Analytics:** `view-analytics` (future use for Phase 3)
  - [ ] **School Settings:** `manage-school-settings`, `manage-billing`
  - [ ] Add each permission with category for UI grouping
- [ ] Create `DefaultRoleSeeder` to seed default roles per school:
  - [ ] **Admin** (school admin): All permissions except billing (reserved for Super Admin)
  - [ ] **Instructor**: Course/Module/Lesson/Assignment CRUD, grade-submissions, override-grade, view-analytics
  - [ ] **Student**: view-course, view-module, view-lesson, view-assignment (readonly), create/submit answers
  - [ ] Mark all three as `is_system_role = true` so they cannot be deleted
- [ ] Ensure both seeders run on `php artisan migrate:fresh --seed`

## 4. Authorization Policies

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
- [ ] Register policies in `AuthServiceProvider` (or auto-discovery if using Laravel 13 conventions)

## 5. Middleware & Gates

- [ ] Create `CheckPermission` middleware:
  - [ ] Accepts `$permission` parameter (e.g., `middleware('auth', 'permission:edit-course')`)
  - [ ] Checks: `auth()->user()->hasPermission($permission)` for current school
  - [ ] Returns 403 if denied
- [ ] Create `CheckRole` middleware:
  - [ ] Accepts `$role` parameter (e.g., `middleware('auth', 'role:instructor')`)
  - [ ] Checks: `auth()->user()->hasRole($role)` for current school
  - [ ] Returns 403 if denied
- [ ] Register gates in `AuthServiceProvider`:
  - [ ] `Gate::define('permission', fn ($user, $permission) => $user->hasPermission($permission))`
  - [ ] `Gate::define('role', fn ($user, $role) => $user->hasRole($role))`
  - [ ] Use: `@can('permission', 'edit-course')` in Blade templates
- [ ] Write tests proving:
  - [ ] User with "edit-course" permission can update courses
  - [ ] User without permission gets 403
  - [ ] Permission check respects current school (`school_id`)
  - [ ] Super Admin bypass (if applicable via `withoutGlobalScope`)

## 6. Livewire Components — School Admin Panel

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

## 7. Routes & Controller

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

## 8. Testing

- [ ] Create `RoleTest` (feature test):
  - [ ] School Admin can create a role
  - [ ] Role is automatically scoped to school (BelongsToSchool)
  - [ ] Unique slug constraint enforced per school (allow same slug in different schools)
  - [ ] Cannot delete system roles
- [ ] Create `PermissionTest` (feature test):
  - [ ] Super Admin seeds permissions globally
  - [ ] Schools reference same global permission pool
  - [ ] Permission count is consistent across schools
- [ ] Create `AuthorizationTest` (feature test):
  - [ ] User with permission:edit-course can update course
  - [ ] User without permission gets 403
  - [ ] Role assignment via UserRoleAssigner works
  - [ ] hasPermission() and hasRole() respect current school
- [ ] Create `MiddlewareTest` (feature test):
  - [ ] `CheckPermission` middleware blocks access correctly
  - [ ] `CheckRole` middleware blocks access correctly
  - [ ] Gates work in Blade templates (@can directive)
- [ ] Run full test suite: `php artisan test --compact`

## 9. Documentation & Verification

- [ ] Update docs/RBAC.md:
  - [ ] System roles vs custom roles distinction
  - [ ] Permission hierarchy/categories
  - [ ] How to programmatically check permissions
  - [ ] How to use middleware and gates in routes/views
- [ ] Run `php artisan migrate:fresh --seed` and verify:
  - [ ] All permissions seeded to database
  - [ ] Default roles created for first school
  - [ ] Admin user has Admin role
- [ ] Run `vendor/bin/pint --dirty --format agent` for code style
- [ ] Confirm no breaking changes to existing auth tests

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

