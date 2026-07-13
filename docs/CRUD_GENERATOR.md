# CRUD Generator - Complete Guide

**Version:** 2.1  
**Date:** July 1, 2026  
**Status:** ✅ Production Ready

> **Note:** `make:rsc` now generates a **Livewire-based** CRUD (components in `app/Livewire`, views in `resources/views/livewire`, plain `Route::get` entries) instead of a Controller + `Route::resource` + `resources/views/app` stack. Sections below that reference `ProductController`, `Route::resource`, or `resources/views/app/...` describe the earlier Controller/Blade generator; see [`CRUD_GENERATOR_SHOWCASE.md`](CRUD_GENERATOR_SHOWCASE.md) for the current generated file list, routes, and architecture. The Repository/Service/Migration/Permission sections below are still accurate.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Create Command (make:rsc)](#create-command-makersc)
4. [Delete Command (delete:rsc)](#delete-command-deletersc)
5. [Migration Setup](#migration-setup)
6. [Column Configuration](#column-configuration)
7. [Generated Files](#generated-files)
8. [Routes Setup](#routes-setup)
9. [View Details](#view-details)
10. [Field Types](#field-types)
11. [Testing with Dusk](#testing-with-dusk)
12. [Tailwind CSS](#tailwind-css)
13. [Customization](#customization)
14. [Architecture](#architecture)
15. [Troubleshooting](#troubleshooting)

---

## Overview

Tailwind CRUD Generator auto-creates professional CRUD views with:
- ✅ Configurable view paths (no hardcoded 'siakad')
- ✅ Four separate blade files (index, create, edit, show)
- ✅ Modern Tailwind CSS v4 styling
- ✅ Dark mode support
- ✅ Responsive design (mobile-first)
- ✅ RESTful routes
- ✅ Auto-generated forms with full enum support (select dropdowns)
- ✅ Column-aware filter UI (text, date range, enum dropdown) out of the box
- ✅ Server-side DataTables with filter-aware AJAX endpoint
- ✅ Sidebar driven by `config/sidebar.php` (config-based, not hardcoded HTML)
- ✅ Auto-generated CRUD permission migration (view/create/update/delete), assigned to the `admin` role

**Example:**
```bash
php artisan make:rsc Product --label="Products"
# Generates: index.blade.php, create.blade.php, edit.blade.php, show.blade.php
# Location: resources/views/app/product/
```

---

## Quick Start

### Generate CRUD with Interactive Migration
```bash
php artisan make:rsc Product --label="Products"
```

**The generator will:**
1. ✅ Ask if you want to create a migration
2. ✅ Interactively prompt for column details
3. ✅ Create migration automatically
4. ✅ Ask to run migration
5. ✅ Generate a CRUD permission migration (view/create/update/delete) and assign it to the `admin` role
6. ✅ Ask to run the permission migration
7. ✅ Generate Repository, Service, and Controller
8. ✅ Generate Form Requests
9. ✅ Configure form input types
10. ✅ Generate Blade views
11. ✅ Create routes in `routes/web.php`

### Manual Setup (If Needed)

**1. Create Model only:**
```bash
php artisan make:model Product
```

**2. Define migration fields manually:**
```php
// database/migrations/xxxx_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->integer('stock')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**3. Run migration:**
```bash
php artisan migrate
```

**4. Generate CRUD:**
```bash
php artisan make:rsc Product --label="Products" --repository-service-only
```

**5. Add routes to `routes/web.php`:**
```php
use App\Http\Controllers\ProductController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class);
});
```

**6. Test:**
```bash
php artisan dusk
```

---

## Create Command (make:rsc)

### Basic Syntax
```bash
php artisan make:rsc {ModelName} [--label="Label"] [--view-path=path] [--repository-service-only]
```

### Options

| Option | Default | Purpose |
|--------|---------|---------|
| `name` | Required | Model/resource name |
| `--label` | Same as name | Display label in UI |
| `--view-path` | `app` | View directory path |
| `--repository-service-only` | false | Skip controller & views |

### Examples

**Full CRUD with interactive migration:**
```bash
php artisan make:rsc Product --label="Products"
# Creates: Model, Migration, Repository, Service, Controller, Views, Routes
```

**Custom view path:**
```bash
php artisan make:rsc Product --label="Products" --view-path="admin"
# → resources/views/admin/product/
```

**Repository & Service Only:**
```bash
php artisan make:rsc Product --repository-service-only
# Skips: Controller and Views
# Useful for API-only models
```

### Interactive Flow

When you run the command, it will prompt you:

```
Do you want to create a migration? [yes]
  → Type "yes" or press Enter to create migration

Column name (or "done" to finish, "back" to remove last column):
  → name                          ← column name
  Column type [text]:
    [1] string
    [2] integer
    [3] decimal
    [4] boolean
    [5] date
    ...
  String length (press Enter for default 255): 255
  Nullable? [no]: no
  Add default value? [no]: no
  Add index? [no]: no
  Add unique constraint? [no]: no

  Input type for 'name' field:
    [1] skip
    [2] text
    [3] email
    [4] password
    [5] url
    [6] tel
    
  Confirm column configuration? [yes]
```

#### Column Configuration Details

- **Column name:** Database column name (snake_case recommended)
- **Column type:** Choose from: string, integer, decimal, boolean, text, date, datetime, timestamp, json, enum
- **Type-specific options:**
  - **string:** Length (default 255)
  - **decimal:** Precision and scale (e.g., 10,2)
  - **enum:** Comma-separated values
- **Nullable:** Can column be null?
- **Default value:** Default value for new records
- **Index:** Add database index for faster queries
- **Unique:** Add unique constraint (string only)
- **Input type:** Form field type for generated views
  - `skip` = Don't include in forms
  - `text` = Regular text input
  - `email` = Email input
  - `password` = Password input
  - `url` = URL input
  - `tel` = Telephone input
  - `textarea` = Multi-line text
  - `select` = Dropdown (for enums — options are pre-populated automatically)
  - `radio` = Radio buttons (for boolean)
  - `checkbox` = Checkbox input
  - `date` = Date picker
  - `datetime-local` = DateTime picker
  - `number` = Number input

#### Tips

- Type `back` to remove the last column and reconfigure it
- Type `done` when you've added all columns
- The command will verify your configuration before creating the migration
- If migration runs successfully, you'll be prompted to configure form input types

---

## Delete Command (delete:rsc)

### Basic Syntax
```bash
php artisan delete:rsc {ModelName} [--migrations]
```

### Options

| Option | Default | Purpose |
|--------|---------|---------|
| `name` | Required | Model/resource name to delete |
| `--migrations` | false | Also delete migration files |

### Examples

**Delete all CRUD files:**
```bash
php artisan delete:rsc Product
# Deletes: Model, Repository, Service, Livewire components, Form Requests, Views
# Removes: Routes, Service Provider Binding, Sidebar Item
```

**Delete with migrations:**
```bash
php artisan delete:rsc Product --migrations
# Also deletes: Migration files matching the table name
```

### What Gets Deleted

**Files:**
- ✓ Model: `app/Models/Product.php`
- ✓ Repository: `app/Repositories/Product/`
- ✓ Service: `app/Services/ProductService.php`
- ✓ Livewire components: `app/Livewire/Products/`
- ✓ Form Requests: `app/Http/Requests/Product/`
- ✓ Views: `resources/views/livewire/products/`
- ✓ Migrations (if `--migrations` flag used)

**Configuration:**
- ✓ Routes: Removes the generated `Route::get(...)` Index/Create/Edit lines and their `App\Livewire` imports from `routes/web.php`
- ✓ Service Provider: Removes binding from `app/Providers/AppServiceProvider.php`
- ✓ Sidebar: Removes entry from `config/sidebar.php`
- ✓ Runs `php artisan optimize` after cleanup

### Safety Features

- ✓ Confirmation prompt before deletion
- ✓ Detailed output showing what was deleted
- ✓ Safe pattern matching (won't delete unrelated files)
- ✓ Graceful handling if files don't exist

---

## Migration Setup

The `make:rsc` command handles migration creation interactively. Here's what happens:

---

## Column Configuration

### Column Type Reference

| Type | Options | Use Case |
|------|---------|----------|
| `string` | Length (default 255) | Names, titles, short text |
| `integer` | None | Whole numbers, quantities |
| `bigInteger` | None | Large whole numbers |
| `smallInteger` | None | Small whole numbers |
| `decimal` | Precision, Scale | Prices, ratings, precise decimals |
| `float` | None | Floating point numbers |
| `boolean` | None | True/False, Yes/No, Active/Inactive |
| `text` | None | Long text, descriptions, content |
| `longText` | None | Very long content |
| `date` | None | Date only (YYYY-MM-DD) |
| `dateTime` | None | Date and time |
| `timestamp` | None | Auto-timestamp fields |
| `json` | None | JSON data |
| `enum` | Comma-separated values | Fixed set of options |

### Form Input Type Mapping

The generator automatically suggests input types based on column type:

| Column Type | Suggested Input Types |
|-------------|----------------------|
| `string` | text, email, password, url, tel |
| `integer` | number |
| `decimal` / `float` | number |
| `boolean` | radio, checkbox |
| `text` / `longText` | textarea |
| `date` | date |
| `dateTime` / `timestamp` | datetime-local |
| `enum` | select |
| `json` | textarea |

### Example: Creating a Product Table

```
Column 1: name
  Type: string
  Length: 255
  Nullable: no
  Default: none
  Index: no
  Unique: yes
  Input Type: text

Column 2: description
  Type: text
  Nullable: yes
  Default: none
  Input Type: textarea

Column 3: price
  Type: decimal
  Precision: 10
  Scale: 2
  Nullable: no
  Index: yes
  Input Type: number

Column 4: is_active
  Type: boolean
  Nullable: no
  Default: true
  Input Type: radio

Type "done" to finish migration creation
```

---

## Generated Files

### Directory Structure

```
app/
├── Models/Product.php                           # Eloquent Model
├── Repositories/Product/
│   ├── ProductRepositoryInterface.php           # Interface
│   └── ProductRepository.php                    # Implementation
├── Services/ProductService.php                  # Business Logic
├── Livewire/Products/
│   ├── ProductIndex.php                         # List + filters + delete
│   ├── ProductCreate.php                        # Create form
│   └── ProductEdit.php                          # Edit form
└── Http/Requests/Product/
    ├── StoreProductRequest.php                  # Create Validation
    └── UpdateProductRequest.php                 # Update Validation

database/migrations/
├── xxxx_create_products_table.php               # Migration
└── xxxx_add_products_permissions_and_assign_to_admin.php  # Permission migration

resources/views/livewire/products/
├── product-index.blade.php                      # List page via <x-livewire-data-table>
├── product-create.blade.php                     # Create form
└── product-edit.blade.php                       # Edit form

routes/web.php                                   # Auto-added Route::get lines + component imports
app/Providers/AppServiceProvider.php             # Auto-added binding
config/sidebar.php                               # Auto-added menu entry
```

### Output Messages

**Migration Phase:**
```
✓ Column 'name' added
✓ Column 'description' added
✓ Column 'price' added
Migration executed successfully.
Permission migration created: database/migrations/xxxx_add_products_permissions_and_assign_to_admin.php
Permission migration executed successfully.
```

**CRUD Generation Phase:**
```
✓ Model created
✓ Repository Interface created
✓ Repository created
✓ Service created
✓ Service provider binding added
✓ Controller created
✓ Form Request rules generated
✓ Blade index view created
✓ Blade create view created
✓ Blade edit view created
✓ Blade show view created
✓ Routes added
✓ Sidebar item added
```

**Final:**
```
php artisan optimize executed.
```

**Error Handling:**
```
Error occurred: [error message]
Cleaning up generated files...
[Deleted: Model, Repository, Service, Controller, etc.]
Generation failed and files have been cleaned up.
```

---

## Routes Setup

### ✅ Automatic Route Generation

The generator automatically adds routes to `routes/web.php`:

```php
// routes/web.php - Auto-added by generator
use App\Livewire\Products\ProductCreate;
use App\Livewire\Products\ProductEdit;
use App\Livewire\Products\ProductIndex;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('products', ProductIndex::class)->middleware('permission:products.view')->name('products.index');
    Route::get('products/create', ProductCreate::class)->middleware(['permission:products.view', 'permission:products.create'])->name('products.create');
    Route::get('products/{product}/edit', ProductEdit::class)->middleware(['permission:products.view', 'permission:products.update'])->name('products.edit');
});
```

**If routes don't auto-add**, add them manually and ensure:
- Correct Livewire component namespace (`App\Livewire\{Plural}`)
- Proper `permission:` middleware applied
- Kebab-cased route name matches the sidebar/view path

### Routes Created

| HTTP | URL | Component | Route Name |
|------|-----|-----------|-----------|
| GET | `/products` | `ProductIndex` | `products.index` |
| GET | `/products/create` | `ProductCreate` | `products.create` |
| GET | `/products/{product}/edit` | `ProductEdit` | `products.edit` |

There is no `show`/`store`/`update`/`destroy` route — Create/Edit submit via Livewire actions on the same route, and delete happens through the `delete-confirmed` Livewire event dispatched by the global confirm modal in `resources/views/layouts/app.blade.php`.

### Verify Routes

```bash
php artisan route:list | grep products
```

---

## View Details

> View file paths below use the pre-Livewire naming (`index.blade.php`, etc.) and `products.store`/`products.update`/`products.show` route names. The current generator produces `resources/views/livewire/products/product-{index,create,edit}.blade.php`, backed by `ProductIndex`/`ProductCreate`/`ProductEdit` Livewire components under only three routes: `products.index`, `products.create`, `products.edit` (see [Routes Setup](#routes-setup) above). There is no generated show view.

### 1. Index View (`product-index.blade.php`)

**URL:** `/products`  
**Method:** GET  
**Route:** `products.index`

**Features:**
- Header with model label
- "New" button for creating
- Success/error alert messages
- Column-aware filter UI (text, date range, enum dropdown — per column type)
- Apply / Reset filter buttons
- Server-rendered table via `<x-livewire-data-table>`, sorted with `wire:click` (no AJAX/DataTables.js)
- Pagination controls

**Layout:**
```
┌─────────────────────────────────────┐
│ Products                 [New Products]
│ Manage your Products                 │
├─────────────────────────────────────┤
│ ✓ Success/Error messages            │
├─────────────────────────────────────┤
│ [Name ____] [Status ▼] [Apply][Reset]│
├─────────────────────────────────────┤
│ ┌───────────────────────────────────┐
│ │ ID │ Name │ Status │ Created │ Act
│ ├───────────────────────────────────┤
│ │ 1  │ ...  │ ...    │ ...     │ V E D
│ └───────────────────────────────────┘
│ [← Previous] 1 2 3 [Next →]         │
└─────────────────────────────────────┘
```

### 2. Create View (`create.blade.php`)

**URL:** `/products/create`  
**Method:** GET (display) / POST (submit to store)  
**Route:** `products.create` / `products.store`

**Features:**
- Breadcrumb navigation
- Form title & description
- Auto-generated form fields
- Validation error display
- Submit button
- Cancel button

**Layout:**
```
┌─────────────────────────────────────┐
│ Products / Create                   │
├─────────────────────────────────────┤
│ Create Products                     │
│ Fill in the form below to create... │
├─────────────────────────────────────┤
│ ✗ Validation errors (if any)        │
├─────────────────────────────────────┤
│ ┌───────────────────────────────────┐
│ │ Product Name                      │
│ │ [____________________]            │
│ │                                   │
│ │ Description                       │
│ │ [____________________]            │
│ │                                   │
│ │ Price                             │
│ │ [____________________]            │
│ │                                   │
│ │ [Create] [Cancel]                 │
│ └───────────────────────────────────┘
└─────────────────────────────────────┘
```

### 3. Edit View (`edit.blade.php`)

**URL:** `/products/{id}/edit`  
**Method:** GET (display) / PUT (submit to update)  
**Route:** `products.edit` / `products.update`

**Features:**
- Breadcrumb with item link
- Form title & description
- Pre-populated form fields
- Two-column layout:
  - 8-column form area
  - 4-column sidebar
- Delete button that opens the single global delete-confirmation modal (rendered once in `resources/views/layouts/app.blade.php`, not per-view)
- Save & cancel buttons

**Layout:**
```
┌─────────────────────────────────┬─────────┐
│ Products / Item / Edit          │         │
├─────────────────────────────────┼─────────┤
│ Edit Products                   │ Info    │
│ Update the record details       │ Created │
│                                 │ Updated │
├─────────────────────────────────┤ [Delete]│
│ ┌─────────────────────────────┐ │         │
│ │ Name [______________]       │ │         │
│ │ Desc [______________]       │ │         │
│ │ Price [______________]      │ │         │
│ │                             │ │         │
│ │ [Save] [Cancel]             │ │         │
│ └─────────────────────────────┘ │         │
└─────────────────────────────────┴─────────┘
```

### 4. Show View — **not generated**

The generator no longer produces a show/detail view or route; the section below describes the earlier Controller/Blade generator and is kept for historical reference only.

Original section (`show.blade.php`)

**URL:** `/products/{id}`  
**Method:** GET  
**Route:** `products.show`

**Features:**
- Breadcrumb navigation
- Read-only field display
- Two-column layout:
  - 8-column details area
  - 4-column sidebar
- Created/Updated timestamps in sidebar
- Edit button
- Delete button with confirmation modal
- Back to list button

**Layout:**
```
┌─────────────────────────────────┬─────────┐
│ Products / Item                 │         │
├─────────────────────────────────┼─────────┤
│ Details                         │ Info    │
│ Name: Product Name              │ Created │
│ Desc: Description               │ Updated │
│ Price: $99.99                   │         │
│                                 │ [Edit]  │
│                                 │ [Delete]│
│                                 │ [Back]  │
└─────────────────────────────────┴─────────┘
```

---

## Field Types

Generator auto-detects database column types and creates appropriate form inputs:

| Database Type | HTML Input | Example |
|---------------|-----------|---------|
| `string(255)` | `<input type="text">` | Name, title, email |
| `text` | `<textarea rows="4">` | Description, content |
| `integer` | `<input type="number">` | Quantity, count, age |
| `bigint` | `<input type="number">` | Large numbers |
| `smallint` | `<input type="number">` | Small numbers |
| `tinyint` | `<input type="number">` | 0-255 range |
| `decimal(10,2)` | `<input type="number" step="0.01">` | Price, rating |
| `float` | `<input type="number" step="0.01">` | Decimal values |
| `double` | `<input type="number" step="0.01">` | Decimal values |
| `boolean` | Radio buttons (Yes/No) | is_active, is_published |
| `date` | `<input type="date">` | Birth date, start date |
| `datetime` | `<input type="datetime-local">` | Timestamp, scheduled |
| `timestamp` | `<input type="datetime-local">` | Auto-populated |
| Foreign Key | `<select>` dropdown | Category, user, relation |

### Form Field Examples

**Text Input:**
```blade
<input type="text" name="name" class="mt-1 block w-full rounded-md" required>
```

**Textarea:**
```blade
<textarea name="description" rows="4" class="mt-1 block w-full rounded-md" required></textarea>
```

**Select (Foreign Key):**
```blade
<select name="category_id" class="mt-1 block w-full rounded-md" required>
  @foreach($categories as $cat)
    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
  @endforeach
</select>
```

**Boolean (Radio):**
```blade
<div class="flex items-center">
  <input type="radio" name="is_active" value="1" checked>
  <label>Yes</label>
</div>
<div class="flex items-center">
  <input type="radio" name="is_active" value="0">
  <label>No</label>
</div>
```

---

## Tailwind CSS

### Classes Used

**Layout:**
```
flex, grid, gap-{n}, px-{n}, py-{n}, mt-{n}, mb-{n}, pt-{n}, pb-{n}
```

**Colors (Light Mode):**
```
bg-white, bg-blue-600, bg-red-600, bg-gray-200
text-gray-900, text-gray-700, text-gray-600
border-gray-200, border-gray-300
```

**Colors (Dark Mode):**
```
dark:bg-gray-800, dark:bg-gray-700
dark:text-white, dark:text-gray-300
dark:border-gray-600, dark:border-gray-700
```

**Typography:**
```
text-{size}, font-{weight}, font-semibold, font-bold, text-center
```

**Responsive:**
```
sm:, lg:, max-w-{size}, container, w-full
```

**Interactive:**
```
hover:, active:, focus:, disabled:, transition, ring
```

### Customizing Styles

**Change button color:**
```blade
<!-- Blue to green -->
<button class="bg-green-600 hover:bg-green-700">{{ __('Save') }}</button>
```

**Change layout grid:**
```blade
<!-- Change sidebar width (8-4 to 7-5) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-7">Form</div>
    <div class="lg:col-span-5">Sidebar</div>
</div>
```

**Add custom styling:**
```blade
<!-- Add background pattern -->
<div class="bg-gradient-to-r from-blue-50 to-green-50 dark:from-gray-800 dark:to-gray-700">
```

---

## Customization

### Add Custom Fields

Open blade file and insert after auto-generated fields:

```blade
<!-- In create.blade.php or edit.blade.php -->
<div class="mb-4">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    Category
  </label>
  <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
    @foreach($categories as $cat)
      <option value="{{ $cat->id }}">{{ $cat->name }}</option>
    @endforeach
  </select>
</div>
```

### Modify Table Columns (Index View)

```blade
<!-- In index.blade.php thead -->
<tr>
  <th class="px-6 py-3">{{ __('ID') }}</th>
  <th class="px-6 py-3">{{ __('Name') }}</th>
  <th class="px-6 py-3">{{ __('Price') }}</th>       <!-- Add custom -->
  <th class="px-6 py-3">{{ __('Stock') }}</th>       <!-- Add custom -->
  <th class="px-6 py-3">{{ __('Actions') }}</th>
</tr>
```

### Add DataTables Integration

```blade
<!-- In index.blade.php @push('scripts') -->
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('product-table');
    if (table && typeof DataTable !== 'undefined') {
      new DataTable('#product-table', {
        ajax: {
          url: '{{ route("products.data") }}',
          type: 'GET'
        },
        columns: [
          { data: 'id' },
          { data: 'name' },
          { data: 'price' },
          { data: 'stock' },
          { data: 'actions', orderable: false, searchable: false }
        ]
      });
    }
  });
</script>
@endpush
```

### Change Colors

Edit CSS classes:

```blade
<!-- From blue to purple -->
<button class="bg-purple-600 hover:bg-purple-700 text-white">
  {{ __('Save') }}
</button>

<!-- From red to orange -->
<button class="bg-orange-600 hover:bg-orange-700 text-white">
  {{ __('Delete') }}
</button>
```

---

## Architecture

### Separation of Concerns

```
Request → Controller → Service → Repository → Database
                ↓
             Form Request (Validation)
             
Response ← View (Blade + Tailwind)
```

### File Organization

```
app/Repositories/{Model}/
├── {Model}RepositoryInterface.php    # Interface
└── {Model}Repository.php              # Implementation

app/Services/
└── {Model}Service.php                 # Business logic

app/Http/Controllers/
└── {Model}Controller.php              # HTTP handling

app/Http/Requests/{Model}/
├── Store{Model}Request.php            # Create validation
└── Update{Model}Request.php           # Update validation

resources/views/app/{model}/
├── index.blade.php                    # List
├── create.blade.php                   # Create form
├── edit.blade.php                     # Edit form
└── show.blade.php                     # Details

routes/web.php
└── Route::resource(...)               # RESTful routes
```

### Data Flow

**Create Flow:**
```
GET /products/create
    ↓ (Load form)
ProductController::create()
    ↓ (Return view)
create.blade.php
    ↓ (User fills form & submits)
POST /products (store)
    ↓ (Validate)
StoreProductRequest
    ↓ (Process)
ProductController::store()
    ↓ (Save via service)
ProductService::create()
    ↓ (Store in DB via repository)
ProductRepository::create()
    ↓ (Redirect)
/products/{id} (show)
```

**Edit Flow:**
```
GET /products/{id}/edit
    ↓
ProductController::edit()
    ↓ (Pre-populate form)
edit.blade.php
    ↓ (User updates & submits)
PUT /products/{id} (update)
    ↓ (Validate)
UpdateProductRequest
    ↓ (Process)
ProductController::update()
    ↓ (Update via service)
ProductService::update()
    ↓ (Update in DB via repository)
ProductRepository::update()
    ↓ (Redirect)
/products/{id} (show)
```

---

## Sidebar Management

The sidebar is driven by `config/sidebar.php`. The generator automatically appends an entry to that file:

```php
// config/sidebar.php
[
    'label' => 'Products',
    'route' => 'products.index',
    'icon'  => 'shopping_cart',
    'active_pattern' => 'products.*',
],
```

The sidebar blade component loops over this config, so no HTML is injected into the view.

### Remove Sidebar Item

When you delete a CRUD with `php artisan delete:rsc Product`, the config entry is automatically removed.

**Manual removal:** Delete the matching array entry from `config/sidebar.php`.

---

## Service Provider Binding

The generator automatically adds bindings to `app/Providers/AppServiceProvider.php`:

```php
$this->app->bind(
    ProductRepositoryInterface::class,
    ProductRepository::class
);
```

When you delete with `php artisan delete:rsc Product`, bindings are automatically removed.

---

## Troubleshooting

### Issue: Migration errors during column creation

**Problem:** Invalid column type or configuration  
**Solution:**
1. Use `back` command to remove the problematic column
2. Check column type is valid (string, integer, decimal, etc.)
3. For decimal, ensure precision > scale
4. Re-add the column with correct settings

```
Column name: price
Column type: decimal
Precision (total digits) [8]: 10
Scale (decimal places) [2]: 2  ← Must be ≤ 10
```

### Issue: Input type not matching column type

**Problem:** Form field type doesn't match database type  
**Solution:**
1. The generator suggests appropriate input types automatically
2. If wrong type was selected, manually edit the blade file
3. Or delete and regenerate with correct input type

```bash
# Delete and regenerate
php artisan delete:rsc Product
php artisan make:rsc Product --label="Products"
```

### Issue: Views not rendering

**Problem:** Blade files not found  
**Solution:**
1. Verify view path matches `--view-path` option
2. Check files exist in `resources/views/app/` (default) or custom path
3. Ensure model name matches view folder (kebab-case)

```bash
# Check actual paths
ls resources/views/app/product/
ls resources/views/admin/product/  # if --view-path=admin used
```

### Issue: Generation failed and files were cleaned up

**Problem:** Error occurred during generation, all files automatically deleted  
**Solution:**
1. Check the error message for details
2. Common causes:
   - Invalid migration (duplicate column, syntax error)
   - Disk permissions issue
   - Database connection problem
3. Fix the issue and run `make:rsc` again

```bash
# If migration was the issue, retry from scratch
php artisan make:rsc Product --label="Products"
```

### Issue: Deletion didn't remove everything

**Problem:** Some files/config still exist after delete:rsc  
**Solution:**
1. Delete command removes what it can find
2. If you manually edited files, delete them manually:
   - Custom blade files
   - Modified routes
   - Custom repository methods
3. Check `config/sidebar.php` for orphaned menu entries

```bash
# Verify what was deleted
git status  # See what files changed
grep -r "Product" routes/web.php  # Check if routes removed
grep -r "ProductRepository" app/Providers/AppServiceProvider.php
```

### Issue: Form validation errors not showing

**Problem:** Error messages don't appear  
**Solution:**
1. Verify Form Request class exists
2. Check validation rules are defined
3. Ensure error messages in blade template
4. Check form has `method="POST"` with CSRF token

```php
// app/Http/Requests/Product/StoreProductRequest.php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'price' => 'required|decimal:0,2',
    ];
}
```

```blade
<!-- In create/edit view -->
@if ($errors->any())
    @foreach ($errors->all() as $error)
        <div class="text-red-600">{{ $error }}</div>
    @endforeach
@endif
```

### Issue: Styles not applying

**Problem:** Tailwind classes not working  
**Solution:**
1. Rebuild CSS: `npm run build`
2. Or run dev watcher: `npm run dev`
3. Check no CSS conflicts
4. Verify dark mode meta tag in layout
5. Clear browser cache

```bash
# Build once
npm run build

# OR watch for changes
npm run dev
```

### Issue: Routes not automatically added

**Problem:** Routes missing from `routes/web.php`  
**Solution:**
1. Generator tries to add routes automatically
2. If it fails, add manually:

```php
// routes/web.php
use App\Http\Controllers\ProductController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class);
});
```

**Verify routes exist:**
```bash
php artisan route:list | grep product
```

### Issue: Service provider binding not added

**Problem:** Dependency injection fails  
**Solution:**
1. Generator automatically adds binding
2. If missing, add manually to `AppServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\Product\ProductRepositoryInterface::class,
        \App\Repositories\Product\ProductRepository::class
    );
}
```

### Issue: Sidebar item not showing

**Problem:** Menu item missing from sidebar  
**Solution:**
1. Check `config/sidebar.php` — the generator appends an entry here
2. If missing, add manually:

```php
// config/sidebar.php
[
    'label' => 'Products',
    'route' => 'products.index',
    'icon'  => 'shopping_cart',
    'active_pattern' => 'products.*',
],
```

### Issue: Database table already exists

**Problem:** Migration fails because table exists  
**Solution:**
1. Generator prompts to drop existing table
2. Choose yes to recreate
3. Or drop manually:

```bash
php artisan migrate:refresh  # Drop all tables
php artisan migrate           # Re-run all migrations
```

### Issue: Column already exists in table

**Problem:** Can't add column to existing table  
**Solution:**
1. Use `--repository-service-only` to skip migration
2. Or manually create a new migration:

```bash
php artisan make:migration add_columns_to_products_table
```

```php
Schema::table('products', function (Blueprint $table) {
    $table->string('new_column')->nullable();
});
```

---

## Statistics

| Metric | Count |
|--------|-------|
| Console Commands | 2 (make:rsc, delete:rsc) |
| Generator Classes | 8+ |
| Blade files per CRUD | 3 (index, create, edit) |
| Supported column types | 13 |
| Input type options | 10+ |
| Generated files per CRUD | 8 |
| Dusk test cases | 11+ |
| Auto-generated screenshots | 11+ |
| Tailwind utilities used | 50+ |
| Configuration prompts | 10+ |

---

## Production Checklist

Before deploying to production:

- [ ] All tests passing: `php artisan test`
- [ ] Dusk tests passing: `php artisan dusk`
- [ ] Routes verified: `php artisan route:list | grep product`
- [ ] Validation rules complete
- [ ] Form error handling tested
- [ ] Dark mode verified
- [ ] Mobile responsive tested
- [ ] Permissions/authorization added
- [ ] Database migrations fresh: `php artisan migrate`
- [ ] Assets compiled: `npm run build`
- [ ] Error handling tested
- [ ] Delete confirmation working
- [ ] Validation messages clear
- [ ] Performance acceptable

---

## Best Practices

### 1. Plan Your Schema First
```
Take time to plan your columns before running make:rsc:
- Write down all columns you need
- Decide on types and constraints
- Plan relationships with other tables
- Consider indexing strategy
```

### 2. Use Descriptive Names
```bash
# Good: Clear, descriptive names
php artisan make:rsc BlogArticle --label="Blog Articles"

