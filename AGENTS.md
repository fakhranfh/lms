<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/dusk (DUSK) - v8
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== testing requirements ===

# Testing Requirements

This project requires three types of tests:

1. **Feature Tests (Pest)**: Unit and feature tests using Pest v4.
   - Create with: `php artisan make:test --pest {name}`
   - Run with: `php artisan test --compact`
   - Most tests should be feature tests; only use `--unit` flag for isolated unit tests.

2. **Browser Tests (Laravel Dusk)**: End-to-end browser automation tests.
   - Run with: `php artisan dusk`
   - Tests user interactions in a real browser.
   - Requires `php artisan serve` running on port 8000.

3. **Stress Tests (k6)**: Performance and load testing.
   - Written in JavaScript using k6.
   - Tests application performance under load.
   - Session cookie name follows `APP_NAME` format: `laravel-13-boilerplate-session`.
   - Requires DB migrations to be fresh before running.

When implementing features or fixing bugs, ensure all three test types are considered and appropriate tests are created for the changes.

=== project-session-context ===

# Project Session Context (2026-06-22)

Key discoveries about this specific Laravel 13 boilerplate project.

## CSRF in Tests

- Laravel 13 renamed `VerifyCsrfToken` → `PreventRequestForgery`
- `PreventRequestForgery::runningUnitTests()` checks `runningInConsole() && runningUnitTests()`, so CSRF is **NOT auto-disabled** in HTTP feature tests
- Fix: `tests/TestCase.php` has `$this->withoutMiddleware(PreventRequestForgery::class)` in `setUp()`

## Dusk Browser Tests

- `DatabaseMigrations` drops `sessions` table between test classes → session-based login flows become flaky
- `DatabaseTransactions` **does NOT work** for Dusk (server runs in separate process, can't see transaction)
- HTML5 `required` attributes block form submission in Dusk → use `$browser->script()` before `press()`
- `$browser->script()` returns array, **cannot chain** — must call before the chain
- `$browser->press('Logout')` works for `<button type="submit">` inside `<form>`

## k6 Stress Testing

- `open()` resolves relative to **script directory**, not `pwd`
- Cookie jar does NOT share between `setup()` and `default()` — prefer per-VU flows
- k6 v2 cookie jar auto-manages cookies within a single VU iteration
- PHP built-in server handles only 1-2 concurrent requests
- Session cookie name uses `APP_NAME`: `laravel-13-boilerplate-session`
- Extract cookies from `res.headers['Set-Cookie']` regex: `/([a-z0-9_-]+session)=([^;]+)/i`

## Blade Template Issues Fixed

- `reset-password.blade.php`: added `action="{{ route('password.update') }}"`, `@csrf`, hidden `token` + `email` inputs
- `reset-password.blade.php`: use `$request->route('token')` not `$request->token` for route params
- `login.blade.php`: display both `session('status')` and `session('success')` for flash messages
- `CustomPasswordResetResponse` flashes `status`, not `success`

## Password Rules

- `PasswordValidationRules`: `min(8)->mixedCase()->numbers()->symbols()->uncompromised()`
- `uncompromised()` hits external API — mock with `Http::fake()` in feature tests, use random long passwords in k6/browser tests
- `Password::getRepository()->delete($user)` deletes **ALL** tokens for that email, not just the used one

## Fortify Notes

- `MustVerifyEmail` redirects to `/email/verify` after registration, not `/dashboard`
- `password.reset` route uses `{token}` in URL path, not query string
- `password.update` is POST `/reset-password` (no token in URL)
- `home` config default is `/dashboard`

## Database

- Dev DB: MySQL (`laravel-13-boilerplate`), Test DB: MySQL (`laravel-13-boilerplate_test`)
- SQLite not available on this machine → phpunit.xml uses MySQL
- `php artisan dusk` needs `php artisan serve` running on port 8000
- DB tables get wiped after `php artisan dusk` (DatabaseMigrations) → re-migrate before k6

</laravel-boost-guidelines>
