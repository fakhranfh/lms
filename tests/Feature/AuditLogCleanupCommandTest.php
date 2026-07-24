<?php

use App\Models\AuditLog;

test('cleanup deletes audit logs older than the retention window', function () {
    $old = AuditLog::factory()->create(['created_at' => now()->subYears(2)]);
    $recent = AuditLog::factory()->create(['created_at' => now()]);

    $this->artisan('audit-logs:cleanup', ['--older-than' => '1-year'])->assertExitCode(0);

    expect(AuditLog::find($old->id))->toBeNull()
        ->and(AuditLog::find($recent->id))->not->toBeNull();
});
