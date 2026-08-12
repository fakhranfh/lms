<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\UserLoginLink;
use App\Services\UserLoginLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginLinkTest extends TestCase
{
    use RefreshDatabase;

    protected UserLoginLinkService $userLoginLinkService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userLoginLinkService = app(UserLoginLinkService::class);
    }

    public function test_generate_token_creates_unique_tokens(): void
    {
        $token1 = $this->userLoginLinkService->generateToken();
        $token2 = $this->userLoginLinkService->generateToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(48, strlen($token1));
    }

    public function test_create_link_stores_ttl_and_user(): void
    {
        $user = User::factory()->create();

        $link = $this->userLoginLinkService->createLink($user, 30);

        $this->assertEquals($user->id, $link->user_id);
        $this->assertTrue($link->expires_at->isFuture());
        $this->assertTrue($link->expires_at->diffInMinutes(now()) <= 30);
        $this->assertNull($link->used_at);
    }

    public function test_find_valid_by_token_returns_null_for_expired_link(): void
    {
        $link = UserLoginLink::factory()->expired()->create();

        $this->assertNull($this->userLoginLinkService->findValidByToken($link->token));
    }

    public function test_find_valid_by_token_returns_null_for_used_link(): void
    {
        $link = UserLoginLink::factory()->used()->create();

        $this->assertNull($this->userLoginLinkService->findValidByToken($link->token));
    }

    public function test_find_valid_by_token_returns_link_when_valid(): void
    {
        $link = UserLoginLink::factory()->create();

        $this->assertNotNull($this->userLoginLinkService->findValidByToken($link->token));
    }

    public function test_build_login_url_uses_school_domain(): void
    {
        $school = School::factory()->create(['domain' => 'testschool.lms.local']);
        $user = User::factory()->forSchool($school)->create();
        $link = $this->userLoginLinkService->createLink($user);

        $url = $this->userLoginLinkService->buildLoginUrl($link);

        $this->assertStringContainsString('testschool.lms.local', $url);
        $this->assertStringContainsString($link->token, $url);
        $this->assertStringContainsString('/login-link/', $url);
    }

    public function test_login_with_valid_token_logs_in_user(): void
    {
        $link = UserLoginLink::factory()->create();

        $response = $this->get(route('user-login-link.login', $link->token));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($link->user);
    }

    public function test_login_marks_link_as_used(): void
    {
        $link = UserLoginLink::factory()->create();

        $this->get(route('user-login-link.login', $link->token));

        $link->refresh();
        $this->assertNotNull($link->used_at);
    }

    public function test_login_link_cannot_be_reused(): void
    {
        $link = UserLoginLink::factory()->create();

        $this->get(route('user-login-link.login', $link->token));
        auth()->logout();

        $response = $this->get(route('user-login-link.login', $link->token));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_with_expired_token_redirects_to_login(): void
    {
        $link = UserLoginLink::factory()->expired()->create();

        $response = $this->get(route('user-login-link.login', $link->token));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_with_invalid_token_redirects_to_login(): void
    {
        $response = $this->get(route('user-login-link.login', 'not-a-real-token'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_command_generates_login_link_by_email(): void
    {
        $user = User::factory()->create(['email' => 'link-target@example.com']);

        $this->artisan('user:login-link', ['user' => 'link-target@example.com'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_login_links', ['user_id' => $user->id]);
    }

    public function test_command_generates_login_link_by_id(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:login-link', ['user' => $user->id])
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_login_links', ['user_id' => $user->id]);
    }

    public function test_command_generates_login_link_by_unique_name(): void
    {
        $user = User::factory()->create(['name' => 'Vaughn Smitham']);

        $this->artisan('user:login-link', ['user' => 'Vaughn Smitham'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_login_links', ['user_id' => $user->id]);
    }

    public function test_command_fails_for_unknown_user(): void
    {
        $this->artisan('user:login-link', ['user' => 'nobody@example.com'])
            ->assertExitCode(1);

        $this->assertDatabaseCount('user_login_links', 0);
    }

    public function test_command_fails_and_lists_matches_for_ambiguous_name(): void
    {
        User::factory()->create(['name' => 'Duplicate Name']);
        User::factory()->create(['name' => 'Duplicate Name']);

        $this->artisan('user:login-link', ['user' => 'Duplicate Name'])
            ->assertExitCode(1);

        $this->assertDatabaseCount('user_login_links', 0);
    }
}
