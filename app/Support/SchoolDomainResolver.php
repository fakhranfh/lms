<?php

namespace App\Support;

use App\Models\School;
use App\Services\SchoolService;
use Illuminate\Http\Request;

class SchoolDomainResolver
{
    public function __construct(private SchoolService $schoolService) {}

    public function resolve(Request $request): ?School
    {
        return $this->schoolService->findByDomain($this->canonicalHost($request->getHost()));
    }

    /**
     * Schools are always stored with a domain on the primary app.domain
     * (e.g. "school1.lms.local"). When accessed via an APP_EXTRA_DOMAINS
     * alias (e.g. "school1.lms.io"), translate the host to its canonical
     * form so the lookup still matches.
     */
    private function canonicalHost(string $host): string
    {
        $rootDomain = config('app.domain');

        foreach (config('app.extra_domains') as $extraDomain) {
            if ($host === $extraDomain) {
                return $rootDomain;
            }

            if (str_ends_with($host, ".{$extraDomain}")) {
                $subdomain = substr($host, 0, -strlen(".{$extraDomain}"));

                return "{$subdomain}.{$rootDomain}";
            }
        }

        return $host;
    }
}
