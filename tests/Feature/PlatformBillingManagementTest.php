<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\PlatformBillingSetting;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('platform admin configures the payment account from the billing workspace', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('platform.billing.index', ['tab' => 'settings']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/billing/index')
            ->where('activeTab', 'settings')
            ->where('settings.is_configured', false));

    $this->patch(route('platform.billing.update'), [
        'bank_name' => 'BCA',
        'account_name' => 'PT Gymlo Indonesia',
        'account_number' => '1234567890',
        'instructions' => 'Cantumkan nama subscriber.',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('platform.billing.index', ['tab' => 'settings']));

    $settings = PlatformBillingSetting::query()->sole();

    expect($settings->isConfigured())->toBeTrue()
        ->and($settings->updated_by)->toBe($admin->getKey())
        ->and(PlatformActivityLog::query()->where('event', 'platform_billing.updated')->count())->toBe(1);
});

test('regular users cannot access or update platform billing settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('platform.billing.index'))
        ->assertForbidden();

    $this->patch(route('platform.billing.update'), [
        'bank_name' => 'BCA',
        'account_name' => 'Unauthorized',
        'account_number' => '123',
    ])->assertForbidden();

    expect(PlatformBillingSetting::query()->exists())->toBeFalse();
});

test('owner upgrades from free using the selected server priced plan', function () {
    Storage::fake('local');
    PlatformBillingSetting::factory()->create(['id' => 1]);
    $owner = User::factory()->create();
    $freePlan = SaasPlan::factory()->create([
        'name' => 'Free',
        'slug' => 'free',
        'price' => '0.00',
        'trial_days' => 0,
        'max_gyms' => 1,
        'max_members' => 20,
        'max_staff' => 5,
    ]);
    $growthPlan = SaasPlan::factory()->create([
        'name' => 'Growth',
        'slug' => 'growth',
        'price' => '299000.00',
        'max_gyms' => 3,
        'max_members' => 500,
        'max_staff' => 20,
    ]);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($freePlan, 'plan')
        ->create([
            'status' => SubscriptionStatus::Active,
            'current_period_ends_at' => null,
        ]);
    $gym = Gym::factory()->create(['subscription_id' => $subscription->getKey()]);
    $gym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('subscription.show', ['tab' => 'payment', 'plan_id' => $growthPlan->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscription/show')
            ->where('activeTab', 'payment')
            ->where('selectedPlan.id', $growthPlan->getKey())
            ->where('billing.is_configured', true)
            ->where('canSubmitPayment', true));

    $this->post(route('subscription.payments.store'), [
        'saas_plan_id' => $growthPlan->getKey(),
        'reference_number' => 'UPGRADE-001',
        'proof' => UploadedFile::fake()->image('upgrade.jpg'),
    ])->assertSessionHasNoErrors();

    $payment = SubscriptionPayment::query()->sole();

    expect($payment->saas_plan_id)->toBe($growthPlan->getKey())
        ->and($payment->plan_name)->toBe('Growth')
        ->and($payment->amount)->toBe('299000.00')
        ->and($subscription->refresh()->saas_plan_id)->toBe($freePlan->getKey());
});

test('owner cannot upload payment until platform account is configured', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $plan = SaasPlan::factory()->create(['price' => '299000.00']);
    $subscription = Subscription::factory()->for($owner, 'subscriber')->for($plan, 'plan')->create();
    $gym = Gym::factory()->create(['subscription_id' => $subscription->getKey()]);
    $gym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('subscription.payments.store'), [
            'saas_plan_id' => $plan->getKey(),
            'reference_number' => 'NO-ACCOUNT',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])
        ->assertSessionHasErrors('proof');

    expect(SubscriptionPayment::query()->exists())->toBeFalse();
});
