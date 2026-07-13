<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CountTenantUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $result;

    public function __construct(public string $tenantId) {}

    public function handle(): void
    {
        app(CurrentTenant::class)->setTenantId($this->tenantId);

        $this->result = User::count();
    }
}

test('a queued job scopes queries to the tenant_id carried in its payload', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    User::factory()->for($tenantA, 'tenant')->count(2)->create();
    User::factory()->for($tenantB, 'tenant')->count(3)->create();

    $job = new CountTenantUsersJob($tenantA->id);
    $job->handle();

    expect($job->result)->toBe(2);
});
