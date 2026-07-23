<?php

namespace Tests\Feature;

use App\Enums\RoleName;
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

    public function test_create_demo_user_creates_instructor_user(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school, 'instructor');

        $this->assertEquals($school->id, $user->school_id);
        $this->assertStringContainsString('demo-', $user->email);
        $this->assertStringNotContainsString('-student', $user->email);
    }

    public function test_create_demo_user_creates_student_user(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school, 'student');

        $this->assertEquals($school->id, $user->school_id);
        $this->assertStringContainsString('demo-student', $user->email);
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
        $this->assertEquals('instructor', $access->role);
        $this->assertTrue($access->expires_at->isFuture());
    }

    public function test_grant_demo_access_stores_role_type(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);

        $instructorAccess = $this->demoService->grantDemoAccess($school, $user, 'instructor');
        $studentAccess = $this->demoService->grantDemoAccess($school, $user, 'student');

        $this->assertEquals('instructor', $instructorAccess->role);
        $this->assertEquals('student', $studentAccess->role);
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
                'role' => 'instructor',
                'expires_at' => now()->addDays(7),
            ]);

        $access = $this->demoService->getOrCreateDemoAccess($school, 'instructor');

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

    public function test_demo_user_has_instructor_role_with_all_permissions(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school, 'instructor');

        $this->assertTrue($user->roles()->exists());
        $this->assertTrue($user->hasRole(RoleName::Instructor));

        // Verify demo user has sidebar menu permissions
        $this->assertTrue($user->can('users.view'));
        $this->assertTrue($user->can('roles.view'));
        $this->assertTrue($user->can('permissions.view'));
        $this->assertTrue($user->can('courses.view'));
    }

    public function test_demo_user_can_access_all_sidebar_menu_items(): void
    {
        $school = School::factory()->create();
        $access = $this->demoService->getOrCreateDemoAccess($school);
        $user = $access->user;

        $sidebarConfig = config('sidebar');

        foreach ($sidebarConfig as $item) {
            if (isset($item['requires_permission'])) {
                $this->assertTrue(
                    $user->can($item['requires_permission']),
                    "Demo user missing permission: {$item['requires_permission']} for menu item: {$item['label']}"
                );
            }
        }
    }

    public function test_build_demo_login_url_uses_school_domain(): void
    {
        $school = School::factory()->create(['domain' => 'testschool.lms.local']);
        $access = DemoLmsAccess::factory()
            ->create(['school_id' => $school->id, 'expires_at' => now()->addDays(14)]);

        $url = $this->demoService->buildDemoLoginUrl($school, $access->access_token);

        $this->assertStringContainsString($school->domain, $url);
        $this->assertStringContainsString($access->access_token, $url);
        $this->assertTrue(str_starts_with($url, 'http://'));
        $this->assertStringContainsString('/demo-lms/login/', $url);
    }

    public function test_build_demo_login_url_includes_port_when_not_standard(): void
    {
        $school = School::factory()->create(['domain' => 'testschool.lms.local']);
        $access = DemoLmsAccess::factory()
            ->create(['school_id' => $school->id]);

        $url = $this->demoService->buildDemoLoginUrl($school, $access->access_token, 'http', 8080);

        $this->assertStringContainsString(':8080', $url);
    }

    public function test_build_demo_login_url_omits_standard_ports(): void
    {
        $school = School::factory()->create(['domain' => 'testschool.lms.local']);
        $access = DemoLmsAccess::factory()
            ->create(['school_id' => $school->id]);

        $httpUrl = $this->demoService->buildDemoLoginUrl($school, $access->access_token, 'http', 80);
        $httpsUrl = $this->demoService->buildDemoLoginUrl($school, $access->access_token, 'https', 443);

        $this->assertStringNotContainsString(':80', $httpUrl);
        $this->assertStringNotContainsString(':443', $httpsUrl);
    }

    public function test_demo_access_get_login_url_method(): void
    {
        $school = School::factory()->create(['domain' => 'myschool.lms.local']);
        $access = DemoLmsAccess::factory()
            ->create(['school_id' => $school->id, 'expires_at' => now()->addDays(14)]);

        $url = $access->getLoginUrl();

        $this->assertStringContainsString('myschool.lms.local', $url);
        $this->assertStringContainsString($access->access_token, $url);
        $this->assertStringContainsString('/demo-lms/login/', $url);
    }

    public function test_get_demo_credentials_returns_both_roles(): void
    {
        $school = School::factory()->create();

        $credentials = $this->demoService->getDemoCredentials($school);

        $this->assertArrayHasKey('instructor', $credentials);
        $this->assertArrayHasKey('student', $credentials);
        $this->assertEquals('instructor', $credentials['instructor']->role);
        $this->assertEquals('student', $credentials['student']->role);
    }

    public function test_demo_student_user_has_correct_permissions(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school, 'student');

        $this->assertTrue($user->roles()->exists());
        $this->assertTrue($user->hasRole(RoleName::Student));
    }

    public function test_demo_instructor_user_has_correct_permissions(): void
    {
        $school = School::factory()->create();

        $user = $this->demoService->createDemoUser($school, 'instructor');

        $this->assertTrue($user->roles()->exists());
        $this->assertTrue($user->hasRole(RoleName::Instructor));
    }
}
