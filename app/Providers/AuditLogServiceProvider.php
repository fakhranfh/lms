<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\Role;
use App\Models\SchoolTier;
use App\Models\Submission;
use App\Models\TierChange;
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
        Module::class,
        Lesson::class,
        Assignment::class,
        Submission::class,
        Role::class,
        User::class,
        PricingTier::class,
        SchoolTier::class,
        TierChange::class,
        PaymentTransaction::class,
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
