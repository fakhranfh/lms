# Repository Pattern - Architecture Guide

**Version:** 1.0  
**Date:** June 26, 2026  
**Status:** ✅ Production Ready

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Pattern Architecture](#pattern-architecture)
3. [Components](#components)
4. [How It Works](#how-it-works)
5. [Implementation Details](#implementation-details)
6. [Usage Examples](#usage-examples)
7. [Best Practices](#best-practices)
8. [Testing](#testing)
9. [Advantages](#advantages)
10. [Common Patterns](#common-patterns)

---

## Overview

The **Repository Pattern** is an architectural pattern that provides an abstraction layer between your application's business logic and data access layer. It acts as a collection-like interface for accessing domain objects.

### Why Use Repository Pattern?

✅ **Separation of Concerns** - Business logic is separated from data access  
✅ **Testability** - Easy to mock data layer for unit testing  
✅ **Maintainability** - Changes to database queries don't affect business logic  
✅ **Flexibility** - Swap data sources (Database, API, Cache) without changing business logic  
✅ **Reusability** - Data access logic can be reused across controllers  

### Pattern Flow

```
Request
   ↓
Controller
   ↓
Service (Business Logic)
   ↓
Repository (Data Access)
   ↓
Database / Eloquent Model
```

---

## Pattern Architecture

### Three-Tier Structure

This application implements a **three-tier architecture**:

```
┌─────────────────────────────────────────┐
│         Controller                      │
│  (HTTP Request Handling)                │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│         Service                         │
│  (Business Logic & Orchestration)       │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌──────────────────┬──────────────────────┐
│   Repository     │                      │
│   Interface      │   Repository         │
│                  │   Implementation     │
│ (Contract)       │   (Data Access)      │
└──────────────────┴──────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│      Eloquent Model                     │
│      (ORM Abstraction)                  │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│      Database                           │
└─────────────────────────────────────────┘
```

---

## Components

### 1. Repository Interface

**Location:** `app/Repositories/{Model}/{Model}RepositoryInterface.php`

**Purpose:** Defines the contract for data access operations

**Example:**

```php
<?php

namespace App\Repositories\Product;

interface ProductRepositoryInterface
{
    public function query(array $filters = []);

    public function get(array $filters = [], array $with = []);

    public function getAll();

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}
```

**Method Reference:**

| Method | Parameters | Purpose |
|--------|-----------|---------|
| `query()` | `$filters = []` | Build query with filters, returns query builder |
| `get()` | `$filters = []`, `$with = []` | Get records with filters & eager load relations |
| `getAll()` | - | Get all records from database |
| `find()` | `$id` | Find single record by ID |
| `create()` | `$data` | Create new record |
| `update()` | `$id`, `$data` | Update existing record |
| `delete()` | `$id` | Delete record |

**Key Responsibilities:**
- Define what operations are available
- No implementation details
- Acts as a contract between service and repository

### 2. Repository Implementation

**Location:** `app/Repositories/{Model}/{Model}Repository.php`

**Purpose:** Implements the interface with actual data access logic

**Example:**

```php
<?php

namespace App\Repositories\Product;

use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function query(array $filters = [])
    {
        $query = Product::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }

    public function get(array $filters = [], array $with = [])
    {
        $query = $this->query($filters);

        return $query->with($with)->get();
    }

    public function getAll()
    {
        return Product::all();
    }

    public function find($id)
    {
        return Product::find($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update($id, array $data)
    {
        $model = Product::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        return Product::destroy($id);
    }
}
```

**Method Details:**

**`query(array $filters = [])`**
- Builds a query builder with optional filters
- Iterates through filters and applies `where` clauses
- Skips empty/null values
- Returns Eloquent query builder

**`get(array $filters = [], array $with = [])`**
- Gets records matching filters
- Supports eager loading via `$with` array
- Returns collection

**`getAll()`**
- Returns all records from table
- No filters applied

**`find($id)`**
- Find record by ID
- Returns model instance or null
- Uses `find()` instead of `findOrFail()`

**`create(array $data)`**
- Creates new record
- Returns created model instance

**`update($id, array $data)`**
- Updates existing record
- Uses `findOrFail()` to throw exception if not found
- Returns updated model instance

**`delete($id)`**
- Deletes record by ID
- Returns number of deleted records

**Key Responsibilities:**
- Implement interface methods
- Handle database queries via Eloquent
- Manage filter logic
- Support eager loading with relations
- Return data in appropriate format

### 3. Service Class

**Location:** `app/Services/{Model}Service.php`

**Purpose:** Delegates to repository and can orchestrate business logic

**Example:**

```php
<?php

namespace App\Services;

use App\Repositories\Product\ProductRepositoryInterface;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function get(array $filters = [], array $with = [])
    {
        return $this->productRepository->get($filters, $with);
    }

    public function getAll()
    {
        return $this->productRepository->getAll();
    }

    public function find($id)
    {
        return $this->productRepository->find($id);
    }

    public function create(array $data)
    {
        return $this->productRepository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->productRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->productRepository->delete($id);
    }
}
```

**Service Method Pattern:**

The service provides a simple delegation layer. You can add business logic when needed:

```php
class ProductService
{
    // ... constructor and basic methods ...

    // Add business logic methods
    public function getActive(array $filters = [])
    {
        return $this->productRepository->get(
            array_merge($filters, ['is_active' => true])
        );
    }

    public function createWithSlug(array $data)
    {
        // Add slug generation
        $data['slug'] = str()->slug($data['name']);
        return $this->productRepository->create($data);
    }

    public function bulkUpdate(array $updates)
    {
        foreach ($updates as $id => $data) {
            $this->productRepository->update($id, $data);
        }
        return count($updates);
    }
}
```

**Key Responsibilities:**
- Delegate data operations to repository
- Implement business rules when needed
- Coordinate multiple repository calls if necessary
- Transform data for controllers/views
- Serve as reusable logic layer

### 4. Controller

> **Note:** `make:rsc` generated modules no longer include a Controller — HTTP handling for generated CRUD now lives in `app/Livewire/{Plural}/{Model}Index.php`, `{Model}Create.php`, and `{Model}Edit.php`, which call the Service layer directly from Livewire actions/`render()`. The Repository and Service layers below are unchanged; the Controller example is representative of manually-created modules (or the pre-Livewire generator output).

**Location:** `app/Http/Controllers/{Model}Controller.php`

**Purpose:** Handles HTTP requests and delegates to service

**Example:**

```php
<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    private function foreignData()
    {
        return [];
    }

    public function index()
    {
        return view('app.product.index');
    }

    public function show($id)
    {
        $item = $this->productService->find($id);
        $foreignData = $this->foreignData();

        return view('app.product.show', [
            'item' => $item,
        ] + $foreignData);
    }

    public function create()
    {
        return view('app.product.create', $this->foreignData());
    }

    public function store(StoreProductRequest $request)
    {
        $item = $this->productService->create($request->validated());
        return redirect()->route('products.show', $item)
            ->with('success', __('Product created successfully.'));
    }

    public function edit($id)
    {
        $item = $this->productService->find($id);
        $foreignData = $this->foreignData();

        return view('app.product.edit', [
            'item' => $item,
        ] + $foreignData);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $this->productService->update($id, $request->validated());
        return redirect()->route('products.show', $id)
            ->with('success', __('Product updated successfully.'));
    }

    public function destroy($id)
    {
        $this->productService->delete($id);
        return redirect()->route('products.index')
            ->with('success', __('Product deleted successfully.'));
    }
}
```

**Foreign Keys Handling:**

When your model has foreign keys, the controller auto-injects related services:

```php
class ProductController extends Controller
{
    protected $productService;
    protected $categoryService;
    protected $supplierService;

    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
        SupplierService $supplierService
    )
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->supplierService = $supplierService;
    }

    private function foreignData()
    {
        return [
            'categories' => $this->categoryService->getAll(),
            'suppliers' => $this->supplierService->getAll(),
        ];
    }

    // ... other methods use $this->foreignData() to pass to views
}
```

**Key Responsibilities:**
- Handle HTTP requests
- Validate input via Form Requests
- Delegate to service
- Manage foreign key data for forms
- Return appropriate responses

---

## How It Works

### Data Flow Example: Create Product

```
1. User submits form to POST /products
                ↓
2. ProductController::store(StoreProductRequest $request) receives request
   └─ Validation happens automatically in Form Request
                ↓
3. Controller calls ProductService::create($request->validated())
                ↓
4. Service delegates to repository
   └─ ProductService::create() → $productRepository->create($data)
                ↓
5. Repository executes Eloquent
   └─ Product::create($data)
                ↓
6. Database creates record
                ↓
7. Repository returns created Product model instance
                ↓
8. Service returns Product model
                ↓
9. Controller redirects to show page with success message
                ↓
10. View renders the created product
```

### Data Flow Example: Fetch with Filters

```
GET /products (controller index)
                ↓
ProductController::index() receives request
                ↓
Controller calls: return view('app.product.index')
                ↓
View is rendered without data (template handles fetching via service)
                ↓
Alternative: Fetch data explicitly
ProductController::index()
    $products = $this->productService->get(['is_active' => true])
    return view('app.product.index', ['products' => $products])
                ↓
Service calls: $productRepository->get($filters, $with = [])
                ↓
Repository builds query:
    $query = Product::query()
    ->where('is_active', true)
    ->with([])  // eager load relations
                ↓
Database executes query
                ↓
Repository returns collection of records
                ↓
View renders the product table
```

### Data Flow Example: Update with Foreign Keys

```
GET /products/{id}/edit
                ↓
ProductController::edit($id) receives ID
                ↓
1. Gets item: $item = $this->productService->find($id)
   └─ Service calls: $productRepository->find($id)
   └─ Repository executes: Product::find($id)
   └─ Returns Product model or null

2. Gets foreign data: $foreignData = $this->foreignData()
   └─ Returns ['categories' => $categories, 'suppliers' => $suppliers]
   └─ Fetched via CategoryService::getAll(), SupplierService::getAll()
                ↓
3. Returns view with item and foreign data
   └─ view('app.product.edit', ['item' => $item, 'categories' => ..., ...])
                ↓
User edits form and submits PUT /products/{id}
                ↓
ProductController::update(UpdateProductRequest $request, $id)
                ↓
1. Request validation happens automatically
2. Controller calls: $this->productService->update($id, $request->validated())
   └─ Service calls: $productRepository->update($id, $data)
   └─ Repository executes:
      - $model = Product::findOrFail($id)
      - $model->update($data)
      - return $model
                ↓
3. Redirect to show page
```

---

## Implementation Details

### Service Provider Binding

**Location:** `app/Providers/AppServiceProvider.php`

The repository interface is bound to its implementation in the service provider:

```php
public function register(): void
{
    // Bind ProductRepositoryInterface to ProductRepository
    $this->app->bind(
        \App\Repositories\Product\ProductRepositoryInterface::class,
        \App\Repositories\Product\ProductRepository::class
    );
}
```

**Benefits:**
- Dependency injection works automatically
- Easy to swap implementation
- Single point of configuration

### Dependency Injection

The framework automatically injects dependencies through constructor:

```php
class ProductService
{
    // Automatically resolved from service provider
    public function __construct(ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}

class ProductController
{
    // Automatically resolved by framework
    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }
}
```

---

## Usage Examples

### Basic CRUD Operations

#### Read Operations

```php
// Get all products
$products = $repository->getAll();

// Get with filters
$activeProducts = $repository->get(['is_active' => true]);

// Get with filters and eager load relations
$products = $repository->get(
    ['category' => 'electronics'],
    ['category', 'reviews']  // eager load relations
);

// Get with query builder
$query = $repository->query(['is_active' => true]);
$count = $query->count();
$expensive = $query->where('price', '>', 1000)->get();

// Find single product
$product = $repository->find(1);  // Returns model or null
```

#### Write Operations

```php
// Create
$product = $repository->create([
    'name' => 'Laptop',
    'price' => 999.99,
    'description' => 'High-performance laptop',
    'is_active' => true,
]);

// Update
$product = $repository->update(1, [
    'price' => 899.99,
    'is_active' => false,
]);

// Delete
$deleted = $repository->delete(1); // Returns number of deleted records
```

### Extended Repository Methods

Add custom methods to repository for specific queries:

```php
class ProductRepository implements ProductRepositoryInterface
{
    // Required interface methods...
    public function query(array $filters = []) { }
    public function get(array $filters = [], array $with = []) { }
    public function getAll() { }
    public function find($id) { }
    public function create(array $data) { }
    public function update($id, array $data) { }
    public function delete($id) { }

    // Custom methods - extend with specific queries
    public function getActive(array $with = [])
    {
        return $this->get(['is_active' => true], $with);
    }

    public function findBySlug(string $slug)
    {
        $query = Product::query();
        $query->where('slug', $slug);
        return $query->first();
    }

    public function getExpensive(float $minPrice = 1000)
    {
        return $this->query()
            ->where('price', '>=', $minPrice)
            ->orderBy('price', 'desc')
            ->get();
    }

    public function searchByName(string $name)
    {
        return $this->query()
            ->where('name', 'like', "%{$name}%")
            ->get();
    }

    public function countByCategory(string $category): int
    {
        return $this->query(['category' => $category])
            ->count();
    }

    public function getNewest(int $limit = 10)
    {
        return $this->query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
```

### Service with Business Logic

Services can add business logic on top of repository operations:

```php
class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    // Required delegation methods
    public function get(array $filters = [], array $with = [])
    {
        return $this->productRepository->get($filters, $with);
    }

    // Add custom business logic methods

    // Business logic: Filter active products
    public function getActive(array $with = [])
    {
        return $this->productRepository->getActive($with);
    }

    // Business logic: Apply discount with validation
    public function applyDiscount(int $productId, float $discountPercent)
    {
        // Validate discount
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException('Discount must be between 0 and 100');
        }

        // Get product
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \Exception("Product not found");
        }

        // Calculate new price
        $discountAmount = $product->price * ($discountPercent / 100);
        $newPrice = $product->price - $discountAmount;

        // Update with discount info
        return $this->productRepository->update($productId, [
            'price' => $newPrice,
            'discount_percent' => $discountPercent,
            'original_price' => $product->price,
        ]);
    }

    // Business logic: Duplicate product
    public function duplicateProduct(int $productId)
    {
        $original = $this->productRepository->find($productId);
        
        if (!$original) {
            throw new \Exception("Product not found");
        }

        return $this->productRepository->create([
            'name' => $original->name . ' (Copy)',
            'price' => $original->price,
            'description' => $original->description,
            'category' => $original->category,
            'is_active' => false,  // Deactivate copy by default
        ]);
    }

    // Business logic: Bulk price update with validation
    public function bulkUpdatePrices(array $updates)
    {
        // $updates = [productId => newPrice, ...]
        $updated = 0;

        foreach ($updates as $productId => $newPrice) {
            if ($newPrice < 0) {
                throw new \InvalidArgumentException(
                    "Price for product {$productId} cannot be negative"
                );
            }

            $this->productRepository->update($productId, ['price' => $newPrice]);
            $updated++;
        }

        return $updated;
    }

    // Business logic: Search with filters
    public function search(string $query, array $filters = [])
    {
        $baseFilters = array_merge($filters, []);
        
        // Use query builder for complex searches
        $dbQuery = $this->productRepository->query($baseFilters);
        
        return $dbQuery
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }
}
```

---

## Best Practices

### 1. Keep Repository Methods Focused

```php
// ✅ GOOD - Each method has single purpose
public function find($id)
{
    return Product::find($id);
}

public function get(array $filters = [], array $with = [])
{
    $query = $this->query($filters);
    return $query->with($with)->get();
}

// Custom method for specific use case
public function findBySlug(string $slug)
{
    return Product::query()
        ->where('slug', $slug)
        ->first();
}

// ❌ AVOID - Too much logic in one method
public function findWithEverything($id)
{
    return Product::query()
        ->where('id', $id)
        ->with(['category', 'reviews', 'supplier'])
        ->withCount(['comments', 'likes', 'ratings'])
        ->first();
}
```

Better approach: Use filter and eager load parameters

```php
// Repository
public function get(array $filters = [], array $with = [])
{
    return $this->query($filters)->with($with)->get();
}

// Service or Controller
public function getProductFull($id)
{
    return $this->productRepository->get(
        ['id' => $id],
        ['category', 'reviews', 'supplier']
    );
}
```

### 2. Use Consistent Naming

The generated pattern uses specific method names. Stick to them:

```php
// Interface and Implementation
public function query(array $filters = [])        // Build query
public function get(array $filters = [])          // Get collection
public function getAll()                          // Get all records
public function find($id)                         // Find single
public function create(array $data)               // Create new
public function update($id, array $data)          // Update existing
public function delete($id)                       // Delete

// Custom methods add specific names
public function findBySlug(string $slug)          // Find by specific field
public function getActive(array $with = [])       // Get filtered set
public function countByCategory(string $cat)      // Get count with filter
```

Use in services:

```php
class ProductService
{
    // Delegate basic operations (match repository)
    public function find($id)
    {
        return $this->productRepository->find($id);
    }

    // Add service-specific methods
    public function getActiveWithReviews()
    {
        return $this->productRepository->getActive(['reviews']);
    }
}
```

### 3. Leverage Eloquent Methods

```php
// ✅ GOOD - Use Eloquent capabilities
public function getRecent(int $limit = 10): array
{
    return $this->model
        ->latest()
        ->limit($limit)
        ->get()
        ->toArray();
}

// ❌ AVOID - Reinventing the wheel
public function getRecent(int $limit = 10): array
{
    $records = $this->model->all();
    $sorted = [];
    foreach ($records as $record) {
        $sorted[] = $record;
    }
    rsort($sorted);
    return array_slice($sorted, 0, $limit);
}
```

### 4. Handle Errors Consistently

```php
// ✅ GOOD - Consistent error handling
public function getById(int $id): Product
{
    return $this->model->findOrFail($id);
    // Returns 404 automatically
}

public function delete(int $id): bool
{
    $product = $this->model->findOrFail($id);
    return $product->delete();
}

// ❌ AVOID - Inconsistent error handling
public function getById(int $id)
{
    return $this->model->find($id); // Returns null
}

public function delete(int $id)
{
    if (!$this->model->find($id)) {
        return false;
    }
    return $this->model->find($id)->delete();
}
```

### 5. Don't Mix Concerns

```php
// ❌ AVOID - Repository doing business logic
public function createProduct(array $data)
{
    // Business logic belongs in Service!
    if ($data['price'] < 0) {
        throw new Exception('Invalid price');
    }
    $data['slug'] = str()->slug($data['name']);
    return $this->model->create($data);
}

// ✅ GOOD - Repository only handles data access
public function create(array $data): Product
{
    return $this->model->create($data);
}

// Business logic in Service
public function createProduct(array $data): Product
{
    if ($data['price'] < 0) {
        throw new InvalidArgumentException('Price cannot be negative');
    }
    $data['slug'] = str()->slug($data['name']);
    return $this->repository->create($data);
}
```

### 6. Use Interface in Type Hints

```php
// ✅ GOOD - Depend on interface, not implementation
public function __construct(ProductRepositoryInterface $repository)
{
    $this->repository = $repository;
}

// ❌ AVOID - Coupling to concrete class
public function __construct(ProductRepository $repository)
{
    $this->repository = $repository;
}
```

### 7. A Repository Must Only Touch Its Own Model

A repository is scoped to exactly one entity. It must never call, query, or update another model directly — that is cross-entity orchestration and belongs in the Service layer.

```php
// ❌ AVOID - StockMovementRepository reaching into Product
class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function create(array $data)
    {
        // Wrong layer: this repository owns StockMovement, not Product
        $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
        $product->increment('stock', $data['quantity_change']);

        return StockMovement::create($data);
    }
}
```

```php
// ✅ GOOD - each repository only touches its own model
class ProductRepository implements ProductRepositoryInterface
{
    public function adjustStock($id, int $delta)
    {
        $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($product->stock + $delta < 0) {
            throw ValidationException::withMessages([
                'quantity_change' => "Insufficient stock for {$product->name}.",
            ]);
        }

        $product->increment('stock', $delta);

        return $product;
    }
}

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function create(array $data)
    {
        return StockMovement::create($data);
    }
}

// Orchestration across repositories belongs in the Service layer
class StockMovementService
{
    protected $stockMovementRepository;
    protected $productRepository;

    public function __construct(
        StockMovementRepositoryInterface $stockMovementRepository,
        ProductRepositoryInterface $productRepository,
    ) {
        $this->stockMovementRepository = $stockMovementRepository;
        $this->productRepository = $productRepository;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $this->productRepository->adjustStock($data['product_id'], $data['quantity_change']);

            return $this->stockMovementRepository->create($data);
        });
    }
}
```

**Why:** keeps each repository's responsibility limited to one Eloquent model, keeps data-consistency logic (locking, transactions, validation across entities) in one place, and avoids repositories silently depending on each other's tables.

### 8. Add Custom Repository Methods

```php
// Interface
interface ProductRepositoryInterface
{
    public function all(): array;
    public function getById(int $id): Product;
    public function create(array $data): Product;
    public function update(int $id, array $data): Product;
    public function delete(int $id): bool;
    
    // Custom methods
    public function getActive(): array;
    public function findBySlug(string $slug): ?Product;
}

// Implementation
class ProductRepository implements ProductRepositoryInterface
{
    // Required methods...

    public function getActive(): array
    {
        return $this->model
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }
}

// Service uses custom methods
class ProductService
{
    public function getActiveCatalog(): array
    {
        return $this->repository->getActive();
    }

    public function resolveProduct(string $slug): ?Product
    {
        return $this->repository->findBySlug($slug);
    }
}
```

### 9. Queries Only Happen in the Repository

All Eloquent query building — `where`, `whereHas`, `groupBy`, `orderBy`, aggregates (`sum`, `count`), joins, etc. — must live in a Repository method. Services must call a repository method to get data; they must never build or execute a query against a Model directly.

```php
// ❌ AVOID - Service querying a Model directly
class ReportService
{
    public function salesTotals(Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'totalSales' => (float) $query->clone()->sum('total'),
            'totalTransactions' => $query->clone()->count(),
        ];
    }
}
```

```php
// ✅ GOOD - Query lives in the repository; the service only orchestrates
class TransactionRepository implements TransactionRepositoryInterface
{
    public function salesTotals(Carbon $dateFrom, Carbon $dateTo): array
    {
        $query = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'totalSales' => (float) $query->clone()->sum('total'),
            'totalTransactions' => $query->clone()->count(),
        ];
    }
}

class ReportService
{
    protected $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function salesTotals(Carbon $dateFrom, Carbon $dateTo): array
    {
        return $this->transactionRepository->salesTotals($dateFrom, $dateTo);
    }
}
```

If an existing repository method doesn't fit a new read/aggregate need, add a new method to that entity's repository (and its interface) rather than reaching for the Model from a Service, Controller, or another Repository. This keeps every query discoverable in one place per entity and keeps [rule 7](#7-a-repository-must-only-touch-its-own-model) (one repository, one model) intact.

**Why:** if query logic is scattered across services, there is no single place to look when a query needs to change, no way to swap the data source per rule 2, and no way to unit-test query logic in isolation from business logic.

---

## Testing

### Unit Testing Repository

```php
<?php

namespace Tests\Unit\Repositories;

use App\Models\Product;
use App\Repositories\Product\ProductRepository;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product);
    }

    public function test_can_get_all_products()
    {
        Product::factory()->count(3)->create();
        
        $products = $this->repository->all();
        
        $this->assertCount(3, $products);
    }

    public function test_can_get_product_by_id()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $result = $this->repository->getById($product->id);
        
        $this->assertEquals('Test Product', $result->name);
    }

    public function test_can_create_product()
    {
        $data = [
            'name' => 'New Product',
            'price' => 99.99,
        ];
        
        $product = $this->repository->create($data);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Product',
        ]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create();
        
        $this->repository->update($product->id, ['name' => 'Updated']);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated',
        ]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();
        
        $deleted = $this->repository->delete($product->id);
        
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

### Feature Testing Service

```php
<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Services\ProductService;
use Mockery\Mock;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private Mock $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = $this->mock(ProductRepositoryInterface::class);
        $this->service = new ProductService($this->mockRepository);
    }

    public function test_create_product_validates_price()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->createProduct([
            'name' => 'Product',
            'price' => -10, // Invalid
        ]);
    }

    public function test_create_product_generates_slug()
    {
        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with(\Mockery::hasKey('slug', 'test-product'))
            ->andReturn(new Product);
        
        $this->service->createProduct([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
    }

    public function test_apply_discount_reduces_price()
    {
        $product = new Product(['price' => 100]);
        
        $this->mockRepository
            ->shouldReceive('getById')
            ->once()
            ->andReturn($product);
        
        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, \Mockery::hasKey('price', 80)) // 20% discount
            ->andReturn($product);
        
        $this->service->applyDiscount(1, 20);
    }
}
```

---

## Advantages

### 1. Testability

```php
// Easy to mock in tests
$mockRepository = $this->createMock(ProductRepositoryInterface::class);
$service = new ProductService($mockRepository);

// No need for database
$mockRepository->expects($this->once())
    ->method('getById')
    ->willReturn(new Product);
```

### 2. Flexibility

**Swap implementations:**

```php
// Easily switch data source
class ApiProductRepository implements ProductRepositoryInterface
{
    // Fetch from external API instead of database
    public function getById(int $id)
    {
        return Http::get("https://api.example.com/products/{$id}");
    }
}

// Just change service provider binding
$this->app->bind(
    ProductRepositoryInterface::class,
    ApiProductRepository::class
);
```

### 3. Reusability

```php
// Same service logic for multiple controllers
class ProductController extends Controller
{
    public function __construct(ProductService $service) { }
}

class ApiProductController extends Controller
{
    public function __construct(ProductService $service) { }
}

class AdminProductController extends Controller
{
    public function __construct(ProductService $service) { }
}

// All share the same business logic
```

### 4. Maintainability

```php
// Database schema changes? Only update repository
// Business logic changes? Only update service
// Endpoint changes? Only update controller

// Changes in one layer don't affect others
```

### 5. Clean Code

```php
// Clear separation of concerns
// Easy to understand what each layer does
// Easy to find where to make changes
// Easy to add new features
```

---

## Common Patterns

### Filter Pattern (get with filters)

```php
// Repository - handles filtering
public function query(array $filters = [])
{
    $query = Product::query();
    
    foreach ($filters as $key => $value) {
        if (is_null($value) || $value === '') {
            continue;
        }
        $query->where($key, $value);
    }
    
    return $query;
}

public function get(array $filters = [], array $with = [])
{
    return $this->query($filters)->with($with)->get();
}

// Service - can wrap with business logic
public function getActive(array $with = [])
{
    return $this->productRepository->get(['is_active' => true], $with);
}

// Controller
public function index()
{
    // Can pass filters to service if needed
    return view('app.product.index');
}

// Or explicitly fetch data
public function getActiveList()
{
    $products = $this->productService->getActive(['category']);
    return view('app.product.list', ['products' => $products]);
}
```

### Eager Loading Pattern

```php
// Repository
public function get(array $filters = [], array $with = [])
{
    $query = $this->query($filters);
    return $query->with($with)->get();
}

// Service
public function getProductsWithDetails()
{
    return $this->productRepository->get(
        [],
        ['category', 'reviews', 'supplier']  // Eager load relations
    );
}

// Controller
public function show($id)
{
    $item = $this->productService->find($id);  // Basic
    
    // Or with relations
    $items = $this->productService->get(
        ['id' => $id],
        ['category', 'reviews']
    );
    
    return view('app.product.show', ['item' => $item]);
}
```

### Query Builder Pattern

```php
// Repository - return query builder for complex queries
public function query(array $filters = [])
{
    $query = Product::query();
    
    foreach ($filters as $key => $value) {
        if (is_null($value) || $value === '') {
            continue;
        }
        $query->where($key, $value);
    }
    
    return $query;
}

// Service or Repository - use query for complex logic
public function search(string $term)
{
    return $this->query()
        ->where('name', 'like', "%{$term}%")
        ->orWhere('description', 'like', "%{$term}%")
        ->get();
}

public function getExpensiveItems(float $minPrice)
{
    return $this->query()
        ->where('price', '>=', $minPrice)
        ->orderBy('price', 'desc')
        ->limit(10)
        ->get();
}
```

### Foreign Key Pattern

```php
// Controller auto-injects related services
class ProductController extends Controller
{
    protected $productService;
    protected $categoryService;
    protected $supplierService;

    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
        SupplierService $supplierService
    ) {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->supplierService = $supplierService;
    }

    // Get foreign data for forms
    private function foreignData()
    {
        return [
            'categories' => $this->categoryService->getAll(),
            'suppliers' => $this->supplierService->getAll(),
        ];
    }

    // Pass foreign data to views
    public function create()
    {
        return view('app.product.create', $this->foreignData());
    }

    public function edit($id)
    {
        $item = $this->productService->find($id);
        
        return view('app.product.edit', [
            'item' => $item,
        ] + $this->foreignData());
    }
}
```

### Form Validation Pattern

```php
// Auto-generated Form Requests
// app/Http/Requests/Product/StoreProductRequest.php
class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:products',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
        ];
    }
}

// Controller uses validation automatically
public function store(StoreProductRequest $request)
{
    // Data is already validated
    $item = $this->productService->create($request->validated());
    return redirect()->route('products.show', $item);
}
```

---

## File Structure Reference

### Generated Files from CRUD Generator

When you run `php artisan make:rsc Product`, the generator creates:

```
app/
├── Models/Product.php
├── Repositories/Product/
│   ├── ProductRepositoryInterface.php
│   └── ProductRepository.php
├── Services/ProductService.php
└── Livewire/Products/
    ├── ProductIndex.php
    ├── ProductCreate.php
    └── ProductEdit.php

app/Http/Requests/Product/
├── StoreProductRequest.php
└── UpdateProductRequest.php

resources/views/livewire/products/
├── product-index.blade.php
├── product-create.blade.php
└── product-edit.blade.php
```

> No Controller or `show` view is generated. See [`CRUD_GENERATOR_SHOWCASE.md`](CRUD_GENERATOR_SHOWCASE.md) for the current architecture.

---

## Quick Reference

### Repository Interface Methods

| Method | Parameters | Returns | Purpose |
|--------|-----------|---------|---------|
| `query()` | `$filters = []` | QueryBuilder | Build query with filters |
| `get()` | `$filters = []`, `$with = []` | Collection | Get records with relations |
| `getAll()` | - | Collection | Get all records |
| `find()` | `$id` | Model\|null | Find single record |
| `create()` | `$data` | Model | Create new record |
| `update()` | `$id`, `$data` | Model | Update record |
| `delete()` | `$id` | int | Delete record |

### Service Pattern

Services delegate to repository and can add business logic:

| Method | Purpose |
|--------|---------|
| `get()` | Delegate to repository->get() |
| `getAll()` | Delegate to repository->getAll() |
| `find()` | Delegate to repository->find() |
| `create()` | Delegate or add validation/logic |
| `update()` | Delegate or add validation/logic |
| `delete()` | Delegate to repository->delete() |
| `custom*()` | Add business logic methods |

### Controller RESTful Actions

| HTTP | Route | Controller Method | Purpose |
|------|-------|-------------------|---------|
| GET | /products | index() | List view |
| GET | /products/{id} | show($id) | Detail view |
| GET | /products/create | create() | Create form |
| POST | /products | store() | Save new record |
| GET | /products/{id}/edit | edit($id) | Edit form |
| PUT | /products/{id} | update() | Update record |
| DELETE | /products/{id} | destroy($id) | Delete record |

### Data Flow Summary

```
User Request
    ↓
HTTP Route (routes/web.php)
    ↓
Controller receives request
    ├─ Validate with Form Request
    ├─ Call Service method
    ├─ Get foreign data if needed
    └─ Return view with data
    ↓
Service method
    ├─ Add business logic (optional)
    └─ Delegate to Repository
    ↓
Repository method
    ├─ Build/execute query
    └─ Return data to Service
    ↓
Service returns to Controller
    ↓
Controller returns response
    ↓
User sees result
```

---

## Summary

The **Repository Pattern** provides:

✅ **Clean Architecture** - Clear separation of concerns  
✅ **Testability** - Easy to unit test with mocks  
✅ **Maintainability** - Easy to find and modify code  
✅ **Flexibility** - Easy to swap implementations  
✅ **Reusability** - Share logic across controllers  
✅ **Scalability** - Easy to extend with new features  

**Status:** ✅ Production Ready  
**Last Updated:** June 26, 2026  
**Version:** 1.0  
**For CRUD Generation:** See `docs/CRUD_GENERATOR.md`

---

**Ready to architect. Happy coding!** 🏗️
