<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Services\SchoolService;
use App\Support\CurrentSchool;
use App\Support\RootDomains;
use App\Support\SchoolDomainResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchoolFromDomain
{
    public function __construct(
        private CurrentSchool $currentSchool,
        private SchoolDomainResolver $schoolDomainResolver,
        private SchoolService $schoolService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $rootDomain = RootDomains::match($host);
        $adminDomain = "admin.{$rootDomain}";

        if ($request->user() && $request->user()->hasRole(RoleName::Admin) && $request->user()->school_id !== null) {
            abort(500, 'Invalid state: admin user must not belong to a school.');
        }

        if ($host === $adminDomain) {
            $this->currentSchool->setSchoolId(null);

            if ($request->user() && ! $request->user()->hasRole(RoleName::Admin)) {
                abort(403);
            }

            return $next($request);
        }

        if ($host === $rootDomain) {
            $this->currentSchool->setSchoolId(null);

            return $next($request);
        }

        $school = $this->schoolDomainResolver->resolve($request);

        if (! $school) {
            abort(404);
        }

        if ($request->user() && $request->user()->school_id === null
            && ! $request->user()->hasRole(RoleName::Admin)
            && ! $this->schoolService->administers($request->user(), $school)) {
            abort(403);
        }

        $this->currentSchool->setSchoolId($school->id);

        return $next($request);
    }
}
