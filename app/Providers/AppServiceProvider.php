<?php

namespace App\Providers;

use App\Enums\TierFeature;
use App\Http\Responses\CustomAuthenticatedSessionResponse;
use App\Http\Responses\CustomVerifyEmailViewResponse;
use App\Listeners\UpdateUserTimezoneOnLogin;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Repositories\Auth\AuthRepository;
use App\Repositories\Auth\AuthRepositoryInterface;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Course\CourseRepositoryInterface;
use App\Repositories\Lesson\LessonRepository;
use App\Repositories\Lesson\LessonRepositoryInterface;
use App\Repositories\Module\ModuleRepository;
use App\Repositories\Module\ModuleRepositoryInterface;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\PricingTier\PricingTierRepository;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use App\Repositories\Role\RoleRepository;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\School\SchoolRepository;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\SchoolPaymentGateway\SchoolPaymentGatewayRepository;
use App\Repositories\SchoolPaymentGateway\SchoolPaymentGatewayRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\CourseService;
use App\Services\CredentialEncryption;
use App\Services\FeatureGateService;
use App\Services\LessonService;
use App\Services\ModuleService;
use App\Services\PaymentGatewayConfigService;
use App\Services\PaymentGatewayFactory;
use App\Services\PaymentGatewayRegistry;
use App\Services\PaymentWebhookService;
use App\Services\PricingTierService;
use App\Services\SchoolService;
use App\Services\SubscriptionPaymentService;
use App\Services\TierChangeService;
use App\Support\CurrentSchool;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VerifyEmailViewResponse::class, function () {
            return new CustomVerifyEmailViewResponse;
        });

        $this->app->singleton(LoginResponse::class, function () {
            return new CustomAuthenticatedSessionResponse;
        });

        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(PricingTierRepositoryInterface::class, PricingTierRepository::class);
        $this->app->bind(SchoolRepositoryInterface::class, SchoolRepository::class);
        $this->app->bind(SchoolPaymentGatewayRepositoryInterface::class, SchoolPaymentGatewayRepository::class);
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->bind(ModuleRepositoryInterface::class, ModuleRepository::class);
        $this->app->bind(LessonRepositoryInterface::class, LessonRepository::class);

        $this->app->singleton(CurrentSchool::class);

        $this->app->singleton(CredentialEncryption::class);
        $this->app->singleton(FeatureGateService::class);
        $this->app->singleton(PaymentGatewayRegistry::class);
        $this->app->singleton(PaymentGatewayFactory::class);
        $this->app->singleton(PricingTierService::class);
        $this->app->singleton(SchoolService::class);
        $this->app->singleton(SubscriptionPaymentService::class);
        $this->app->singleton(TierChangeService::class);
        $this->app->singleton(PaymentGatewayConfigService::class);
        $this->app->singleton(PaymentWebhookService::class);
        $this->app->singleton(CourseService::class);
        $this->app->singleton(ModuleService::class);
        $this->app->singleton(LessonService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        $this->registerFeatureGates();

        Event::listen(Login::class, UpdateUserTimezoneOnLogin::class);
    }

    private function registerFeatureGates(): void
    {
        $featureGate = $this->app->make(FeatureGateService::class);

        foreach (TierFeature::cases() as $feature) {
            Gate::define("use-{$feature->value}", function (User $user) use ($featureGate, $feature) {
                return $featureGate->can($user, $feature);
            });
        }
    }
}
