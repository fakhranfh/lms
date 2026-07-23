# RBAC (Role-Based Access Control)

## Overview

This application uses [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) as the underlying package, with `App\Models\Role` and `App\Models\Permission` extending Spatie's base models to add school-scoping.

## System Roles vs Custom Roles

- **System roles**: `Admin`, `Instructor`, `Student`, created per-school by `DefaultRoleSeeder` when a school is provisioned. These have `protected = true`.
- **Custom roles**: created by an Admin via `RoleCreate`/`RoleEdit`. Have `protected = false` and can be freely edited/deleted.
- The `protected` flag (boolean, on `Role`) blocks deletion: `Role::delete()`/`forceDelete()` throw an exception if `protected` is true, regardless of who is calling it.

## School-Scoped Roles

- `Role` has a nullable `school_id` column. A role with `school_id = null` is a **global** role (`Role::isGlobal(): bool`); platform-admin routes use these.
- Tenant (school) roles always carry their owning school's `school_id`, allowing two schools to each have their own "Admin" role without name collisions.
- `Role::findByName()` is overridden to only match global roles (`whereNull('school_id')`) — tenant role lookups must go through `Role::forSchool($schoolId)` (query scope) instead.
- Permissions are attached via a custom `belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id')` relation.

## Permission Categories

Permissions are seeded via a migration (`database/migrations/2026_07_16_075013_seed_permissions_and_default_roles.php`), not a seeder, since they must exist before any school/role rows are created. Each permission has `name`, `slug`, `label`, `group`.

| Group | Slugs |
|---|---|
| Courses | `courses.create`, `courses.view`, `courses.edit`, `courses.delete` |
| Modules | `modules.create`, `modules.view`, `modules.edit`, `modules.delete` |
| Lessons | `lessons.create`, `lessons.view`, `lessons.edit`, `lessons.delete` |
| Assignments | `assignments.create`, `assignments.view`, `assignments.edit`, `assignments.delete` |
| Submissions | `submissions.view`, `submissions.grade`, `submissions.override-grade` |
| Roles | `roles.create`, `roles.view`, `roles.edit`, `roles.delete`, `roles.assign-permissions` |
| Users | `users.manage`, `users.assign-roles` |
| Permissions | `permissions.view` |
| Analytics | `analytics.view` |
| Settings | `settings.school`, `settings.billing` |

## Default Roles Reference

Assigned per school by `DefaultRoleSeeder`:

- **Admin** — every permission except `settings.billing`.
- **Instructor** — full CRUD on courses/modules/lessons/assignments, plus `submissions.view`, `submissions.grade`, `submissions.override-grade`, and `analytics.view`. Permission list is defined by `App\Enums\RoleName::defaultPermissions()`.
- **Student** — view-only on courses/modules/lessons/assignments/submissions.

## Checking Permissions Programmatically

```php
$user->hasPermissionTo('assignments.create');
$user->hasRole('Admin');
```

Two gates are registered in `AppServiceProvider::boot()`:

```php
Gate::define('permission', fn (User $user, string $permission) => $user->hasPermissionTo($permission));
Gate::define('role', fn (User $user, string $role) => $user->hasRole($role));
```

These back Laravel's `can()`/`@can` helpers, so `$user->can('submissions.grade')` and `@can('permission', 'roles.view')` in Blade both resolve to the same check. Policies (`app/Policies/*`, all extending `BasePolicy`) use the same `hasPermissionTo`/`hasRole` calls internally rather than duplicating logic.

## Middleware & Gates in Routes

Spatie's own `permission`/`role` middleware aliases (registered in `bootstrap/app.php`) are used directly — no custom middleware wrapper exists:

```php
// routes/web/authenticated.php
Route::get('/assignments', AssignmentsIndex::class)->middleware('permission:assignments.view');
Route::get('/submissions/{submission}/override', OverrideScoreModal::class)->middleware('permission:submissions.override-grade');

// routes/web/admin.php
Route::middleware(['auth', 'role:Admin'])->group(function () { ... });
```

## Permission Assignment Matrix

Editing which permissions a role has happens on `RoleEdit` (`app/Livewire/Roles/RoleEdit.php`) as a checkbox grid grouped by category (one role at a time), not a separate cross-role matrix modal. A read-only cross-role overview exists at `App\Http\Controllers\PermissionController::index`.
