<?php

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use Database\Seeders\PricingTierSeeder;
use Illuminate\Support\Str;

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
        'metadata' => [
            'tier_name' => $tier->name,
            'billing_period' => strtolower($tier->billing_period->label()),
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'admin_fee_rate' => $adminFeeRate,
            'admin_fee_amount' => $adminFeeAmount,
        ],
    ]);
}

test('initiator can view their pending payment page', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertOk();
    $response->assertSeeText('My School');
    $response->assertSeeText($plusTier->name);
    $response->assertSeeText('Rp');
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

test('confirming payment creates the school and attaches the initiator as admin', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $response = $this->post(route('school.payment.confirm', $transaction));

    $response->assertRedirect(route('manage.schools.index'));

    $school = School::query()->where('domain', 'myschool.lms.local')->firstOrFail();

    expect($school->tier->id)->toBe($plusTier->id)
        ->and($school->admins->contains($user->id))->toBeTrue()
        ->and($user->fresh()->hasRole(RoleName::SchoolAdmin))->toBeTrue();

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentStatus::Completed)
        ->and($transaction->school_id)->toBe($school->id);
});

test('another user cannot confirm someone else\'s payment', function () {
    $this->seed(PricingTierSeeder::class);
    $owner = User::factory()->create(['school_id' => null]);
    $intruder = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($owner, $plusTier);

    $this->actingAs($intruder);
    $response = $this->post(route('school.payment.confirm', $transaction));

    $response->assertStatus(403);
    expect(School::query()->where('domain', 'myschool.lms.local')->exists())->toBeFalse();
});

test('viewing an already completed payment page redirects to the dashboard', function () {
    $this->seed(PricingTierSeeder::class);
    $user = User::factory()->create(['school_id' => null]);
    $plusTier = PricingTier::query()->where('slug', 'plus')->firstOrFail();
    $transaction = createPendingRegistrationTransaction($user, $plusTier);

    $this->actingAs($user);
    $this->post(route('school.payment.confirm', $transaction));

    $response = $this->get(route('school.payment.index', $transaction));

    $response->assertRedirect(route('manage.schools.index'));
});
