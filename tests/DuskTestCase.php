<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Guard against DatabaseMigrations running migrate/rollback against a
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

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-logging',
            '--log-level=3',
            '--silent',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
