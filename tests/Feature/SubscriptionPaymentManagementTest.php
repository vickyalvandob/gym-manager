<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\PlatformActivityLog;
use App\Models\PlatformBillingSetting;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** @return array{owner: User, gym: Gym, plan: SaasPlan, subscription: Subscription} */
function manualBillingWorkspace(array $subscriptionAttributes = []): array
{
    PlatformBillingSetting::factory()->create(['id' => 1]);

    $owner = User::factory()->create();
    $plan = SaasPlan::factory()->create([
        'name' => 'Growth',
        'price' => '299000.00',
        'billing_interval' => 'monthly',
        'max_gyms' => 3,
        'max_members' => 500,
        'max_staff' => 20,
    ]);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($plan, 'plan')
        ->create([
            'status' => SubscriptionStatus::Expired,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->subMonths(2),
            'current_period_ends_at' => now()->subMonth(),
            ...$subscriptionAttributes,
        ]);
    $gym = Gym::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'onboarding_completed_at' => now(),
    ]);
    $gym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);

    return compact('owner', 'gym', 'plan', 'subscription');
}

function submitManualSubscriptionPayment(
    TestCase $testCase,
    User $owner,
    Gym $gym,
    ?SaasPlan $plan = null,
    string $reference = 'TRX-12345',
): void {
    $selectedPlan = $plan ?? $gym->subscription()->with('plan')->firstOrFail()->plan;

    $testCase->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('subscription.payments.store'), [
            'saas_plan_id' => $selectedPlan->getKey(),
            'reference_number' => $reference,
            'proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('subscription.show', [
            'tab' => 'payment',
            'plan_id' => $selectedPlan->getKey(),
        ]));
}

test('expired owner is redirected to subscription while billing remains accessible', function () {
    ['owner' => $owner, 'gym' => $gym] = manualBillingWorkspace([
        'status' => SubscriptionStatus::Active,
        'current_period_ends_at' => now()->subMinute(),
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.show'));

    $this->get(route('subscription.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscription/show')
            ->where('subscription.status', SubscriptionStatus::Expired->value)
            ->where('subscription.grants_access', false)
            ->where('auth.subscription.grants_access', false));
});

test('subscriber submits one private manual payment for the server priced plan', function () {
    Storage::fake('local');
    ['owner' => $owner, 'gym' => $gym, 'plan' => $plan, 'subscription' => $subscription] = manualBillingWorkspace();

    submitManualSubscriptionPayment($this, $owner, $gym);

    $payment = SubscriptionPayment::query()->sole();

    expect($payment->subscription_id)->toBe($subscription->getKey())
        ->and($payment->saas_plan_id)->toBe($plan->getKey())
        ->and($payment->amount)->toBe('299000.00')
        ->and($payment->status)->toBe(SubscriptionPaymentStatus::Pending);
    Storage::disk('local')->assertExists($payment->proof_path);

    $this->post(route('subscription.payments.store'), [
        'saas_plan_id' => $plan->getKey(),
        'reference_number' => 'TRX-DUPLICATE',
        'proof' => UploadedFile::fake()->image('duplicate.jpg'),
    ])->assertSessionHasErrors('proof');

    expect(SubscriptionPayment::query()->count())->toBe(1);
});

test('platform user detail contains gym usage and pending subscription payments', function () {
    Storage::fake('local');
    ['owner' => $owner, 'gym' => $gym] = manualBillingWorkspace();
    Member::factory()->for($gym)->count(2)->create();
    $staff = User::factory()->create();
    $gym->users()->attach($staff, [
        'role' => GymRole::Admin->value,
        'status' => GymUserStatus::Active->value,
    ]);
    submitManualSubscriptionPayment($this, $owner, $gym);
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('platform.users.show', $owner))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/users/show')
            ->where('managedUser.gyms.0.members_count', 2)
            ->where('managedUser.gyms.0.staff_count', 1)
            ->where('managedUser.subscription.payments.0.status', SubscriptionPaymentStatus::Pending->value)
            ->where('managedUser.subscription.payments.0.reference_number', 'TRX-12345'));

    $this->get(route('platform.users.index', ['billing_status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.total', 1)
            ->where('users.data.0.id', $owner->getKey())
            ->where('users.data.0.subscription.pending_payments_count', 1));
});

test('platform approval activates and extends the subscriber subscription', function () {
    Storage::fake('local');
    ['owner' => $owner, 'gym' => $gym, 'plan' => $plan, 'subscription' => $subscription] = manualBillingWorkspace();
    submitManualSubscriptionPayment($this, $owner, $gym);
    $payment = SubscriptionPayment::query()->sole();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->put(route('platform.users.subscription.update', $owner), [
            'saas_plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Active->value,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->toDateTimeString(),
            'current_period_ends_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('status');

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Expired);

    $this
        ->patch(route('platform.subscription-payments.update', $payment), [
            'decision' => SubscriptionPaymentStatus::Approved->value,
            'review_notes' => 'Transfer sesuai mutasi bank.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $payment->refresh();
    $subscription->refresh();

    expect($payment->status)->toBe(SubscriptionPaymentStatus::Approved)
        ->and($payment->reviewed_by)->toBe($admin->getKey())
        ->and($payment->period_ends_at?->toDateString())->toBe(now()->addMonthNoOverflow()->toDateString())
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->grantsAccess())->toBeTrue()
        ->and(PlatformActivityLog::query()->where('event', 'subscription_payment.approved')->count())->toBe(1);
});

test('rejection requires a note and does not activate subscription', function () {
    Storage::fake('local');
    ['owner' => $owner, 'gym' => $gym, 'subscription' => $subscription] = manualBillingWorkspace();
    submitManualSubscriptionPayment($this, $owner, $gym);
    $payment = SubscriptionPayment::query()->sole();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->patch(route('platform.subscription-payments.update', $payment), [
            'decision' => SubscriptionPaymentStatus::Rejected->value,
            'review_notes' => '',
        ])
        ->assertSessionHasErrors('review_notes');

    $this->patch(route('platform.subscription-payments.update', $payment), [
        'decision' => SubscriptionPaymentStatus::Rejected->value,
        'review_notes' => 'Nominal pada bukti tidak sesuai.',
    ])->assertSessionHasNoErrors();

    expect($payment->refresh()->status)->toBe(SubscriptionPaymentStatus::Rejected)
        ->and($subscription->refresh()->status)->toBe(SubscriptionStatus::Expired);
});

test('payment proof is private to subscriber and platform admin', function () {
    Storage::fake('local');
    ['owner' => $owner, 'gym' => $gym] = manualBillingWorkspace();
    submitManualSubscriptionPayment($this, $owner, $gym);
    $payment = SubscriptionPayment::query()->sole();
    $otherUser = User::factory()->create();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($otherUser)
        ->get(route('subscription-payments.proof', $payment))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('subscription-payments.proof', $payment))
        ->assertOk();
});
