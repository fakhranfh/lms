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
        return $this->schoolService->findByDomain($request->getHost());
    }
}
