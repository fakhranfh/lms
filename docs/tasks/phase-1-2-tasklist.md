# Phase 1.2 — Content Engine: Task List

**Goal:** Implement Course > Module > Lesson hierarchy with forced ordering, progress tracking, and instructor-friendly content management UI.

**Dependency:** Phase 1.0 (Data Architecture) and Phase 1.1 (RBAC) must be complete.

Reference: [PRD.md](../PRD.md) — Section 5 (US2, US5), Section 8 (Component Inventory), Section 16 (Acceptance Criteria US5).

---

## Progress Summary

| Section | Status | Notes |
|---------|--------|-------|
| 1. Database Schema | ✅ Complete | 4 migrations, 4 tables created (courses, modules, lessons, lesson_user) |
| 2. Models & Relationships | ✅ Complete | Course, Module, Lesson models with full relationships & methods |
| 3. RBAC: Permissions & Roles | ✅ Complete | 36 permissions seeded via migration, 3 roles per school via seeder |
| 4. Factories & Seeders | ✅ Complete | CourseFactory, ModuleFactory, LessonFactory + ContentEngineSeeder (8 tests); RealisticCoursesSeeder added for non-dummy demo content |
| 5. Livewire Components | ✅ Complete | CoursesIndex, CourseBuilder, CourseForm, ModuleForm, LessonForm all built; move up/down, publish toggle (inline checkbox); course delete was broken (mismatched events) and is now fixed, search is now case-insensitive with debounce + skeleton loading |
| 6. Routes & Controller | ✅ Complete (architecture changed) | Implemented as full-page Livewire routes (`courses.*`, `modules.*`, `lessons.*`), not dedicated Controllers — see Resolved Decisions |
| 7. Student-Facing Views | ⏳ Not Started | LessonViewerComponent, CourseProgressComponent needed |
| 8. Validations & Policies | 🟡 Partial (architecture changed) | No dedicated Policy/FormRequest classes; authorization is inline `abort_unless(can(...))` per component, validation via Livewire `#[Validate]` attributes |
| 9. Testing | 🟡 Partial | Schema tests ✅ (7/7); Seeder tests ✅ (8/8); Livewire feature tests written for all 5 components but currently flaky — see Known Issues below |
| 10. Documentation | ⏳ Not Started | CONTENT_ENGINE.md documentation needed |

**Overall:** 6/10 sections complete, 2 partial. Next: fix test-suite flakiness (Known Issues) → Student-Facing Views → Documentation.

### Known Issues (found during 2026-07-17 audit)

