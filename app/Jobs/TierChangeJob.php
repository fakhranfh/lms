<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Services\TierChangeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TierChangeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PaymentTransaction $transaction
    ) {}

    public function handle(TierChangeService $service): void
    {
        $service->finalizeTierChange($this->transaction);
    }
}
