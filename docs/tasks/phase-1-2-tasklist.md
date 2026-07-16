# Phase 1.2 — Content Engine: Task List

**Goal:** Implement Course > Module > Lesson hierarchy with forced ordering, progress tracking, and instructor-friendly content management UI.

**Dependency:** Phase 1.0 (Data Architecture) and Phase 1.1 (RBAC) must be complete.

Reference: [PRD.md](../PRD.md) — Section 5 (US2, US5), Section 8 (Component Inventory), Section 16 (Acceptance Criteria US5).

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
    - [ ] `hasMany(Assignment::class)` — assignments in this lesson (Phase 1.3)
  - [x] Add method: `moveUp(): void`
  - [x] Add method: `moveDown(): void`
  - [x] Add method: `isCompletedBy($user): bool` — check if user completed lesson
  - [x] Add method: `markCompleteFor($user): void` — set completed_at for user
  - [x] Add accessor: `isPublished(): bool`
- [x] Create `LessonProgress` model (optional convenience model for lesson_user pivot):
  - [x] Alternatively, just use `User::lessons()` with pivot data

## 3. RBAC: Permission Definitions & Seeding

**From Phase 1.1 Deferred Tasks:**

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
  - [ ] Add each permission with label, group for UI grouping
- [ ] Create `DefaultRoleSeeder` to seed default roles per school:
  - [ ] **Admin** (school admin): All permissions except billing
  - [ ] **Instructor**: Course/Module/Lesson/Assignment CRUD, grade-submissions, override-grade, view-analytics
  - [ ] **Student**: view-course, view-module, view-lesson, view-assignment (readonly), view-submissions (own only)
  - [ ] Mark all as system roles so they cannot be deleted
- [ ] Ensure both seeders run on `php artisan migrate:fresh --seed`

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
- [ ] Create `CourseDemoSeeder`:
  - [ ] Seeds 2-3 demo courses per school (if first-time setup)
  - [ ] Each course has 2-3 modules
  - [ ] Each module has 3-5 lessons
  - [ ] Useful for testing and demo purposes

## 5. Livewire Components — Instructor Content Builder

- [ ] Create `CourseBuilder` Livewire component:
  - [ ] Display tree/outline view: Course > Modules > Lessons
  - [ ] Expandable/collapsible modules
  - [ ] Show lesson order and title
  - [ ] Action buttons per level:
    - [ ] Course: Edit, Publish, Delete, Add Module
    - [ ] Module: Edit, Move Up/Down, Delete, Add Lesson
    - [ ] Lesson: Edit, Move Up/Down, Delete
  - [ ] Search/filter by title
  - [ ] Drag-to-reorder (optional, requires JavaScript) or use up/down buttons (accessible)
  - [ ] Real-time update of order via wire model
- [ ] Create `CourseForm` Livewire component (modal):
  - [ ] Fields: title (required), description (textarea), slug (auto-generated)
  - [ ] Validation: title max 255, slug unique per school
  - [ ] Mode: Create or Edit
  - [ ] On submit: create/update course, refresh CourseBuilder
- [ ] Create `ModuleForm` Livewire component (modal):
  - [ ] Fields: title (required), description (textarea), order (hidden, auto-calculated)
  - [ ] Link to parent course
  - [ ] On submit: create/update module with correct order
- [ ] Create `LessonForm` Livewire component (modal):
  - [ ] Fields: 
    - [ ] title (required)
    - [ ] content (rich HTML editor, e.g., TinyMCE or Quill)
    - [ ] video_embed_url (optional, validate YouTube/Vimeo URL)
    - [ ] duration_minutes (optional, numeric)
  - [ ] Validation: title required, video_embed_url valid URL format if provided
  - [ ] On submit: create/update lesson with correct order
- [ ] Create `DeleteConfirmModal`:
  - [ ] Reusable modal for delete confirmations (course/module/lesson)
  - [ ] Show what will be deleted (cascade warning: deleting course deletes all modules/lessons)
  - [ ] Require typed confirmation (e.g., "type the title to confirm")
