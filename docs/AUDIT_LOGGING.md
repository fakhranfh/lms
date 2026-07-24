# Audit Logging

## How it works

Every school-scoped and compliance-relevant model is wrapped with an Eloquent observer (`App\Observers\AuditableObserver`), registered per-model in `App\Providers\AuditLogServiceProvider::boot()`. On `created`, `updated`, `deleted`, and `restored` events, the observer writes an immutable `App\Models\AuditLog` row and mirrors the same event to the `audit` log channel (`storage/logs/audit.log`, JSON, rotated daily).

`AuditLog` records cannot be updated or deleted through the model — `update()` and `delete()` both throw `RuntimeException`. The only supported removal path is the retention cleanup command, which uses a query-builder mass delete (bypassing the per-model guard by design).

## Audited models

`Course`, `Module`, `Lesson`, `Assignment`, `Submission`, `Role`, `User`, `PricingTier`, `SchoolTier`, `TierChange`, `PaymentTransaction`.

Add or remove models by editing the `AUDITED_MODELS` list in `App\Providers\AuditLogServiceProvider`.

## What counts as a change

- `updated_at`/`created_at` changing alone does not produce an audit log (see `AuditableObserver::relevantChanges()`).
- All other changed fields in a single `save()`/`update()` call are recorded as one audit log with `old_values` (pre-change state for only the changed keys) and `new_values` (the changed keys).
- `created` events store the full initial attribute set in `new_values`; `deleted` events store the full final attribute set in `old_values`.

## Querying audit logs

```php
use App\Models\AuditLog;

// All changes to a specific course
AuditLog::forModel($course)->latest('created_at')->get();

// All changes to any Course
AuditLog::forType(\App\Models\Course::class)->get();

// All actions by a specific user
AuditLog::byUser($user)->latest('created_at')->get();

// All activity within a school (tenant)
AuditLog::inTenant($school->id)->get();
```

## Retention policy

Audit logs are kept for 1 year by default. `php artisan audit-logs:cleanup` (scheduled monthly in `routes/console.php`) deletes rows older than the retention window:

```bash
php artisan audit-logs:cleanup --older-than=1-year
php artisan audit-logs:cleanup --older-than=90-days
```

## Performance considerations

- Query with the provided scopes (`forModel`, `forType`, `byUser`, `inTenant`) rather than scanning the whole table — the migration indexes `(school_id, auditable_type, auditable_id)`, `(user_id, created_at)`, and `(auditable_type, created_at)`.
- Avoid unscoped `AuditLog::all()` in production; always filter by tenant, model, or date range.
- The admin dashboard (`/audit-logs`) paginates at 100 rows per page.
