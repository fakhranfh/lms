<?php

namespace Tests\Feature;

use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Models\User;
use App\Services\DemoLmsAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLmsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected DemoLmsAccessService $demoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->demoService = app(DemoLmsAccessService::class);
    }

    public function test_generate_access_token_creates_unique_token(): void
    {
        $school = School::factory()->create();

        $token1 = $this->demoService->generateAccessToken($school);
        $token2 = $this->demoService->generateAccessToken($school);

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(32, strlen($token1));
        $this->assertEquals(32, strlen($token2));
    }

    public function test_create_demo_user_creates_user(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school);

        $this->assertEquals($school->id, $user->school_id);
        $this->assertStringContainsString('demo-', $user->email);
        $this->assertStringContainsString($school->domain, $user->email);
    }

    public function test_create_demo_user_returns_existing_user_if_already_created(): void
    {
        $school = School::factory()->create();

        $user1 = $this->demoService->createDemoUser($school);
        $user2 = $this->demoService->createDemoUser($school);

        $this->assertEquals($user1->id, $user2->id);
    }

    public function test_grant_demo_access_creates_valid_access_record(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);

        $access = $this->demoService->grantDemoAccess($school, $user);

        $this->assertNotNull($access->access_token);
        $this->assertEquals(32, strlen($access->access_token));
        $this->assertEquals($school->id, $access->school_id);
        $this->assertEquals($user->id, $access->user_id);
        $this->assertTrue($access->expires_at->isFuture());
    }

    public function test_is_demo_access_valid_returns_true_for_unexpired_access(): void
    {
        $access = DemoLmsAccess::factory()
            ->create(['expires_at' => now()->addDays(5)]);

        $this->assertTrue($this->demoService->isDemoAccessValid($access));
    }

    public function test_is_demo_access_valid_returns_false_for_expired_access(): void
    {
        $access = DemoLmsAccess::factory()
            ->create(['expires_at' => now()->subDays(1)]);

        $this->assertFalse($this->demoService->isDemoAccessValid($access));
    }

    public function test_is_demo_access_valid_returns_false_for_null_expiry(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $access = DemoLmsAccess::factory()
            ->create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'expires_at' => now()->subDays(1),
            ]);

        $this->assertFalse($this->demoService->isDemoAccessValid($access));
    }

    public function test_get_or_create_demo_access_returns_existing_valid_access(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $existingAccess = DemoLmsAccess::factory()
            ->create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);

        $access = $this->demoService->getOrCreateDemoAccess($school);

        $this->assertEquals($existingAccess->id, $access->id);
    }

    public function test_get_or_create_demo_access_creates_new_access_if_expired(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        DemoLmsAccess::factory()
            ->create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'expires_at' => now()->subDays(1),
            ]);

        $access = $this->demoService->getOrCreateDemoAccess($school);

        $this->assertTrue($this->demoService->isDemoAccessValid($access));
    }

    public function test_get_or_create_returns_newly_created_access(): void
    {
        $school = School::factory()->create();

        $access1 = $this->demoService->getOrCreateDemoAccess($school);
        $access2 = $this->demoService->getOrCreateDemoAccess($school);

        $this->assertEquals($access1->id, $access2->id);
        $this->assertTrue($this->demoService->isDemoAccessValid($access1));
    }

    public function test_demo_login_with_valid_token_logs_in_user(): void
    {
        $access = DemoLmsAccess::factory()
            ->create(['expires_at' => now()->addDays(14)]);

        $response = $this->get(route('demo-lms.login', $access->access_token));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($access->user);
    }

    public function test_demo_login_with_invalid_token_redirects_to_login(): void
    {
        $response = $this->get(route('demo-lms.login', 'invalid-token'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_demo_login_with_expired_access_redirects_to_login(): void
    {
        $access = DemoLmsAccess::factory()
            ->create(['expires_at' => now()->subDays(1)]);

        $response = $this->get(route('demo-lms.login', $access->access_token));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_demo_login_updates_accessed_at_timestamp(): void
    {
        $access = DemoLmsAccess::factory()
            ->create(['expires_at' => now()->addDays(14), 'accessed_at' => null]);

        $this->get(route('demo-lms.login', $access->access_token));

        $access->refresh();
        $this->assertNotNull($access->accessed_at);
    }

    public function test_regenerate_demo_access_creates_new_token(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $existingAccess = DemoLmsAccess::factory()
            ->create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);

        $newAccess = $this->demoService->regenerateDemoAccess($school);

        $this->assertNotEquals($existingAccess->access_token, $newAccess->access_token);
        $this->assertTrue($this->demoService->isDemoAccessValid($newAccess));
    }
}
