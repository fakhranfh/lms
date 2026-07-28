<?php

use App\Enums\AdminFeeType;
use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use Database\Seeders\PricingTierSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

test('login page can be rendered on the root domain', function () {
    $this->get('/login')->assertSuccessful();
});

test('login page can be rendered on a school subdomain', function () {
    $school = School::factory()->create(['domain' => 'myschool.'.config('app.domain')]);

    $this->get("http://{$school->domain}/login")->assertSuccessful();
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login fails with unregistered email', function () {
    $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login updates timezone from ip address', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('Asia/Jakarta', 200),
    ]);

    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
        'timezone' => 'UTC',
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
});

test('login keeps existing timezone when ip lookup fails', function () {
    Http::fake([
        'ipapi.co/*' => Http::response('', 500),
        'ip-api.com/*' => Http::response('', 500),
    ]);

    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
        'timezone' => 'Asia/Jakarta',
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
});

test('login redirects to the pending payment page when the user has an unpaid school registration', function () {
    $this->seed(PricingTierSeeder::class);
    $tier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
        'school_id' => null,
    ]);

    $subtotal = (float) $tier->price;
    $transaction = PaymentTransaction::create([
        'initiated_by' => $user->id,
        'transaction_id' => (string) Str::uuid(),
        'amount' => $subtotal,
        'currency' => 'IDR',
        'status' => PaymentStatus::Pending,
        'registration_data' => ['name' => 'My School', 'domain' => 'myschool.lms.local', 'tier_id' => $tier->id],
        'subtotal' => $subtotal,
        'vat_rate' => 0,
        'vat_amount' => 0,
        'admin_fee_rate' => 0,
        'admin_fee_type' => AdminFeeType::Percentage,
        'admin_fee_amount' => 0,
        'tier_name' => $tier->name,
        'billing_period' => 'monthly',
    ]);

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('school.payment.index', $transaction));
});

test('login is throttled after 5 failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'password',
    ])->assertStatus(429);
});
