<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\RoleName;
use App\Livewire\Admin\AuditLogTable;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTableTest extends TestCase
{
    public function test_super_admin_can_view_audit_log_table(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        AuditLog::factory()->create(['description' => 'Course updated for viewing']);

        $response = $this->actingAs($admin)->get('http://admin.lms.local/audit-logs');

        $response->assertStatus(200)->assertSee('Course updated for viewing');
    }

    public function test_non_admin_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->for($school, 'school')->create();

        $response = $this->actingAs($user)->get('http://admin.lms.local/audit-logs');

        $response->assertStatus(403);
    }

    public function test_filter_by_date_range_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        AuditLog::factory()->create(['description' => 'Old entry', 'created_at' => now()->subYears(2)]);
        AuditLog::factory()->create(['description' => 'Recent entry', 'created_at' => now()]);

        Livewire::actingAs($admin)->test(AuditLogTable::class)
            ->set('dateFrom', now()->subDay()->toDateString())
            ->assertSee('Recent entry')
            ->assertDontSee('Old entry');
    }

    public function test_filter_by_user_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        $otherUser = User::factory()->create();

        AuditLog::factory()->create(['user_id' => $admin->id, 'description' => 'By admin']);
        AuditLog::factory()->create(['user_id' => $otherUser->id, 'description' => 'By other']);

        Livewire::actingAs($admin)->test(AuditLogTable::class)
            ->set('userId', $admin->id)
            ->assertSee('By admin')
            ->assertDontSee('By other');
    }

    public function test_expand_detail_modal_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        $log = AuditLog::factory()->create(['description' => 'Detail target']);

        Livewire::actingAs($admin)->test(AuditLogTable::class)
            ->call('show', $log->id)
            ->assertSet('selectedAuditLogId', $log->id);
    }
}
