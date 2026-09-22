<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    /**
     * The models whose lifecycle events should be audited.
     *
     * @var list<class-string>
     */
    private const AUDITED_MODELS = [
        Course::class,
        Role::class,
        User::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::AUDITED_MODELS as $model) {
            $model::observe(AuditableObserver::class);
        }
    }
}
