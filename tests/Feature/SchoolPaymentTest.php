<?php

use App\Enums\AdminFeeType;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Jobs\ProcessPaymentWebhook;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepositoryInterface;
use App\Services\PaymentGatewayFactory;
use App\Services\SchoolService;
use App\Services\SubscriptionPaymentService;
use Database\Seeders\PricingTierSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function enableXenditGateway(): PaymentGateway
{
    $gatewayType = PaymentGatewayType::firstOrCreate(
        ['name' => 'xendit'],
        ['label' => 'Xendit', 'is_active' => true]
    );

    $gateway = PaymentGateway::factory()
        ->for($gatewayType)
        ->state(['is_enabled' => true, 'is_sandbox_mode' => true, 'enabled_channels' => ['QRIS', 'BCA']])
        ->create();

    PaymentGatewayCredential::create([
        'payment_gateway_id' => $gateway->id,
        'credential_key' => 'api_key',
        'credential_value' => 'test_api_key',
    ]);

    return $gateway;
}

function createPendingRegistrationTransaction(User $user, PricingTier $tier, string $domain = 'myschool.lms.local'): PaymentTransaction
{
    $subtotal = (float) $tier->price;
    $vatRate = (float) config('billing.vat_rate');
    $adminFeeRate = (float) config('billing.admin_fee_rate');
    $vatAmount = $subtotal * $vatRate;
    $adminFeeAmount = $subtotal * $adminFeeRate;

    return PaymentTransaction::create([
        'initiated_by' => $user->id,
        'transaction_id' => (string) Str::uuid(),
        'amount' => $subtotal + $vatAmount + $adminFeeAmount,
        'currency' => 'IDR',
        'status' => PaymentStatus::Pending,
        'registration_data' => [
            'name' => 'My School',
            'domain' => $domain,
            'tier_id' => $tier->id,
        ],
        'subtotal' => $subtotal,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'admin_fee_rate' => $adminFeeRate,
        'admin_fee_type' => AdminFeeType::Percentage,
        'admin_fee_amount' => $adminFeeAmount,
        'tier_name' => $tier->name,
        'billing_period' => strtolower($tier->billing_period->label()),
    ]);
}

test('initiator can view their pending payment page', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertOk();
    $response->assertSeeText('My School');
    $response->assertSeeText($plusTier->name);
    $response->assertSeeText('Rp');
    $response->assertSeeText('QRIS');
    $response->assertSeeText('BCA Virtual Account');
});

test('another user cannot view someone else\'s pending payment page', function () {
    $this->seed(PricingTierSeeder::class);
    $owner = User::factory()->create(['school_id' => null]);
    $intruder = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($owner, $plusTier);

    $this->actingAs($intruder);
    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertStatus(403);
});

test('unauthenticated user cannot view payment page', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertRedirect(route('login'));
});

test('confirming payment initiates a payment request against the chosen channel', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-123',
            'reference_id' => 'inv-123',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '00020101...'],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->post(route('school.payment.confirm', $transaction), ['channel' => 'QRIS']);

    $response->assertRedirect(route('school.payment.index', $transaction));

    expect(School::query()->where('domain', 'myschool.lms.local')->exists())->toBeFalse();

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentStatus::Pending)
        ->and($transaction->channel)->toBe('QRIS')
        ->and($transaction->payment_instructions)->toBe('00020101...')
        ->and($transaction->transaction_id)->toBe('pr-registration-123');
});

test('confirming payment via ajax returns json payment instructions without redirecting', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-456',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '00020102...'],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.confirm', $transaction), ['channel' => 'QRIS']);

    $response->assertOk()->assertJson([
        'channel' => 'QRIS',
        'channel_label' => 'QRIS',
        'view_type' => 'qris',
        'payment_instructions' => '00020102...',
        'redirect_url' => null,
    ]);
    $response->assertJsonStructure(['channel_logo']);
});

test('confirming payment for an e-wallet channel stays on the payment page instead of redirecting off-site', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-ovo',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'WEB_URL', 'value' => 'https://ovo.example/pay/123'],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->post(route('school.payment.confirm', $transaction), ['channel' => 'OVO']);

    $response->assertRedirect(route('school.payment.index', $transaction));

    $transaction->refresh();
    expect($transaction->channel)->toBe('OVO')
        ->and($transaction->payment_instructions)->toBe('https://ovo.example/pay/123');
});

test('confirming payment persists a checkout url longer than 255 characters', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    $longUrl = 'https://ewallet-service-dev.xendit.co/ewallets/sandbox/checkout?token='.str_repeat('a1b2c3d4', 40);
    expect(strlen($longUrl))->toBeGreaterThan(255);

    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-dana-long',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'WEB_URL', 'value' => $longUrl],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.confirm', $transaction), ['channel' => 'DANA']);

    $response->assertOk();

    $transaction->refresh();
    expect($transaction->payment_instructions)->toBe($longUrl);
});

test('confirming payment for an e-wallet channel via ajax returns the deeplink without an auto redirect', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-dana',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'WEB_URL', 'value' => 'https://dana.example/pay/456'],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.confirm', $transaction), ['channel' => 'DANA']);

    $response->assertOk()->assertJson([
        'view_type' => 'ewallet',
        'payment_instructions' => 'https://dana.example/pay/456',
        'redirect_url' => null,
    ]);
});