# Avoid: Vague names
php artisan make:rsc Item --label="Items"
```

### 3. Leverage the "back" Feature
```
While adding columns, if you make a mistake:
- Type "back" to remove the last column
- Re-add it with correct settings
- Much faster than deleting and regenerating everything
```

### 4. Use --repository-service-only Wisely
```bash
# Use this when:
php artisan make:rsc Product --repository-service-only

# For API-only resources without views
# For sharing repository logic across multiple controllers
# When you'll create custom controllers
```

### 5. Customize After Generation
```
The generator creates excellent starting points:
1. Generate the full CRUD
2. Test that it works
3. Customize views/validation as needed
4. Don't re-generate if you made small changes
```

### 6. Version Control
```bash
# Commit after successful generation
git add .
git commit -m "feat: create product CRUD"

# This way, if something needs fixing, you can revert
git revert HEAD
```

### 7. Verify Before Deleting
```bash
# Always check what you're about to delete
git status

# The delete command is careful, but verify routes/config removal
grep -r "Product" routes/web.php
grep -r "ProductRepository" app/Providers/
```

---

## Error Recovery

### If Generation Fails

**The generator has built-in rollback:**
1. All generated files are tracked
2. If ANY error occurs, it automatically cleans up
3. Database rolls back if migration fails
4. No orphaned files left behind

```
Error occurred: [details]
Cleaning up generated files...
Deleted: Model
Deleted: Repository  
Deleted: Service
Deleted: Controller
...
Generation failed and files have been cleaned up.
```

### Manual Recovery

If you need to fix something:

```bash
# Check what files exist
git status

