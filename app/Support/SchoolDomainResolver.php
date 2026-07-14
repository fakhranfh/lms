<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolDomainResolver
{
    public function resolve(Request $request): ?School
    {
        return School::where('domain', $request->getHost())->first();
    }
}
