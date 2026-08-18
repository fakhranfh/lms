<?php

namespace Tests;

use App\Support\CurrentSchool;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * Guard against RefreshDatabase running migrations against a
     * non-test database. A stale bootstrap/cache/config.php freezes env()
     * values from .env, silently ignoring phpunit.xml's DB_DATABASE
     * override and pointing tests at the real dev database.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        if (! $this->app->environment('testing')) {
            throw new \RuntimeException(
                'Refusing to run tests: APP_ENV is not "testing". This usually means '.
                'bootstrap/cache/config.php is stale — run `php artisan config:clear`.'
            );
        }

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Refusing to run tests against database [{$database}] — expected a ".
                'database name ending in "_test". This usually means bootstrap/cache/'.
                'config.php is stale — run `php artisan config:clear`.'
            );
        }
    }

    public function actingAs($user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user->school_id) {
            $this->app->make(CurrentSchool::class)->setSchoolId($user->school_id);
        }

        return $this;
    }
}
