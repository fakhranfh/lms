<?php

namespace App\Repositories\School;

use App\Models\School;

class SchoolRepository implements SchoolRepositoryInterface
{
    public function create(array $data): School
    {
        return School::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
        ]);
    }
}
