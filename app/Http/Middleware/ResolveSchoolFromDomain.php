<?php

namespace App\Http\Middleware;

use App\Support\CurrentSchool;
use App\Support\SchoolDomainResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchoolFromDomain
{
    public function __construct(
        private CurrentSchool $currentSchool,
        private SchoolDomainResolver $schoolDomainResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $rootDomain = config('app.domain');
        $adminDomain = "admin.{$rootDomain}";

        if ($request->user() && $request->user()->hasRole('admin') && $request->user()->school_id !== null) {
            abort(500, 'Invalid state: admin user must not belong to a school.');
        }

        if ($host === $adminDomain) {
            $this->currentSchool->setSchoolId(null);

            if ($request->user() && ! $request->user()->hasRole('admin')) {
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

        $this->currentSchool->setSchoolId($school->id);

        return $next($request);
    }
}
