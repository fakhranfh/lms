<?php

namespace App\Repositories\School;

use App\Models\School;

interface SchoolRepositoryInterface
{
    public function create(array $data): School;
}
