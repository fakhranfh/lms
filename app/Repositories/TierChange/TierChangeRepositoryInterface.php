<?php

namespace App\Repositories\TierChange;

use App\Models\TierChange;

interface TierChangeRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TierChange;
}