# View the error in detail
php artisan make:rsc Product --label="Products" 2>&1

# Delete manually if needed
php artisan delete:rsc Product

# Re-run with correct options
php artisan make:rsc Product --label="Products"
```

### Database Recovery

If migration failed:

```bash
# Check table status
php artisan tinker
> Schema::getTables()

# Rollback last migration
php artisan migrate:rollback

# Or reset everything
php artisan migrate:fresh  # ⚠️ Deletes all data
```

---

## Files Reference

### Main Commands
```
app/Console/Commands/
├── MakeRepositoryServiceController.php      # Generate CRUD
└── DeleteRepositoryServiceController.php    # Delete CRUD
```

### Generators
```
app/Console/Commands/Generators/
├── LivewireGenerator.php
├── RouteGenerator.php
├── PermissionMigrationGenerator.php
└── ServiceProviderBindingGenerator.php
```

### Stubs
```
app/Console/Commands/Stubs/
├── ModelStubGenerator.php
├── RepositoryInterfaceStubGenerator.php
├── RepositoryStubGenerator.php
├── ServiceStubGenerator.php
├── LivewireIndexStubGenerator.php
├── LivewireIndexViewStubGenerator.php   # emits <x-livewire-data-table> usage
├── LivewireCreateStubGenerator.php
├── LivewireCreateViewStubGenerator.php
├── LivewireEditStubGenerator.php
└── LivewireEditViewStubGenerator.php
```

### Tests
```
tests/Browser/
├── ProductCrudTest.php  (example)
└── screenshots/         (auto-generated)
```

---

## Related Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Laravel Dusk](https://laravel.com/docs/dusk)
- [RESTful Routes](https://laravel.com/docs/routing#restful-routes)

---

## Summary

**Tailwind CRUD Generator v2.0** creates professional CRUD applications with:

**Generation Features:**
- ✅ Interactive migration creation with column configuration
- ✅ Automatic column verification
- ✅ CRUD permission migration generation, synced to the `admin` role
- ✅ Smart input type selection (full enum support with pre-populated select options)
- ✅ Column-aware filter UI (text, date range, enum dropdown) on every index page
- ✅ Repository + Service + Controller generation (filter-aware)
- ✅ Form Request auto-generation
- ✅ Blade view creation (index, create, edit, show)
- ✅ Automatic route registration
- ✅ Service Provider binding
- ✅ Sidebar menu entry appended to `config/sidebar.php`

**Cleanup Features:**
- ✅ Complete file deletion
- ✅ Route removal
- ✅ Service Provider binding removal
- ✅ Sidebar entry removal from `config/sidebar.php`
- ✅ Optional migration cleanup
- ✅ Runs `php artisan optimize` after cleanup

**Design:**
- ✅ Modern Tailwind CSS v4 styling
- ✅ Responsive mobile-first design
- ✅ Dark mode support
- ✅ Configurable view paths
- ✅ RESTful routing

**Testing & Documentation:**
- ✅ Dusk browser tests
- ✅ Auto-generated screenshots
- ✅ Comprehensive documentation
- ✅ Error handling with automatic cleanup

**Status:** ✅ Production Ready  
**Last Updated:** July 1, 2026  
**Version:** 2.1  
**Maintainer:** Laravel Boost Team

---

**Ready to build. Happy CRUDing!** 🚀
