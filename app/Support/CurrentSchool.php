<?php

namespace App\Support;

class CurrentSchool
{
    private ?string $schoolId = null;

    public function setSchoolId(?string $schoolId): void
    {
        $this->schoolId = $schoolId;
    }

    public function getSchoolId(): ?string
    {
        return $this->schoolId;
    }
}