- **Test isolation bug:** Running the Livewire course/module/lesson test files together throws `SQLSTATE[23505] duplicate key ... schools_domain_unique`. Faker-generated school domains collide across tests in the same run — needs a unique domain sequence in `SchoolFactory` or per-test `RefreshDatabase`/transaction isolation fix. **Still open.**
- **Permission test failures — root cause confirmed:** `test_user_cannot_*_without_permission` tests wrap `Livewire::test(...)` in `expectException(AuthorizationException::class)`, but `abort_unless($cond, 403)` throws Symfony's `HttpException`, and Livewire's `->call()`/mount cycle captures it as a response status rather than re-throwing to PHPUnit — the correct assertion is `->assertStatus(403)`. Confirmed by direct reproduction (see `CoursesIndexTest::test_cannot_delete_course_without_permission`, which uses `assertStatus(403)` and passes). **Fixed in the 3 new delete tests; the pre-existing tests across `CourseFormTest`/`ModuleFormTest`/`CourseBuilderTest`/`CoursesIndexTest` still use the wrong assertion and remain broken — retrofit is a follow-up task, not yet done.**
- **`LessonForm` duration validation:** `test_duration_must_be_numeric` fails with `Cannot assign string to property LessonForm::$durationMinutes of type ?int` — Livewire's property type coercion rejects non-numeric input before validation runs; needs the property typed as untyped/string with validation, or a custom rule. **Still open.**
- Two real bugs in `Module`/`Lesson` `moveUp`/`moveDown` ordering were found and fixed separately (see git history: reorder() fix for relation's baked-in orderBy, and the in-memory attribute mutation bug) — not re-listed here since already resolved.
- **Course delete button was completely non-functional** (found and fixed 2026-07-17): the button dispatched a `showDeleteModal` Livewire event that nothing listened for; the shared modal in `layouts/app.blade.php` listens for `open-delete-confirm` and its confirm button fires `delete-confirmed`, which `CoursesIndex` had no handler for at all. Fixed by matching the project's own code-generator convention (`LivewireIndexStubGenerator`): button now dispatches `open-delete-confirm` via plain Alpine `@click`, and `CoursesIndex` gained a `#[On('delete-confirmed')] destroy()` method backed by `CourseService`. **Resolved.**
- **Course search was case-sensitive** (found and fixed 2026-07-17): used SQL `like` instead of Postgres's case-insensitive `ilike` (app is confirmed pgsql-only via `config:show database.default`). **Resolved.**
- **Course search silently failed to re-render** (found and fixed 2026-07-17): the `@foreach` loop in `courses-index.blade.php` had no `wire:key`, so Livewire's DOM-morphing couldn't reliably reconcile the course grid when the filtered list changed — the classic missing-key pitfall. Added `wire:key` on both the course and module loops, plus debounced the search input (300ms) and added a skeleton-loading state (`wire:loading.delay.class.remove` scoped to `wire:target="search"`) so filtering has visible feedback. **Resolved.**

---

## 1. Database Schema — Course Hierarchy

- [x] Create migration: `php artisan make:migration create_courses_modules_lessons_tables --no-interaction`
  - [x] `courses` table:
    - [x] `id` (UUID, PK)
    - [x] `school_id` (UUID, FK → schools.id, CASCADE, indexed) — **school-scoped**
    - [x] `title` (VARCHAR 255, not null)
    - [x] `description` (TEXT, nullable)
    - [x] `created_by` (UUID, FK → users.id, nullable, SET NULL) — instructor who created course
    - [x] `is_published` (BOOLEAN, default false) — draft vs published state
    - [x] `slug` (VARCHAR 255, nullable, unique per school) — for URL-friendly access
    - [x] timestamps
  - [x] `modules` table:
    - [x] `id` (UUID, PK)
    - [x] `course_id` (UUID, FK → courses.id, CASCADE)
    - [x] `title` (VARCHAR 255, not null)
    - [x] `description` (TEXT, nullable)
    - [x] `order` (UNSIGNED INT, not null) — enforced ordering (1, 2, 3...)
    - [x] `is_published` (BOOLEAN, default false)
    - [x] timestamps
    - [x] **Unique constraint:** `(course_id, order)` — prevent duplicate ordering within a course
  - [x] `lessons` table:
    - [x] `id` (UUID, PK)
    - [x] `module_id` (UUID, FK → modules.id, CASCADE)
    - [x] `title` (VARCHAR 255, not null)
    - [x] `content` (LONGTEXT, nullable) — rich HTML/markdown lesson body
    - [x] `video_embed_url` (VARCHAR 500, nullable) — external video URL (YouTube, Vimeo, etc.)
    - [x] `order` (UNSIGNED INT, not null) — enforced ordering within module
    - [x] `is_published` (BOOLEAN, default false)
    - [x] `duration_minutes` (UNSIGNED INT, nullable) — estimated reading/viewing time
    - [x] timestamps
    - [x] **Unique constraint:** `(module_id, order)` — prevent duplicate ordering within a module
  - [x] `lesson_user` pivot table (progress tracking):
    - [x] `id` (BIGINT, PK) — auto-increment for Laravel pivot compatibility
    - [x] `lesson_id` (UUID, FK → lessons.id, CASCADE)
    - [x] `user_id` (UUID, FK → users.id, CASCADE)
    - [x] `completed_at` (TIMESTAMP, nullable) — when student marked lesson complete
    - [x] `last_viewed_at` (TIMESTAMP, nullable) — for resuming progress
    - [x] timestamps
    - [x] **Unique constraint:** `(lesson_id, user_id)` — each student can complete once
- [x] Apply `HasUuid` trait to `Course`, `Module`, `Lesson` models

## 2. Models & Relationships

- [x] Generate `Course` model:
  - [x] Apply `HasUuid` trait
  - [x] Apply `BelongsToSchool` trait (school-scoped)
  - [x] Add `$fillable` (`title`, `description`, `slug`, `is_published`)
  - [x] Define relationships:
    - [x] `hasMany(Module::class)` — ordered by `order` column
    - [x] `belongsTo(User::class, 'created_by')` — creator
  - [x] Add method: `publish(): void` — set `is_published = true`
  - [x] Add accessor: `isPublished(): bool`
  - [x] Add accessor: `modulesCount(): int` — count of modules
- [x] Generate `Module` model:
  - [x] Apply `HasUuid` trait
  - [x] Add `$fillable` (`title`, `description`, `order`, `is_published`)
  - [x] Define relationships:
    - [x] `belongsTo(Course::class)` — parent course
    - [x] `hasMany(Lesson::class)` — ordered by `order` column
  - [x] Add method: `moveUp(): void` — decrement order, swap with previous
  - [x] Add method: `moveDown(): void` — increment order, swap with next
  - [x] Add accessor: `lessonsCount(): int`
  - [x] Add method: `nextOrder(): int` — returns max(order) + 1 for new lessons
- [x] Generate `Lesson` model:
  - [x] Apply `HasUuid` trait
  - [x] Add `$fillable` (`title`, `content`, `video_embed_url`, `order`, `duration_minutes`, `is_published`)
  - [x] Define relationships:
    - [x] `belongsTo(Module::class)` — parent module
    - [x] `belongsToMany(User::class, 'lesson_user')` — students who completed
  - [x] Add method: `moveUp(): void`
  - [x] Add method: `moveDown(): void`
  - [x] Add method: `isCompletedBy($user): bool` — check if user completed lesson
  - [x] Add method: `markCompleteFor($user): void` — set completed_at for user
  - [x] Add accessor: `isPublished(): bool`
  - **Note:** `hasMany(Assignment::class)` deferred to Phase 1.3 (Assignment Implementation)
- [x] Create `LessonProgress` model (optional convenience model for lesson_user pivot):
  - [x] Alternatively, just use `User::lessons()` with pivot data

## 3. RBAC: Permission Definitions & Seeding

**Status:** ✅ COMPLETE  
**Implementation:** Migration + Seeder hybrid approach

- [x] Create permission definitions via migration (2026_07_16_075013_seed_permissions_and_default_roles.php):
  - [x] **Course Management:** `courses.create`, `courses.view`, `courses.edit`, `courses.delete`
  - [x] **Module Management:** `modules.create`, `modules.view`, `modules.edit`, `modules.delete`
  - [x] **Lesson Management:** `lessons.create`, `lessons.view`, `lessons.edit`, `lessons.delete`
  - [x] **Assignment Management:** `assignments.create`, `assignments.view`, `assignments.edit`, `assignments.delete`
  - [x] **Submission Grading:** `submissions.view`, `submissions.grade`, `submissions.override-grade`
  - [x] **Role Management:** `roles.create`, `roles.view`, `roles.edit`, `roles.delete`, `roles.assign-permissions`
  - [x] **User Management:** `users.manage`, `users.assign-roles`
  - [x] **Permissions:** `permissions.view`
  - [x] **Analytics:** `analytics.view`
  - [x] **School Settings:** `settings.school`, `settings.billing`
  - [x] Each permission has slug, label, and group for UI organization (36 total permissions)
- [x] Create `DefaultRoleSeeder` to seed default roles per school:
  - [x] **Admin** (school admin): 35 permissions (all except `settings.billing`)
  - [x] **Instructor**: 20 permissions (Course/Module/Lesson/Assignment CRUD + grading + analytics)
  - [x] **Student**: 5 permissions (view-only: courses, modules, lessons, assignments, submissions)
  - [x] Roles are school-scoped with slug: admin, instructor, student
- [x] Ensure permissions seed via migration and roles seed via DefaultRoleSeeder on `php artisan migrate:fresh --seed`
- [x] Fix role slug unique constraint: changed from global unique to per-school `(school_id, slug)` unique
- [x] All tests passing (260+ tests)

## 4. Factories & Seeders (Content Engine)

- [x] Create `CourseFactory`:
  - [x] Generate random title, description, slug
  - [x] Associate with school via `school_id` (BelongsToSchool handles auto-fill)
  - [x] Associate with instructor as creator
- [x] Create `ModuleFactory`:
  - [x] Generate random title, description
  - [x] Accept course_id and order parameters
  - [x] Randomize is_published
- [x] Create `LessonFactory`:
  - [x] Generate random title, content (using faker), optional video URL
  - [x] Accept module_id and order parameters
  - [x] Randomize is_published, duration_minutes (5-60)
- [x] Create `ContentEngineSeeder`:
  - [x] Seeds 3 demo courses per school (published/unpublished variants)
  - [x] Each course has 2-3 modules with proper ordering
  - [x] Each module has 1-2 lessons with proper ordering
  - [x] Includes optional video URLs on some lessons
  - [x] Automatic instructor assignment from school users
  - [x] Integrated into DatabaseSeeder
  - [x] 8 comprehensive tests with 122 assertions (all passing)

## 5. Livewire Components — Instructor Content Builder

- [x] Create `CourseBuilder` Livewire component (`app/Livewire/Courses/CourseBuilder.php`):
  - [x] Display tree/outline view: Course > Modules > Lessons
  - [x] Expandable/collapsible modules (`toggleModule()`)
  - [x] Show lesson order and title
  - [x] Action buttons per level:
    - [x] Course: Edit, Delete, Add Module (Publish handled via `CourseForm` checkbox, not a separate button)
    - [x] Module: Edit, Move Up/Down, Delete, Add Lesson
    - [x] Lesson: Edit, Move Up/Down, Delete
  - [ ] Search/filter by title — not implemented (course list search exists in `CoursesIndex`, not within a single course's module/lesson tree)
  - [x] Reorder via up/down buttons (accessible); drag-to-reorder not implemented
  - [x] Real-time update of order via Livewire `moveModuleUp/Down`, `moveLessonUp/Down`
- [x] Create `CourseForm` Livewire component (full-page, not modal — see Resolved Decisions):
  - [x] Fields: title (required), description (textarea), slug (auto-generated from title), isPublished (checkbox)
  - [x] Validation: title max 255, slug unique per school (checked in `save()` via `CourseService::slugExists`)
  - [x] Mode: Create or Edit
  - [x] On submit: create/update course, redirect to `courses.show`
- [x] Create `ModuleForm` Livewire component (full-page):
  - [x] Fields: title (required), description (textarea), isPublished (checkbox); order auto-calculated by `ModuleService`/`ModuleRepository::getNextOrder()`
  - [x] Link to parent course
  - [x] On submit: create/update module with correct order
- [x] Create `LessonForm` Livewire component (full-page):
  - [x] Fields:
    - [x] title (required)
    - [x] content (plain textarea — HTML tags supported manually; rich editor like TinyMCE/Quill **not** integrated)
    - [x] video_embed_url (optional)
    - [x] duration_minutes (optional, numeric — see Known Issues for a coercion bug on invalid input)
  - [x] Validation: title required; video_embed_url format validation not confirmed as strict YouTube/Vimeo pattern (accepts any URL)
  - [x] On submit: create/update lesson with correct order
- [x] Delete confirmation — two patterns coexist in this codebase:
  - [x] `CourseBuilder` (module/lesson delete): local Alpine `x-data` + its own modal at the bottom of `course-builder.blade.php`, calling `$wire.call('confirmDelete', deleteType, deleteId)` directly. Shows item name being deleted.
  - [x] `CoursesIndex` (course delete): shared global modal in `layouts/app.blade.php`, wired via `@click="$dispatch('open-delete-confirm', { id, name, type })"` → `#[On('delete-confirmed')] destroy()`. **This was broken until 2026-07-17** — the button dispatched a mismatched event name (`showDeleteModal`) that nothing listened for, and the component had no delete method at all. Fixed to match the project's own RSC code-generator convention. The modal now also shows the item name and a cascade warning (see below) since the dispatch was extended with `name`/`type`.
  - [x] Cascade warning text added (2026-07-17): shared modal now shows "This will also delete all of its modules and lessons." when `type === 'courses'`; `CourseBuilder`'s modal already had a module→lessons warning, now styled consistently in `text-error`.
  - [x] Typed-confirmation requirement added (2026-07-17) to both patterns: Delete button is disabled (`:disabled`, dimmed) until the user types the exact item name/title into a confirmation input. Shared modal only gates this when a `name` is passed in the dispatch (backward-compatible with the RSC-generated stub, which doesn't pass one).
- [x] Publish toggle — implemented as an `isPublished` checkbox inline in `CourseForm`/`ModuleForm`/`LessonForm`, not a standalone `PublishToggle` component:
  - [x] "Date published" now shown (2026-07-17): added a `published_at` timestamp column to `courses`/`modules`/`lessons` (migration `2026_07_17_025953_...`) plus a shared `App\Models\Concerns\TracksPublishedAt` trait that sets/clears it automatically via a `saving` hook whenever `is_published` changes (covers create, `update()`, and the repository `publish()`/`unpublish()` methods — no call-site changes needed). Displayed next to each "Published" badge in `courses-index.blade.php` and `course-builder.blade.php` (course/module/lesson level). 7 new tests in `PublishedAtTrackingTest` cover set-on-create, stays-null-as-draft, set-on-later-publish, cleared-on-unpublish, and that unrelated field updates don't touch it.
  - [x] Publishing a course does not cascade to modules/lessons (each has its own independent flag, as designed)

## 6. Routes & Controller

**Status:** ✅ Complete, but via a different architecture than originally planned — see [Resolved Decisions](#routing-architecture).
No `CourseController`/`ModuleController`/`LessonController` exist. Instead, routes bind directly to full-page Livewire components (`Route::get(...)->name(...)` → `App\Livewire\Courses\*`), and create/update/delete/reorder all happen through Livewire actions on those components rather than separate REST endpoints.

- [x] Course routes (`courses.index`, `courses.create`, `courses.show`, `courses.edit`) → `CoursesIndex`, `CourseForm`, `CourseBuilder`
  - [x] List courses for authenticated school (`courses.index`)
  - [x] Create/edit course form (`courses.create`, `courses.edit`)
  - [x] Show course with module tree (`courses.show` → `CourseBuilder`)
  - [x] Delete via Livewire `confirmDelete()` action (not a DELETE route)
  - [x] Authorization inline per-component, not route middleware (`abort_unless(can(...))`)
- [x] Module routes (`modules.create`, `modules.edit`) → `ModuleForm`
  - [x] Create/update via `ModuleForm::save()`
  - [x] Delete via `CourseBuilder::confirmDelete()`
  - [x] Move up/down via `CourseBuilder::moveModuleUp/Down()` (Livewire actions, not routes)
- [x] Lesson routes (`lessons.create`, `lessons.edit`) → `LessonForm`
  - [x] Create/update via `LessonForm::save()`
  - [x] Delete via `CourseBuilder::confirmDelete()`
  - [x] Move up/down via `CourseBuilder::moveLessonUp/Down()`
  - [ ] No standalone `publish($lesson)` toggle route — publish state is set via the `isPublished` checkbox in `LessonForm`
- [x] All CRUD operations respect `BelongsToSchool` scope (checked via `CurrentSchool` + `school_id` comparison in each component's `mount()`)

## 7. Student-Facing Views — Lesson Viewer

- [ ] Create `LessonViewerComponent` Livewire component:
  - [ ] Display lesson content (title, HTML body, video embed)
  - [ ] Show breadcrumb: Course > Module > Lesson
  - [ ] Display progress indicator (X of Y lessons completed in this module)
  - [ ] "Mark Complete" button (only if lesson not yet completed)
  - [ ] Navigation: "Previous Lesson" / "Next Lesson" buttons
  - [ ] Show duration estimate if available
  - [ ] Side panel (optional): Collapsible course outline/navigation tree
- [ ] Create `CourseProgressComponent`:
  - [ ] Show overall course completion % (completed_lessons / total_lessons)
  - [ ] Per-module progress bars
  - [ ] List of lessons with completion checkmarks
- [ ] Routes:
  - [ ] GET `/lessons/{lesson_id}` — show lesson viewer
  - [ ] POST `/lessons/{lesson_id}/mark-complete` — mark lesson complete (Livewire wire:click)

## 8. Validations & Authorization

**Status:** 🟡 Partial, different architecture than planned. No Policy or FormRequest classes exist for this domain (`app/Policies/` only has `UserPolicy`). Authorization and validation are both handled inline in the Livewire components instead:

- [x] Course/Module/Lesson authorization — implemented as inline `abort_unless(auth()->user()->can('courses.edit') && $course->school_id === $schoolId, 403)` style checks in each component's `mount()`, not as `CoursePolicy`/`ModulePolicy`/`LessonPolicy` classes
  - [x] School-scoping check present on every form/builder component
  - [ ] No explicit "no students enrolled yet" delete guard on courses
- [x] Field validation — implemented via Livewire `#[Validate('required|string|max:255')]` attributes directly on component properties, not `FormRequest` classes
  - [x] Required fields, string lengths enforced
  - [ ] `video_embed_url` accepts any URL — no YouTube/Vimeo-specific format rule
  - [x] Order uniqueness per parent enforced at the DB layer (unique constraint), not duplicated in form validation
  - [ ] `LessonForm::$durationMinutes` typed `?int` causes a hard Livewire type-coercion error (not a graceful validation message) when non-numeric input is submitted — see Known Issues

## 9. Testing

- [x] Create `CourseDatabaseTest` (feature test) — schema & model validation:
  - [x] Course can be created and associated with school
  - [x] Module belongs to course with enforced ordering
  - [x] Lesson belongs to module with enforced ordering
  - [x] Lesson completion tracking works correctly
  - [x] Course slug is unique per school
  - [x] Module and lesson ordering is enforced
  - [x] Cascade delete removes related records (7/7 tests passing)
- [x] `CourseFormTest`, `ModuleFormTest`, `LessonFormTest`, `CourseBuilderTest`, `CoursesIndexTest` (Livewire feature tests, `tests/Feature/Livewire/Courses/`) cover most of what `CourseTest`/`ModuleTest`/`LessonTest` below describe, via the actual components rather than controllers:
  - [x] Instructor can create/edit/delete course/module/lesson
  - [x] Course/module/lesson scoped to school; cross-school access blocked
  - [x] Course slug is unique per school
  - [x] Module/lesson order auto-increments on create
  - 🟡 Currently flaky when run as a full suite — see Known Issues (schools_domain_unique collisions, some permission-check test failures)
- [x] `ModuleOrderingTest`, `CourseBuilderMoveOrderTest` (`tests/Feature/`) — added 2026-07-17 to cover move up/down correctness:
  - [x] Move up/down correctly reorders adjacent siblings only (regression test for a bug where moveUp on the bottom item jumped it to the top)
  - [x] Repeated move calls don't corrupt order values (regression test for an in-memory attribute mutation bug)
  - [ ] Cascade delete tests (deleting course/module removes children) not yet written
- [x] `CoursesIndexTest` additions (2026-07-17): `test_search_is_case_insensitive`, `test_can_delete_course`, `test_cannot_delete_course_without_permission` (uses `assertStatus(403)`, not `expectException` — see Known Issues), `test_cannot_delete_course_from_different_school`. All pass on a fresh test DB.
- [x] `PublishedAtTrackingTest` (`tests/Feature/`) — added 2026-07-17, 7 tests covering the new `published_at` auto-tracking trait across Course/Module/Lesson (set on publish-at-create, stays null as draft, set on later publish, cleared on unpublish, unrelated updates don't touch it). All pass.
- [ ] Create `ProgressTrackingTest`:
  - [ ] Student can mark lesson complete
  - [ ] Completion timestamp is recorded
  - [ ] `isCompletedBy()` returns correct status
  - [ ] Progress % calculation is accurate
- [ ] Create `LessonViewerTest`:
  - [ ] Student can view published lesson
  - [ ] Unpublished lesson returns 403 for students (published view only)
  - [ ] "Mark Complete" button visible only if not completed
  - [ ] Navigation to previous/next lesson works
- [x] Run `php artisan test --compact` — database schema tests passing; Livewire component tests written but need the flakiness fix noted in Known Issues before they can be trusted in CI

## 10. Documentation & Verification

- [ ] Update docs/CONTENT_ENGINE.md:
  - [ ] Course > Module > Lesson hierarchy explanation
  - [ ] How ordering works and cascade behavior
  - [ ] How to bulk import courses (if applicable)
  - [ ] Lesson viewer UX explanation
- [ ] Run `php artisan migrate:fresh --seed` and verify:
  - [ ] All permissions seeded to database (from Section 3 PermissionSeeder)
  - [ ] Default roles created for schools (Admin, Instructor, Student from DefaultRoleSeeder)
  - [ ] Schema matches ERD
  - [ ] Demo courses seeded and visible
- [ ] Seed demo courses and verify structure in browser
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Confirm Livewire components render without JS errors

---

## Resolved Decisions

### Routing Architecture
**Decision:** Use full-page Livewire components bound directly to routes instead of traditional Controllers + FormRequests + Policies.

**Rationale:**
- Matches the pattern already used elsewhere in the app (`app/Livewire/Courses/*`)
- Avoids duplicating validation/authorization logic across a Controller and a Livewire form component
- Fewer files for the same CRUD surface (one component per screen instead of Controller + FormRequest + Policy + Blade view)

**Implementation:**
- `courses.index|create|show|edit`, `modules.create|edit`, `lessons.create|edit` route names each point directly at a Livewire component (`CoursesIndex`, `CourseForm`, `CourseBuilder`, `ModuleForm`, `LessonForm`)
- Create/update/delete/reorder are Livewire actions (`save()`, `confirmDelete()`, `moveModuleUp()`, etc.), not REST-style Controller methods
- Authorization is inline `abort_unless(auth()->user()->can(...) && $model->school_id === $schoolId, 403)` in each component's `mount()`, replacing what would have been Policy classes
- Validation is inline `#[Validate(...)]` attributes on component properties, replacing what would have been FormRequest classes
- **Trade-off accepted:** less separation of concerns than the originally planned MVC layout; acceptable here since each component maps 1:1 to a single screen with no reuse pressure

### Ordering Strategy
**Decision:** Use numeric `order` column with unique constraint per parent; move up/down swaps order values.

**Rationale:**
- Deterministic and easy to understand (order 1, 2, 3...)
- Avoids gaps or reordering complexity
- Supports drag-to-reorder if needed later
- Index on (parent_id, order) for fast queries

**Implementation:**
- `order` is UNSIGNED INT, 1-indexed
- On create: set to `max(order) + 1` for parent
- On delete: no reordering needed (order gaps are OK, queries use ORDER BY order)
- Move up/down: swap order values with sibling

### Lesson Completion Tracking
**Decision:** Use `lesson_user` pivot with `completed_at` timestamp; optional `last_viewed_at` for resume.

**Rationale:**
- Simple boolean (completed/not) plus timestamp for when
- `last_viewed_at` allows UI to show resume point
- Indexed on both user_id and lesson_id for fast lookups
- Supports future analytics

**Implementation:**
- Unique constraint (lesson_id, user_id) prevents duplicate entries
- Query: `Lesson::with('users')->get()` returns lessons with completion data
- Or via User: `$user->lessons()->with('pivot')->get()` returns completed lessons

### Publishing Strategy
**Decision:** Courses, Modules, and Lessons each have independent `is_published` flag.

**Rationale:**
- Instructors can work on unpublished content without affecting live courses
- Allows phased rollout (draft → review → publish)
- Student views only see published lessons (enforce via scope or policy)

**Implementation:**
- Student routes apply scope: `.where('is_published', true)` on lessons
- Or use `LocalScope` in Lesson model with conditional check
- Instructor routes bypass this scope to view/edit drafts

---

## Demo Mode Restrictions (Phase 2.1 Follow-up)

**Added 2026-07-17:** Implemented read-only mode for demo school accounts on tier management page:
- [x] Demo accounts can view tiers but cannot initiate upgrades/downgrades
- [x] Banner displayed: "This is a demo account. Tier management is read-only."
- [x] Upgrade/Downgrade buttons disabled with explanatory text
- [x] `TierChangeController::show()` detects demo mode via `DemoLmsAccess` table
- [x] `TierChangeController::initiate()` blocks demo tier changes with 403 status
- [x] 2 comprehensive tests added to `TierChangeFlowTest` (demo prevention + UI verification)
- [x] Admin pricing tier management pages remain unrestricted

