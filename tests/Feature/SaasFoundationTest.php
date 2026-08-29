<?php

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\SaasPlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function attachSaasUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

function validSaasPlanPayload(array $overrides = []): array
{
    return [
        'name' => 'Business',
        'description' => 'Paket untuk gym berkembang.',
        'price' => '599000.00',
        'currency' => 'IDR',
        'billing_interval' => 'monthly',
        'trial_days' => 14,
        'max_members' => 2000,
        'max_staff' => 30,
        'is_active' => true,
        'sort_order' => 20,
        ...$overrides,
    ];
}

test('only platform super admins can access the SaaS control plane', function () {
    $platformAdmin = User::factory()->platformAdmin()->create();
    $owner = User::factory()->create();

    $this->actingAs($platformAdmin)
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/dashboard')
            ->where('metrics.gyms_total', 0));

    $this->actingAs($owner)
        ->get(route('platform.dashboard'))
        ->assertForbidden();
});

test('platform admin can create update and deactivate SaaS plans with audit trail', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->post(route('platform.saas-plans.store'), validSaasPlanPayload([
            'name' => '  Business   Plus  ',
        ]))
        ->assertRedirect();

    $plan = SaasPlan::query()->where('name', 'Business Plus')->firstOrFail();

    $this->patch(route('platform.saas-plans.update', $plan), validSaasPlanPayload([
        'name' => 'Business Pro',
        'price' => '699000.00',
    ]))->assertRedirect(route('platform.saas-plans.edit', $plan));

    $this->patch(route('platform.saas-plans.status.update', $plan), [
        'is_active' => false,
    ])->assertRedirect();

    $plan->refresh();
    expect($plan->name)->toBe('Business Pro')
        ->and($plan->price)->toBe('699000.00')
        ->and($plan->is_active)->toBeFalse()
        ->and(PlatformActivityLog::query()->where('event', 'saas_plan.created')->count())->toBe(1)
        ->and(PlatformActivityLog::query()->where('event', 'saas_plan.updated')->count())->toBe(1)
        ->and(PlatformActivityLog::query()->where('event', 'saas_plan.status_changed')->count())->toBe(1);
});

test('owner completes onboarding before entering the operational workspace', function () {
    $gym = Gym::factory()->create(['onboarding_completed_at' => null]);
    $owner = User::factory()->create();
    $plan = SaasPlan::factory()->create();
    attachSaasUser($gym, $owner, GymRole::Owner);
    Subscription::factory()->trialing()->for($gym)->for($plan, 'plan')->create();

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.edit'));

    $this->get(route('onboarding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/edit')
            ->where('subscription.plan_name', $plan->name));

    $this->put(route('onboarding.update'), [
        'name' => ' Gym   Baru Jakarta ',
        'timezone' => 'Asia/Jakarta',
        'currency' => 'idr',
        'phone' => '021555000',
        'email' => 'halo@gym.test',
        'address' => 'Jakarta Selatan',
    ])->assertRedirect(route('dashboard'));

    $gym->refresh();
    expect($gym->name)->toBe('Gym Baru Jakarta')
        ->and($gym->currency)->toBe('IDR')
        ->and($gym->onboarding_completed_at)->not->toBeNull()
        ->and(ActivityLog::query()->where('event', 'gym.onboarding_completed')->count())->toBe(1);
});

test('expired trial blocks operations while owner can inspect subscription status', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $frontDesk = User::factory()->create();
    $plan = SaasPlan::factory()->create();
    attachSaasUser($gym, $owner, GymRole::Owner);
    attachSaasUser($gym, $frontDesk, GymRole::Admin);
    Subscription::factory()->for($gym)->for($plan, 'plan')->create([
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->subMinute(),
        'current_period_starts_at' => null,
        'current_period_ends_at' => null,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.show'));

    $this->get(route('subscription.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscription/show')
            ->where('subscription.grants_access', false));

    $this->actingAs($frontDesk)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('platform admin assigns subscriptions and can suspend tenant access', function () {
    $admin = User::factory()->platformAdmin()->create();
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $plan = SaasPlan::factory()->create();
    attachSaasUser($gym, $owner, GymRole::Owner);

    $this->actingAs($admin)
        ->put(route('platform.gyms.subscription.update', $gym), [
            'saas_plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Active->value,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->toDateTimeString(),
            'current_period_ends_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->assertRedirect();

    $subscription = $gym->subscription()->firstOrFail();
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->saas_plan_id)->toBe($plan->getKey());

    $this->patch(route('platform.gyms.status.update', $gym), [
        'status' => GymStatus::Suspended->value,
    ])->assertRedirect();

    expect($gym->refresh()->status)->toBe(GymStatus::Suspended)
        ->and(PlatformActivityLog::query()->where('event', 'subscription.updated')->count())->toBe(1)
        ->and(PlatformActivityLog::query()->where('event', 'gym.status_changed')->count())->toBe(1);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertForbidden();

    $this->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('subscription.show'))
        ->assertOk();
});

test('platform admin promotion command and SaaS plan seeder are idempotent', function () {
    $user = User::factory()->create(['email' => 'admin@example.test']);

    $this->artisan('platform:promote-admin', ['email' => $user->email])
        ->expectsOutputToContain('Platform Super Admin')
        ->assertSuccessful();

    $this->seed(SaasPlanSeeder::class);
    $this->seed(SaasPlanSeeder::class);

    expect($user->refresh()->is_platform_admin)->toBeTrue()
        ->and(SaasPlan::query()->count())->toBe(3)
        ->and(SaasPlan::query()->where('is_active', true)->count())->toBe(3);
});

test('suspended gym owner is redirected to subscription after login', function () {
    $gym = Gym::factory()->create(['status' => GymStatus::Suspended]);
    $owner = User::factory()->create([
        'email' => 'suspended-owner@example.test',
        'password' => 'password',
    ]);
    attachSaasUser($gym, $owner, GymRole::Owner);

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'password',
    ])->assertRedirect(route('subscription.show'));
});