test('confirming payment via ajax includes the how-to-pay guide and sandbox simulation flags', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-registration-789',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '1234567890'],
            ],
        ]),
    ]);

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.confirm', $transaction), ['channel' => 'BCA']);

    $response->assertOk()->assertJson([
        'view_type' => 'virtual_account',
        'is_sandbox' => true,
        'supports_simulation' => true,
    ]);
    $response->assertJsonStructure(['guide_steps', 'simulate_url', 'stream_url']);
    expect($response->json('guide_steps'))->not->toBeEmpty();
});

test('simulating payment triggers the gateway simulate endpoint in sandbox mode', function () {
    $this->seed(PricingTierSeeder::class);
    $gateway = enableXenditGateway();
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);
    $transaction->update([
        'payment_gateway_id' => $gateway->id,
        'channel' => 'QRIS',
        'transaction_id' => 'pr-registration-999',
    ]);

    Http::fake([
        '*api.xendit.co*' => Http::response(['status' => 'SUCCEEDED', 'message' => 'ok']),
    ]);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.simulate', $transaction));

    $response->assertOk();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/simulate'));
});

test('simulating payment is rejected outside sandbox mode', function () {
    $this->seed(PricingTierSeeder::class);
    $gatewayType = PaymentGatewayType::firstOrCreate(['name' => 'xendit'], ['label' => 'Xendit', 'is_active' => true]);
    $gateway = PaymentGateway::factory()->for($gatewayType)->state([
        'is_enabled' => true,
        'is_sandbox_mode' => false,
        'enabled_channels' => ['QRIS'],
    ])->create();

    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);
    $transaction->update([
        'payment_gateway_id' => $gateway->id,
        'channel' => 'QRIS',
        'transaction_id' => 'pr-registration-live',
    ]);

    $this->actingAs($user);
    $response = $this->postJson(route('school.payment.simulate', $transaction));

    $response->assertStatus(422);
});

test('another user cannot simulate someone else\'s payment', function () {
    $this->seed(PricingTierSeeder::class);
    $gateway = enableXenditGateway();
    $owner = User::factory()->create(['school_id' => null]);
    $intruder = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($owner, $plusTier);
    $transaction->update(['payment_gateway_id' => $gateway->id, 'channel' => 'QRIS', 'transaction_id' => 'pr-x']);

    $this->actingAs($intruder);
    $response = $this->postJson(route('school.payment.simulate', $transaction));

    $response->assertStatus(403);
});

test('another user cannot confirm someone else\'s payment', function () {
    $this->seed(PricingTierSeeder::class);
    enableXenditGateway();
    $owner = User::factory()->create(['school_id' => null]);
    $intruder = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($owner, $plusTier);

    $this->actingAs($intruder);
    $response = $this->post(route('school.payment.confirm', $transaction), ['channel' => 'QRIS']);

    $response->assertStatus(403);
    expect(School::query()->where('domain', 'myschool.lms.local')->exists())->toBeFalse();
});

test('payment webhook completion creates the school and attaches the initiator as admin', function () {
    $this->seed(PricingTierSeeder::class);
    $gateway = enableXenditGateway();
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);
    $transaction->update([
        'payment_gateway_id' => $gateway->id,
        'channel' => 'QRIS',
        'transaction_id' => 'pr-registration-123',
    ]);

    $webhook = PaymentWebhook::create([
        'payment_gateway_id' => $gateway->id,
        'event_type' => 'payment.succeeded',
        'payload' => json_encode([
            'event' => 'payment.succeeded',
            'data' => ['payment_request_id' => 'pr-registration-123', 'status' => 'SUCCEEDED'],
        ]),
        'processed' => false,
    ]);

    (new ProcessPaymentWebhook($webhook))->handle(
        app(SubscriptionPaymentService::class),
        app(PaymentGatewayFactory::class),
        app(PaymentGatewayTestTransactionRepositoryInterface::class),
        app(SchoolService::class),
    );

    $school = School::query()->where('domain', 'myschool.lms.local')->firstOrFail();

    expect($school->tier->id)->toBe($plusTier->id)
        ->and($school->admins->contains($user->id))->toBeTrue()
        ->and($user->fresh()->hasRole(RoleName::SchoolAdmin))->toBeTrue();

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentStatus::Completed)
        ->and($transaction->school_id)->toBe($school->id);
});

test('viewing an already completed payment page redirects to the dashboard', function () {
    $this->seed(PricingTierSeeder::class);
    $gateway = enableXenditGateway();
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);
    $transaction->update(['payment_gateway_id' => $gateway->id]);
    app(SchoolService::class)->completeRegistrationTransaction($transaction);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertRedirect(route('manage.schools.index'));
});

test('stream endpoint emits a completed event immediately once the school exists', function () {
    $this->seed(PricingTierSeeder::class);
    $gateway = enableXenditGateway();
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);
    $transaction->update(['payment_gateway_id' => $gateway->id]);
    $school = app(SchoolService::class)->completeRegistrationTransaction($transaction);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.stream', $transaction));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('event: completed')
        ->and($content)->toContain(route('manage.schools.index'));

    expect($school->id)->not->toBeNull();
});

test('another user cannot open someone else\'s payment stream', function () {
    $this->seed(PricingTierSeeder::class);
    $owner = User::factory()->create(['school_id' => null]);
    $intruder = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($owner, $plusTier);

    $this->actingAs($intruder);
    $response = $this->get(route('school.payment.stream', $transaction));

    $response->assertStatus(403);
});
