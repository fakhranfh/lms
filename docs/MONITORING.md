# Monitoring

## Horizon

- Dashboard: `/horizon` (same domain the app is served on, or `admin.<domain>` since it's linked from the admin sidebar).
- Access is gated by the `viewHorizon` gate in `App\Providers\HorizonServiceProvider::gate()` — only users with the `Admin` role pass.
- Start a worker locally: `php artisan horizon` (requires `QUEUE_CONNECTION=redis`; Horizon only supports the Redis driver).
- Metrics stay blank until `horizon:snapshot` runs — it's scheduled every 5 minutes in `routes/console.php`.
- `config/horizon.php` keeps 24h of recent/pending/completed job history and 7 days of failed/monitored job history.

### Interpreting job status

| Status | Meaning |
|---|---|
| Pending | Queued, not yet picked up by a worker |
| Active | Currently executing |
| Completed | Finished successfully (trimmed after 24h) |
| Failed | Exhausted retries or threw an unhandled exception |

### Retrying failed jobs

From the Horizon dashboard's "Failed Jobs" tab, click a job to inspect its payload/exception, then use "Retry". From the CLI: `php artisan queue:retry <uuid>` or `php artisan queue:retry all`.

### Troubleshooting queue failures

1. Check `php artisan queue:health` — reports Redis connectivity, pending job count, and failed job count (exit 1 if Redis is unreachable).
2. For AI grading specifically, `php artisan grading:health` reports queue depth against `GradingQueueHealthService::DEPTH_ALERT_THRESHOLD`.
3. Inspect exception traces on the Horizon "Failed Jobs" detail view.
4. Confirm the worker/supervisor process is actually running (`php artisan horizon` in production, or `php artisan queue:work --timeout=45` for local dev without Redis/Horizon).

## Pulse

- Dashboard: `/pulse`, linked from the admin sidebar.
- Access is gated by the `viewPulse` gate defined in `App\Providers\AppServiceProvider::boot()` — only users with the `Admin` role pass.
- Retention: 7 days (`PULSE_STORAGE_KEEP` / `config/pulse.php`).
- Enabled recorders: requests, jobs, exceptions, queues, slow queries, servers. `CacheInteractions` and `SlowOutgoingRequests` are disabled by default to reduce overhead — re-enable via `PULSE_CACHE_INTERACTIONS_ENABLED` / `PULSE_SLOW_OUTGOING_REQUESTS_ENABLED` if needed.
- `pulse:check` must run as a persistent process for the Servers card to populate.

## Performance metrics to watch

- Horizon: queue wait time (`waits` config, alerts past 60s on `redis:default`), job throughput, failed-job rate.
- Pulse: slow requests/queries/jobs (>1000ms threshold by default), exception rate, per-server CPU/memory.
