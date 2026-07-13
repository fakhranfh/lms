# Getting Started — Complete Guide

This guide walks you through using **Laravel 13 Boilerplate** from scratch: installation, configuration, building new features, testing, and deployment.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Installation](#2-installation)
3. [Environment Configuration](#3-environment-configuration)
4. [Running the Application](#4-running-the-application)
5. [Directory Structure](#5-directory-structure)
6. [Authentication Flow](#6-authentication-flow)
7. [Creating a New CRUD Module](#7-creating-a-new-crud-module)
8. [Repository Pattern Architecture](#8-repository-pattern-architecture)
9. [Frontend (Tailwind CSS + Vite)](#9-frontend-tailwind-css--vite)
10. [Testing](#10-testing)
11. [Useful Artisan Commands](#11-useful-artisan-commands)
12. [Email Configuration](#12-email-configuration)
13. [Deployment](#13-deployment)

---

## 1. Prerequisites

Make sure the following are installed on your machine:

| Requirement | Minimum Version |
|---|---|
| PHP | 8.4+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| Database | MySQL 8+ or SQLite |
| Git | — |

Verify versions:

```bash
php -v
composer -V
node -v
npm -v
```

---

## 2. Installation

### Clone the Repository

```bash
git clone https://github.com/fakhranfh/laravel-13-boilerplate
cd laravel-13-boilerplate
```

### One-Command Setup (Recommended)

Run the following single command. It will:
- Install all PHP and Node.js dependencies
- Copy `.env.example` → `.env`
- Generate `APP_KEY`
- Run database migrations
- Build frontend assets

```bash
composer run setup
```

### Manual Setup (Optional)

If you prefer step-by-step:

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Install Node.js dependencies
npm install

# 5. Build frontend assets
npm run build

# 6. Run migrations
php artisan migrate
```

---

## 3. Environment Configuration

Edit the `.env` file to match your local setup:

### Database

**MySQL (recommended for production):**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_boilerplate
DB_USERNAME=root
DB_PASSWORD=
```

**SQLite (good for local development):**

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, etc. are not needed
```

Create the SQLite database file:

```bash
touch database/database.sqlite
php artisan migrate
```

### Application

```env
APP_NAME="Your App Name"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Session & Queue

This boilerplate uses the database driver for both session and queue, so Redis is not required:

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Email

For development, use `log` so emails are written to the log file instead of being sent:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

For production, switch to SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

If no mail server is available at all (e.g. local dev without SMTP), disable email-dependent
features instead of configuring `log`:

```env
FEATURE_EMAIL_ENABLED=false
```

This turns off Fortify's email verification and password reset, and applies profile email
changes immediately instead of queuing a pending-verification step. The "Forgot Password?" link
is hidden on the login page while disabled. Re-enable it any time by setting the flag back to
`true`; no code changes are needed. See `config/features.php`.

---

## 4. Running the Application

### Development Mode

The following command starts three processes concurrently:
- PHP development server (`php artisan serve`)
- Queue listener (`php artisan queue:listen`)
- Vite dev server with hot reload (`npm run dev`)

```bash
composer run dev
```

Access the app at: `http://localhost:8000`

### Frontend Only

If you only need to rebuild assets without starting a server:

```bash
npm run build   # One-time production build
npm run dev     # Watch mode with hot reload
```

### Streaming Logs

```bash
php artisan pail    # Stream logs in real-time in the terminal
```

---

## 5. Directory Structure

```
laravel-13-boilerplate/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controllers for non-CRUD/manual routes
│   │   └── Requests/           # Form Requests for input validation
│   ├── Livewire/                # Livewire components (CRUD Index/Create/Edit per module)
│   │   └── {Plural}/
│   ├── Models/                 # Eloquent models
│   ├── Repositories/           # Repository interfaces & implementations
│   │   └── {Model}/
│   │       ├── {Model}RepositoryInterface.php
│   │       └── {Model}Repository.php
│   ├── Services/               # Business logic layer
│   │   └── {Model}Service.php
│   └── Providers/
│       └── AppServiceProvider.php   # Repository-to-interface bindings
├── database/
│   ├── migrations/             # Database schema
│   ├── factories/              # Model factories for testing
│   └── seeders/                # Seed data
├── resources/
│   ├── views/
│   │   ├── livewire/            # Blade views per CRUD module (paired with app/Livewire)
│   │   ├── auth/               # Authentication views
│   │   ├── components/         # Reusable Blade components (incl. livewire-data-table)
│   │   └── layouts/            # Main layouts (app, guest) — app.blade.php hosts the global delete-confirm modal
│   ├── css/
│   │   └── app.css             # Tailwind CSS entry point
│   └── js/
│       └── app.js              # JavaScript entry point
├── routes/
│   ├── web.php                 # Main application routes
│   └── auth.php                # Authentication routes
├── tests/
│   ├── Feature/                # Pest feature tests
│   ├── Unit/                   # Pest unit tests
│   └── Browser/                # Laravel Dusk browser tests
│   └── k6/                     # Stress tests
└── docs/                       # Project documentation
```

---

## 6. Authentication Flow

This boilerplate uses **Laravel Fortify** as the authentication backend. Here is the full flow:

### Registration

1. User fills in the form at `/register` (name, email, password)
2. Password is validated in real-time: minimum 8 characters, uppercase, lowercase, number, symbol
3. After successful registration, a verification email is sent
4. User is redirected to the email verification notice page

### Login

1. User enters email + password at `/login`
2. If credentials are valid but email is not verified → redirected to the verification notice
3. If email is verified → redirected to `/dashboard`

### Email Verification

1. User opens the email and clicks the verification link
2. If using `MAIL_MAILER=log`, open `storage/logs/laravel.log` and search for the verification URL
3. After clicking the link → user is redirected to the dashboard

### Forgot Password

1. Open `/forgot-password` and enter email address
2. Check the email inbox (or log file) for the reset link
3. Click the link → enter a new password at `/reset-password`

### Changing Email (Profile)

Changing email on the profile page triggers a re-verification flow:

1. User enters a new email at `/profile`
2. A verification email is sent to the new address
3. The old email remains active until the new one is verified
4. After verification → email is updated

---

## 7. Creating a New CRUD Module

The boilerplate includes a CRUD generator that scaffolds all necessary files at once.

### Using the CRUD Generator

```bash
php artisan make:rsc ModelName --label="Display Label"
```

**Example:**

```bash
php artisan make:rsc Product --label="Products"
```

The generator will interactively ask for column configuration:

```
Column name (or 'done' to finish): name
Column type [string]: string
Form input type [text]: text

Column name (or 'done' to finish): price
Column type [string]: decimal
Form input type [text]: number

Column name (or 'done' to finish): done
```

**Files generated automatically:**

```
app/
├── Livewire/Products/
│   ├── ProductIndex.php
│   ├── ProductCreate.php
│   └── ProductEdit.php
├── Http/Requests/Product/
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
├── Models/Product.php
├── Repositories/Product/
│   ├── ProductRepositoryInterface.php
│   └── ProductRepository.php
└── Services/ProductService.php

database/
├── migrations/xxxx_create_products_table.php
└── migrations/xxxx_add_products_permissions_and_assign_to_admin.php

resources/views/livewire/products/
├── product-index.blade.php   # List view via <x-livewire-data-table>
├── product-create.blade.php  # Create form
└── product-edit.blade.php    # Edit form
```

Plain `Route::get` route entries and the sidebar menu entry (`config/sidebar.php`) are also added automatically. The delete confirmation dialog is a single global modal in `resources/views/layouts/app.blade.php`, dispatched via Alpine/Livewire browser events — it is not generated per module.

### Run Migrations After Generating

```bash
php artisan migrate
```

### Accessing the New Module

Open: `http://localhost:8000/products`

### Deleting a CRUD Module

```bash
php artisan delete:rsc Product

# Also delete migration files
php artisan delete:rsc Product --migrations
```

> Full reference: [`docs/CRUD_GENERATOR.md`](CRUD_GENERATOR.md)

---

## 8. Repository Pattern Architecture

All modules — whether generated or manually created — follow this pattern:

```
Request → Controller → Service → Repository Interface → Repository → Database
```

### Why Repository Pattern?

- **Separation of concerns** — controllers never touch queries directly
- **Testability** — repositories can be mocked in unit tests
- **Consistency** — the entire codebase follows the same structure

### Creating a Module Manually (Without the Generator)

If you need to create a module without the generator, you must follow this structure:

**1. Interface:**

```php
// app/Repositories/Post/PostRepositoryInterface.php
interface PostRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): Post;
    public function create(array $data): Post;
    public function update(Post $post, array $data): Post;
    public function delete(Post $post): bool;
}
```

**2. Implementation:**

```php
// app/Repositories/Post/PostRepository.php
class PostRepository implements PostRepositoryInterface
{
    public function all(): Collection
    {
        return Post::all();
    }
    // ...
}
```

**3. Service:**

```php
// app/Services/PostService.php
class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $repository
    ) {}
}
```

**4. Bind in AppServiceProvider:**

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(PostRepositoryInterface::class, PostRepository::class);
```

> Full guide: [`docs/REPOSITORY_PATTERN.md`](REPOSITORY_PATTERN.md)

---

## 9. Frontend (Tailwind CSS + Vite)

### Entry Points

- CSS: `resources/css/app.css`
- JS: `resources/js/app.js`

### Adding Blade Components

Create a new component in `resources/views/components/` and use it in any view:

```blade
<x-component-name :prop="$value" />
```

### Customizing Theme / Colors

Edit `resources/css/app.css`. This boilerplate uses Tailwind CSS v4, which no longer requires a `tailwind.config.js` file — configuration is done via CSS custom properties.

### Dark Mode

All generated views support dark mode using the `dark:` prefix. Ensure the `dark` class is on the `<html>` element to activate it.

### Frontend Changes Not Reflecting?

```bash
npm run build   # Rebuild for production

# Or start the dev server with hot reload
npm run dev
```

---

## 10. Testing

### Run All Tests

```bash
php artisan test --compact
```

### Run Specific Tests

```bash
# Filter by test name
php artisan test --compact --filter=LoginTest

# Filter by file
php artisan test --compact tests/Feature/Auth/LoginTest.php
```

### Creating New Tests

```bash
# Feature test (most common)
php artisan make:test --pest SomeFeatureTest

# Unit test
php artisan make:test --pest SomeUnitTest --unit
```

### Browser Tests (Laravel Dusk)

Dusk runs a headless Chrome browser to test the UI end-to-end.

**First-time Dusk setup:**

```bash
php artisan dusk:install
php artisan dusk:chrome-driver --detect
```

**Run all Dusk tests:**

```bash
php artisan dusk
```

**Run a specific Dusk test:**

```bash
php artisan dusk --filter=RegistrationTest
```

Screenshots are saved to `tests/Browser/screenshots/`.

> Full Dusk guide: [`docs/dusk/TESTING.md`](dusk/TESTING.md)

### Testing Conventions

- Use factories to create test data: `User::factory()->create()`
- Check factory states before setting up model attributes manually
- Use `$this->faker` or `fake()` — follow the convention already used in sibling test files

---

## 11. Useful Artisan Commands

### Development

```bash
composer run dev              # Start all servers concurrently
php artisan serve             # PHP server only
php artisan pail              # Stream logs in real-time
php artisan route:list        # List all registered routes
php artisan route:list --except-vendor  # Application routes only
```

### Database

```bash
php artisan migrate               # Run pending migrations
php artisan migrate:fresh         # Drop all tables and re-migrate
php artisan migrate:fresh --seed  # Fresh migrate + run seeders
php artisan db:seed               # Run seeders only
```

### CRUD Generator

```bash
php artisan make:rsc ModelName --label="Label"   # Scaffold a CRUD module
php artisan delete:rsc ModelName                 # Remove a CRUD module
php artisan delete:rsc ModelName --migrations    # Remove including migrations
```

### Creating Files Manually

```bash
php artisan make:model ModelName -mf   # Model + migration + factory
php artisan make:controller NameController --resource
php artisan make:request Store{Model}Request
php artisan make:test --pest NameTest
php artisan make:class NameClass       # Generic PHP class
```

### Cache & Optimization

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear   # Clear all caches at once
```

### Code Style

```bash
vendor/bin/pint --dirty      # Format only changed files
vendor/bin/pint              # Format all PHP files
```

---

## 12. Email Configuration

### Development (Log Driver)

Emails are not sent to a real inbox — they are written to the log file instead.

```env
MAIL_MAILER=log
```

To read "sent" emails:

```bash
# Stream logs in real-time
php artisan pail
```

Look for the verification URL in the log output:

```
http://localhost:8000/verify-email/...
```

### Production (SMTP)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Queue for Email Delivery

Emails are dispatched through the queue to avoid blocking HTTP requests. Make sure the queue worker is running:

```bash
php artisan queue:listen --tries=1
```

In production, use Supervisor or Laravel Horizon to keep the queue worker alive.

---

## 13. Deployment

### Production `.env` Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_DATABASE=your_production_database

MAIL_MAILER=smtp
# ... SMTP configuration
```

### Deployment Steps

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies (without dev)
composer install --no-dev --optimize-autoloader

# 3. Build frontend assets
npm ci
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue worker
php artisan queue:restart
```

### Using Laravel Cloud

The easiest way to deploy a Laravel application to production is [Laravel Cloud](https://cloud.laravel.com/). It handles scaling, SSL, automated deployments, and queue workers out of the box.

---

## Documentation Reference

| Document | Description |
|---|---|
| [`docs/USERGUIDE.md`](USERGUIDE.md) | End-user feature walkthrough with screenshots |
| [`docs/CRUD_GENERATOR.md`](CRUD_GENERATOR.md) | Full `make:rsc` command reference |
| [`docs/REPOSITORY_PATTERN.md`](REPOSITORY_PATTERN.md) | Repository layer architecture guide |
| [`docs/DATATABLE_INTEGRATION.md`](DATATABLE_INTEGRATION.md) | Server-side DataTables integration |
| [`docs/dusk/TESTING.md`](dusk/TESTING.md) | Dusk browser test setup and guide |
