<?php

namespace App\Services;

use App\Models\School;
use App\Repositories\School\SchoolRepositoryInterface;

class SchoolService
{
    public function __construct(private SchoolRepositoryInterface $schoolRepository) {}

    public function create(array $data): School
    {
        return $this->schoolRepository->create($data);
    }

    public function buildRegisterUrl(School $school, string $scheme, int $port): string
    {
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";

        return "{$scheme}://{$school->domain}{$portSuffix}/register";
    }
}
