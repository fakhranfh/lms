# Content Engine — Course > Module > Lesson Hierarchy

This document explains how the Content Engine works: the hierarchical structure of courses, modules, and lessons, how ordering is managed, cascade behaviors, and the student-facing lesson viewer UX.

---

## Table of Contents

1. [Overview](#overview)
2. [Data Hierarchy](#data-hierarchy)
3. [Ordering & Reordering](#ordering--reordering)
4. [Cascade Behavior](#cascade-behavior)
5. [Publishing & Visibility](#publishing--visibility)
6. [Lesson Viewer UX](#lesson-viewer-ux)
7. [Progress Tracking](#progress-tracking)
8. [Instructor Content Management](#instructor-content-management)
9. [Bulk Import (Future)](#bulk-import-future)
10. [Multi-Material System](#multi-material-system)

---

## Overview

The Content Engine is the core of the LMS curriculum delivery system. It provides:

- **Hierarchical organization:** Courses contain Modules, which contain Lessons
- **Forced ordering:** All items within a parent are strictly ordered (1, 2, 3...)
- **Publishing control:** Each course, module, and lesson can be independently published or kept as draft
- **Progress tracking:** Students can mark lessons complete; completion timestamps are recorded
- **Instructor tools:** Drag-free, button-based reordering; inline edit/delete; real-time Livewire updates
- **Student-facing viewer:** Lesson content, breadcrumbs, progress indicators, next/previous navigation

---

## Data Hierarchy

### Course

The top-level container. Every school has its own set of courses.

**Database:** `courses` table
- `id` (UUID, PK)
- `school_id` (UUID, FK → schools)
- `title` (varchar 255)
- `description` (text, nullable)
- `slug` (varchar 255, unique per school)
- `is_published` (boolean, default false)
- `published_at` (timestamp, nullable) — auto-set when course is published
- `created_by` (UUID, FK → users, nullable) — instructor who created the course
- timestamps

**Relationships:**
- `hasMany(Module)` — all modules in this course, ordered by `order` column
- `belongsTo(User, 'created_by')` — the instructor creator

### Module

A section within a course. Modules group related lessons together.

**Database:** `modules` table
- `id` (UUID, PK)
- `course_id` (UUID, FK → courses)
- `title` (varchar 255)
- `description` (text, nullable)
- `order` (unsigned int) — position within course (1, 2, 3...)
- `is_published` (boolean, default false)
- `published_at` (timestamp, nullable) — auto-set when module is published
- timestamps
- **Unique constraint:** `(course_id, order)` — no duplicate orders per course

**Relationships:**
- `belongsTo(Course)` — parent course
- `hasMany(Lesson)` — all lessons in this module, ordered by `order` column

### Lesson

The smallest unit of content. A lesson contains text/HTML content, duration estimate, and one or more attached materials (see [Multi-Material System](#multi-material-system)).

**Database:** `lessons` table
- `id` (UUID, PK)
- `module_id` (UUID, FK → modules)
- `title` (varchar 255)
- `content` (longtext, nullable) — HTML/markdown body
- `order` (unsigned int) — position within module (1, 2, 3...)
- `is_published` (boolean, default false)
- `published_at` (timestamp, nullable) — auto-set when lesson is published
- `duration_minutes` (unsigned int, nullable) — estimated reading/viewing time
- timestamps
- **Unique constraint:** `(module_id, order)` — no duplicate orders per module

**Relationships:**
- `belongsTo(Module)` — parent module
- `belongsToMany(User)` via `lesson_user` pivot — students who have completed

### Progress Pivot Table

Tracks student completion and viewing state.

**Database:** `lesson_user` pivot table
- `id` (bigint, PK) — auto-increment for Laravel pivot compatibility
- `lesson_id` (UUID, FK → lessons, CASCADE)
- `user_id` (UUID, FK → users, CASCADE)
- `completed_at` (timestamp, nullable) — when student marked complete
- `last_viewed_at` (timestamp, nullable) — when student last viewed (for resume)
- timestamps
- **Unique constraint:** `(lesson_id, user_id)` — each student completes at most once per lesson

---

## Ordering & Reordering

### Numeric Ordering

All items (modules and lessons) use a numeric `order` column:
- Order is 1-indexed within its parent
- Order is strictly enforced: no gaps, no duplicates per parent
- Unique constraint `(parent_id, order)` prevents violations at the database level

### Creating New Items

When a new module is added to a course:
```php
$newOrder = $course->modules()->max('order') + 1;  // e.g., 4 if 3 modules exist
$module = Module::create([
    'course_id' => $course->id,
    'order' => $newOrder,
    // ...
]);
```

The `nextOrder()` method is available on the parent for convenience:
```php
$lesson = Lesson::create([
    'module_id' => $module->id,
    'order' => $module->nextOrder(),
    // ...
]);
```

### Moving Items Up/Down

The `moveUp()` and `moveDown()` methods swap order values with adjacent siblings:

```php
$lesson = Lesson::find($id);
$lesson->moveUp();   // Decrements order, swaps with previous lesson
$lesson->moveDown(); // Increments order, swaps with next lesson
```

**Mechanics:**
- `moveUp()`: Finds the sibling with `order = $this->order - 1`, swaps values using a database transaction
- `moveDown()`: Finds the sibling with `order = $this->order + 1`, swaps values using a database transaction
- If already at the top (moveUp) or bottom (moveDown), the move is a no-op

**Example flow:**
```
Initial state:
  Lesson 1 (order=1)
  Lesson 2 (order=2)
  Lesson 3 (order=3)

After Lesson 1->moveDown():
  Lesson 2 (order=1)
  Lesson 1 (order=2)
  Lesson 3 (order=3)
```

### UI for Reordering

The instructor interface (`CourseBuilder` component) displays up/down buttons for each item:
- **Up button:** Disabled if item is already at position 1
- **Down button:** Disabled if item is already at the last position
- Click triggers a Livewire action: `moveModuleUp($id)`, `moveLessonDown($id)`, etc.
- Real-time re-render after move completes

---

## Cascade Behavior

### Deleting a Course

When a course is deleted:
1. **Cascade delete at DB level:** All modules in that course are deleted (foreign key CASCADE)
2. **Cascade to lessons:** When modules delete, all lessons in those modules are also deleted (CASCADE)
3. **Cascade to progress:** When lessons delete, all `lesson_user` progress records are deleted (CASCADE)

**Database cascades:**
- `modules.course_id` has `CASCADE` delete
- `lessons.module_id` has `CASCADE` delete
- `lesson_user.lesson_id` has `CASCADE` delete

**Delete guard (Instructor UI):**
- The course delete button in `CoursesIndex` calls `CourseService::hasStudentProgress()` before allowing delete
- If any student has viewed or completed lessons, delete is blocked with an error message
- Message: "This course cannot be deleted because students have already started lessons. Contact support if you need to remove this course."
- This prevents data loss from accidentally deleting courses with active student engagement

### Deleting a Module

When a module is deleted:
1. **Cascade delete:** All lessons in that module are deleted (foreign key CASCADE)
2. **Cascade to progress:** All progress records for those lessons are deleted (CASCADE)
3. **Order is NOT recomputed:** Remaining modules in the course keep their original order values; gaps are acceptable

**Note:** You can safely delete a module in the middle without renumbering. For example:
```
After deleting Module 2 from a 3-module course:
  Module 1 (order=1)
  Module 3 (order=3)    # order stays 3, not renumbered to 2
```

### Deleting a Lesson

When a lesson is deleted:
1. **Delete progress records:** All `lesson_user` rows for that lesson are deleted (CASCADE)
2. **Order is NOT recomputed:** Remaining lessons in the module keep their original order

---

## Publishing & Visibility

### Independent Publishing

Courses, modules, and lessons each have an independent `is_published` flag.

- Publishing a course **does not** automatically publish its modules or lessons
- Modules and lessons must be explicitly published
- Students only see published lessons (enforced in the lesson viewer routes and database queries)

**Typical workflow:**
1. Instructor creates a course (draft, `is_published = false`)
2. Instructor adds modules and lessons (all draft)
3. Instructor reviews and publishes modules and lessons individually
4. Once all key content is published, instructor publishes the course itself
5. Course appears in student's course list; students can only access published lessons

### Published Timestamp

When an item is published, a `published_at` timestamp is automatically recorded:

```php
$course = Course::create([
    'title' => 'Intro to PHP',
    'is_published' => true,  // Published on create
]);
// published_at is automatically set to now()

$course->is_published = false;
$course->save();
// published_at is automatically cleared to null
```

This uses the `App\Models\Concerns\TracksPublishedAt` trait, applied to `Course`, `Module`, and `Lesson` models.

**Displayed in UI:** The `published_at` timestamp appears next to the "Published" badge in the course list and course builder, showing when content was last made live.

---

## Lesson Viewer UX

The student-facing lesson viewer is a full-page Livewire component (`LessonViewerComponent`) that provides:

### Layout

```
[Sidebar]                  [Main Content]
  Course Outline           - Breadcrumb: Course > Module > Lesson
  > Module 1               - Lesson Title
    > Lesson 1 (current)   - Video (if embedded)
    > Lesson 2             - Lesson Content (HTML/text)
  > Module 2               - Duration (if available)
  > Module 3               - [Mark Complete] button
                           - [Previous Lesson] [Next Lesson] buttons
```

### Breadcrumb Navigation

Shows the full path: **Course Title** > **Module Title** > **Lesson Title**

- Course title is clickable → returns to course view
- Module title is clickable → (optional; currently navigates to course)

### Course Outline Sidebar

A collapsible panel on the left showing the entire course structure:
- All modules listed
- Each module shows its lessons
- Current lesson is highlighted
- Clicking a lesson in the sidebar navigates to it
- Can collapse/expand to save space

### Progress Indicator

Shows progress within the current module:
- **"3 of 5 lessons completed in Module Name"**
- Helps student understand how much content remains

### Mark Complete Button

- Visible only if the student has not yet completed the lesson
- Clicking saves a completion timestamp to the `lesson_user.completed_at` column
- Button disappears after completion; a checkmark appears instead
- Also updates the progress indicator immediately

### Navigation Buttons

**Previous Lesson:**
- Disabled if this is the first lesson in the module
- Clicking navigates to the previous lesson in the module

**Next Lesson:**
- Disabled if this is the last lesson in the module
- Clicking navigates to the next lesson (in reading order)
- If at the last lesson of a module but there are more modules, skip to the first lesson of the next module (future enhancement)

### Duration Display

If `duration_minutes` is set, displays:
- **"Estimated time: 15 minutes"**
- Helps students plan their study time

### Responsive Behavior

- On mobile: Sidebar collapses by default; toggle button to expand
- On desktop: Sidebar visible by default
- Content area is full-width when sidebar is collapsed

---

## Progress Tracking

### Recording Completion

When a student clicks "Mark Complete" on a lesson:

1. Livewire action `markComplete($lessonId)` is triggered
2. `LessonViewerComponent::markComplete()` calls `$lesson->markCompleteFor($user)`
3. Method creates or updates the `lesson_user` pivot record:
   ```php
   $lesson->users()->attach($user->id, [
       'completed_at' => now(),
       'last_viewed_at' => now(),
   ]);
   ```
4. UI immediately updates:
   - Mark Complete button disappears
   - Checkmark appears
   - Progress indicator recalculates

### Querying Completion

**Check if a student completed a lesson:**
```php
$lesson->isCompletedBy($user);  // Returns bool
```

**Get all completed lessons for a user:**
```php
$user->lessons()->where('is_published', true)->get();  // Returns collection with pivot data
```

**Get completion %, overall course:**
```php
$completedCount = $student->lessons()
    ->wherePivot('completed_at', '!=', null)
    ->where('course_id', $courseId)
    ->count();
$totalCount = Lesson::where('course_id', $courseId)
    ->where('is_published', true)
    ->count();
$percentComplete = round(($completedCount / $totalCount) * 100);
```

### Resuming Progress

The `last_viewed_at` timestamp is updated each time a student views a lesson, allowing future features like:
- "Resume from where you left off" button
- Last-viewed indicator in the outline sidebar
- Time-since-last-viewed analytics

---

## Instructor Content Management

### Course Builder Interface

The instructor's main interface for managing course structure is the `CourseBuilder` Livewire component, accessible at `/courses/{course_id}`.

**Features:**
- **Expandable/collapsible modules:** Click module title to toggle visibility of its lessons
- **Inline edit buttons:** Click "Edit" on a course/module/lesson to open the form
- **Move buttons:** Up/down buttons for modules and lessons
- **Delete buttons:** Delete with cascade confirmation modal
- **Add buttons:**
  - Course level: "Add Module"
  - Module level: "Add Lesson"
- **Publish toggle:** Checkbox next to each item; changes reflect immediately
- **Published timestamp:** Shows when content was last published

### Form Components

Create/edit forms are full-page Livewire components:

**CourseForm** (`/courses/create`, `/courses/{id}/edit`):
- Title (required, max 255)
- Description (textarea, nullable)
- Slug (auto-generated from title, but editable; unique per school)
- Is Published (checkbox)

**ModuleForm** (`/modules/create`, `/modules/{id}/edit`):
- Title (required, max 255)
- Description (textarea, nullable)
- Is Published (checkbox)
- Order is auto-calculated and not editable in the form

**LessonForm** (`/lessons/create`, `/lessons/{id}/edit`):
- Title (required, max 255)
- Content (textarea, HTML supported; rich editor not integrated)
- Video Embed URL (optional; validates YouTube/Vimeo only)
- Duration Minutes (optional, numeric)
- Is Published (checkbox)

### Delete Confirmation

Two delete patterns coexist in the codebase:

**In CourseBuilder (module/lesson delete):**
- Local Alpine modal at bottom of component
- Requires typing the exact item name to confirm
- Shows cascade warning: "This will also delete all of its lessons"

**In CoursesIndex (course delete):**
- Shared global modal in `layouts/app.blade.php`
- Requires typing the exact item name to confirm
- Shows cascade warning: "This will also delete all of its modules and lessons"
- Delete guard prevents deletion if students have progress

---

## Bulk Import (Future)

Currently, there is no bulk import feature. Future phases may add:
- CSV import for courses and modules
- Batch lesson creation from spreadsheet
- Course template cloning

---

## Authorization & Permissions

All content management actions are controlled by these permissions:

| Permission | Description |
|---|---|
| `courses.create` | Create a new course |
| `courses.view` | View courses (course list) |
| `courses.edit` | Edit a course or manage its modules/lessons |
| `courses.delete` | Delete a course |
| `modules.create` | Create a module (admin) |
| `modules.view` | View modules (admin) |
| `modules.edit` | Edit modules |
| `modules.delete` | Delete modules |
| `lessons.create` | Create a lesson (admin) |
| `lessons.view` | View lessons (admin) |
| `lessons.edit` | Edit lessons |
| `lessons.delete` | Delete lessons |

**Default role permissions:**
- **Instructor:** All course/module/lesson CRUD permissions
- **Student:** None; students can only view published content, not manage it
- **Admin:** All permissions

---

## Database Indexes

For performance, these indexes are in place:

```sql
-- Modules
CREATE INDEX idx_modules_course_id_order ON modules(course_id, order);

-- Lessons
CREATE INDEX idx_lessons_module_id_order ON lessons(module_id, order);

-- Progress
CREATE INDEX idx_lesson_user_user_id ON lesson_user(user_id);
CREATE INDEX idx_lesson_user_lesson_id ON lesson_user(lesson_id);
```

These ensure fast queries for:
- Listing modules in a course (ordered)
- Listing lessons in a module (ordered)
- Finding a student's completed lessons
- Cascading deletes

---

## Multi-Material System

A lesson is no longer limited to one video embed — it can hold any number of ordered materials of mixed types (video, PDF, document, audio, presentation, image, interactive, markdown), each independently uploaded, versioned, and tracked for access.

### Structure: 1 Lesson → N Materials

**Database:** `lesson_materials` table
- `id` (UUID, PK)
- `lesson_id` (UUID, FK → lessons, CASCADE)
- `type` (varchar 255) — `App\Enums\MaterialType` value
- `title`, `description` (nullable)
- `file_url`, `file_path` (R2 object URL / key)
- `file_size` (unsigned int, bytes), `mime_type`
- `order` (unsigned int) — position within the lesson
- `version` (unsigned int, default 1), `is_active` (bool, default true)
- **Unique constraint:** `(lesson_id, title, version)`

**Database:** `lesson_material_user` pivot table
- Composite PK `(lesson_material_id, user_id)`
- `accessed_at` (nullable) — when the student marked/viewed the material

`Lesson::materials()` returns the `hasMany(LessonMaterial)` relation; `getMaterialsOrdered()` returns them sorted by `order`, scoped to `->active()` (current version only).

### Material Types & File Limits

Defined in `App\Enums\MaterialType`:

| Type | Max size | Extensions |
|---|---|---|
| Video | 500 MB | mp4, webm, ogg, mov, avi, mkv |
| Audio | 100 MB | mp3, wav, ogg, m4a, flac |
| Interactive | 100 MB | html, htm, json |
| PDF | 50 MB | pdf |
| Presentation | 50 MB | ppt, pptx, odp |
| Document | 25 MB | doc, docx, txt, rtf, odt |
| Image | 25 MB | jpg, jpeg, png, gif, webp, svg |
| Markdown | 10 MB | md, markdown |

Uploads are validated with 3 layers before being accepted: file extension → magic bytes (file signature) → declared MIME type. Extension-only checks are not trusted since they're client-supplied.

### R2 Storage & Quota Management

Files are stored in Cloudflare R2 via `R2StorageService`. There is a single global quota (`GLOBAL_QUOTA_BYTES`, 10 GB) shared across all schools — not a per-school allocation:
- Upload flow: presigned PUT URL generated → client uploads directly to R2 → server finalizes (validates + inserts `lesson_materials` row)
- Quota is enforced before the presigned URL is issued (`enforceQuotaLimit()`, throws a 413 if exceeded)
- Transient R2 failures (408/429/5xx) get automatic retry with exponential backoff (max 3 attempts)
- `StorageMonitoringService` aggregates `SUM(lesson_materials.file_size)` (scoped to `->active()` materials) for the admin dashboard's global/per-school breakdown, rather than scanning the R2 bucket directly — R2 objects sit under a flat prefix and can't be attributed to a school on their own
- An hourly `CalculateStorageUsageJob` logs a `storage_usage_logs` snapshot and emails users with `settings.school` permission the first time usage newly crosses 80%, 90%, or 100%

**Material versioning:** replacing a material's file creates a new `version` row rather than overwriting the old one; the previous version is deactivated (`is_active = false`) but kept for rollback. Deleting the active version auto-activates the next-most-recent one; the last remaining version cannot be deleted.

### Sidebar UI & Player Types

In the student-facing `LessonViewer`, materials render in a right-hand sidebar (~33% width, stacked below the player on mobile) with a per-type icon, and the main area renders a type-appropriate viewer:

| Type | Viewer |
|---|---|
| Video | HTML5 `<video>` player |
| Audio | HTML5 `<audio>` player |
| Image | inline `<img>`, max-sized |
| Interactive | rendered HTML in an iframe |
| PDF, Presentation, Document | in-browser view where supported, else icon + download button |

### Completion Flow

Unlike the old single-video lesson, completion is now driven by material access rather than a single "Mark Complete" click:

- `LessonCompletionService::isLessonComplete()` — a lesson is complete once the student has accessed **100%** of its active materials
- Each material can be marked accessed individually (auto-tracked on view for some types, explicit "Mark as Read" button for others)
- `getLessonProgress()` returns `{total, accessed, percentage, is_complete}`; `getModuleProgress()` / `getCourseProgress()` aggregate this up the hierarchy
- Once all materials are accessed, `lesson_user.completed_at` is set automatically — the manual "Mark Complete" button from the legacy single-video flow no longer applies once a lesson has materials

### Instructor Workflow

In `LessonForm`:
1. Upload a file per material (title, type auto-detected from extension, description optional) — a presigned URL is requested, the file is `PUT` directly to R2, then finalized
2. Reorder materials via up/down controls (`reorderMaterials()`)
3. Replace a material's file via "Upload New Version" — creates a new version rather than overwriting
4. View/switch/delete individual versions under a collapsible "Version History" panel per material
5. Delete a material outright (removes all versions from R2 and the DB)
6. A quota indicator shows used/remaining storage with green/yellow/orange/red thresholds, and disables uploads once the global quota is exhausted

Admins additionally get a dedicated storage dashboard (`admin.storage.dashboard`) with global usage, a 30-day trend graph, a per-school breakdown table, and a filterable/searchable materials browser (`admin.storage.materials`) with per-material delete.

### Student Workflow

In `LessonViewer`:
1. Materials for the current lesson list in the sidebar, each showing a type icon and a checkmark once accessed
2. Clicking a material switches the main-area player/viewer to it
3. A progress bar shows "X of Y materials accessed"
4. A "Download" button is available for every material type; a "Mark as Read" button appears for types that aren't auto-tracked on view
5. Once every material is accessed, the lesson is automatically marked complete and rolls up into module/course progress

### Migration Guide: Single-Video Lessons → Multi-Material (historical)

Lessons created before this system only had `lessons.video_embed_url`. Two migrations handled the transition, in order:

1. `2026_07_17_222102_migrate_video_embed_url_to_lesson_materials.php` — backfilled every lesson where `video_embed_url IS NOT NULL` into a `lesson_materials` row (`type = Video`, `title = lesson title`, `file_url = video_embed_url`, `order = 1`, `file_size = 0` since the URL was an external embed, not an uploaded file)
2. `2026_07_22_062544_drop_video_embed_url_from_lessons_table.php` — dropped the now-unused `lessons.video_embed_url` column once every lesson's video had a corresponding `lesson_materials` row

Both models, the repository/service layer, and the factory have had all `video_embed_url` references removed; new lessons only ever use `lesson_materials`. Covered by `tests/Feature/MaterialMigrationTest.php` (asserts the column no longer exists).

---

## Related Documentation

- [`docs/PRD.md`](PRD.md) — Product requirements and use cases (Section 5: US2, US5)
- [`docs/ERD.md`](ERD.md) — Entity-relationship diagram with full schema
- [`tests/Feature/Livewire/Courses/`](../tests/Feature/Livewire/Courses/) — Comprehensive Livewire component tests
