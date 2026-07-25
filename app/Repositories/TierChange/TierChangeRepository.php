<?php

namespace App\Repositories\TierChange;

use App\Models\TierChange;

class TierChangeRepository implements TierChangeRepositoryInterface
{
    public function create(array $data): TierChange
    {
        return TierChange::create($data);
    }
}