- [ ] Create `PublishToggle` component:
  - [ ] Simple button/toggle: "Publish Course" or "Unpublish"
  - [ ] Show publish status and date published
  - [ ] Note: publishing a course does NOT auto-publish modules/lessons (instructor must publish individually)

## 6. Routes & Controller

- [ ] Create `CourseController` with CRUD actions:
  - [ ] `index()` — list courses for authenticated school (GET /courses)
  - [ ] `create()` — show course form (GET /courses/create)
  - [ ] `store()` — create course (POST /courses)
  - [ ] `show($course)` — show course with module tree (GET /courses/{id})
  - [ ] `edit($course)` — show edit form (GET /courses/{id}/edit)
  - [ ] `update($course)` — update course (PATCH /courses/{id})
  - [ ] `destroy($course)` — soft delete course (DELETE /courses/{id})
  - [ ] All routes require `middleware('auth', 'permission:create-course')` etc.
- [ ] Create `ModuleController`:
  - [ ] `store($course)` — create module in course (POST /courses/{course_id}/modules)
  - [ ] `update($module)` — update module (PATCH /modules/{id})
  - [ ] `destroy($module)` — delete module (DELETE /modules/{id})
  - [ ] `moveUp($module)`, `moveDown($module)` — reorder (POST /modules/{id}/move-up)
- [ ] Create `LessonController`:
  - [ ] `store($module)` — create lesson (POST /modules/{module_id}/lessons)
  - [ ] `update($lesson)` — update lesson (PATCH /lessons/{id})
  - [ ] `destroy($lesson)` — delete lesson (DELETE /lessons/{id})
  - [ ] `moveUp($lesson)`, `moveDown($lesson)` — reorder
  - [ ] `publish($lesson)` — toggle publish status (POST /lessons/{id}/publish)
- [ ] Ensure all CRUD operations respect `BelongsToSchool` scope

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

- [ ] Create `CoursePolicy`:
  - [ ] `viewAny`: authenticated user in same school
  - [ ] `view`: authenticated + course in same school
  - [ ] `create`: permission:create-course
  - [ ] `update`: permission:edit-course + creator or admin
  - [ ] `delete`: permission:delete-course + no students enrolled yet
- [ ] Create `ModulePolicy`: similar to CoursePolicy
- [ ] Create `LessonPolicy`: similar
- [ ] Create `CourseFormRequest` / `ModuleFormRequest` / `LessonFormRequest`:
  - [ ] Validate required fields, string lengths, URL formats
  - [ ] Validate order is numeric and unique per parent

## 9. Testing

- [x] Create `CourseDatabaseTest` (feature test) — schema & model validation:
  - [x] Course can be created and associated with school
  - [x] Module belongs to course with enforced ordering
  - [x] Lesson belongs to module with enforced ordering
  - [x] Lesson completion tracking works correctly
  - [x] Course slug is unique per school
  - [x] Module and lesson ordering is enforced
  - [x] Cascade delete removes related records (7/7 tests passing)
- [ ] Create `CourseTest` (feature test):
  - [ ] Instructor can create/edit/delete course
  - [ ] Course is automatically scoped to school
  - [ ] Unpublished course is invisible to students (phase integration)
  - [ ] Course slug is unique per school (allow same slug in different schools)
- [ ] Create `ModuleTest`:
  - [ ] Module can be created with auto-incremented order
  - [ ] Move up/down correctly reorders siblings
  - [ ] Cascade delete: deleting course deletes modules
  - [ ] Order uniqueness constraint prevents duplicates
- [ ] Create `LessonTest`:
  - [ ] Lesson can be created/edited/deleted
  - [ ] Video URL validation rejects invalid formats
  - [ ] Lesson order within module is enforced
  - [ ] Cascade delete: deleting module deletes lessons
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
- [x] Run `php artisan test --compact` — database schema tests passing

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

