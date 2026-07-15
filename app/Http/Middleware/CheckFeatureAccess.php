<?php

namespace App\Http\Middleware;

use App\Enums\TierFeature;
use App\Exceptions\FeatureNotAvailableException;
use App\Services\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    public function __construct(private FeatureGateService $featureGate) {}

    /**
     * Handle an incoming request.
     *
     * The middleware expects a feature name in the route parameter.
     * Example: Route::get('/analytics', ...)->middleware('feature:analytics')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $tierFeature = TierFeature::from($feature);
            $this->featureGate->requireFeature($user, $tierFeature);
        } catch (FeatureNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\ValueError) {
            return response()->json(['message' => 'Invalid feature name'], 400);
        }

        return $next($request);
    }
}
