<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\PtPackage;
use App\Models\SaasPlan;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\SaasPlanSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('free registration remains active after years and never accepts a paid plan from the browser', function () {
    $this->seed(SaasPlanSeeder::class);
    $this->post(route('register.store'), [
        'name' => 'Owner Freemium', 'gym_name' => 'Freemium Gym', 'email' => 'freemium@example.test',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        'saas_plan_id' => SaasPlan::query()->where('slug', 'scale')->firstOrFail()->id,
    ])->assertSessionHasNoErrors()->assertRedirect(route('onboarding.edit'));

    $subscription = User::query()->where('email', 'freemium@example.test')->firstOrFail()->subscription;
    $this->travel(5)->years();
    expect($subscription->plan->slug)->toBe('free')
        ->and($subscription->trial_ends_at)->toBeNull()
        ->and($subscription->current_period_ends_at)->toBeNull()
        ->and($subscription->grantsAccess())->toBeTrue();
});

test('resource limits aggregate across outlets and a larger plan permits creation without deleting history', function (string $model, string $limit, string $routeName, array $payload) {
    $gym = Gym::factory()->create();
    $subscription = $gym->subscription;
    $subscription->plan->update([$limit => 1, 'max_gyms' => 2]);
    $otherOutlet = Gym::factory()->create(['subscription_id' => $subscription->id]);
    $existing = $model::factory()->for($otherOutlet)->create();
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $beforeUsers = User::query()->count();

    $this->actingAs($owner)->withSession(['current_gym_id' => $gym->id])
        ->post(route($routeName), $payload)->assertSessionHasErrors('name');
    expect($model::query()->where('gym_id', $gym->id)->count())->toBe(0)
        ->and(User::query()->count())->toBe($beforeUsers);
    $this->assertModelExists($existing);

    $subscription->plan->update([$limit => 2]);
    $this->post(route($routeName), $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($model::query()->where('gym_id', $gym->id)->count())->toBe(1);
    $this->assertModelExists($existing);
})->with([
    'membership catalogue' => [MembershipPlan::class, 'max_membership_plans', 'membership-plans.store', [
        'name' => 'Bulanan', 'duration' => 1, 'duration_unit' => 'month', 'price' => '250000', 'is_active' => true,
    ]],
    'PT catalogue' => [PtPackage::class, 'max_pt_packages', 'pt-packages.store', [
        'name' => 'PT Dasar', 'session_count' => 8, 'price' => '800000', 'is_active' => true,
    ]],
    'trainers' => [Trainer::class, 'max_trainers', 'trainers.store', [
        'name' => 'Trainer Baru', 'phone' => '081234567890', 'email' => 'new-trainer@example.test',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'status' => 'active',
    ]],
]);

test('subscription shows the complete usage and prevents selecting undersized plans', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    MembershipPlan::factory()->for($gym)->count(2)->create();
    $smallPlan = SaasPlan::factory()->create(['max_membership_plans' => 1]);

    $this->actingAs($owner)->withSession(['current_gym_id' => $gym->id])
        ->get(route('subscription.show', ['plan_id' => $smallPlan->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('subscription.usage.membership_plans', 2)
            ->has('subscription.limits.trainers')
            ->has('subscription.limits.pt_packages')
            ->where('selectedPlan.can_select', false)->where('canSubmitPayment', false));
});

test('platform manages all resource limits and rejects new trial plans', function () {
    $admin = User::factory()->platformAdmin()->create();
    $payload = [
        'name' => 'Freemium Upgrade', 'price' => '399000', 'currency' => 'IDR', 'billing_interval' => 'monthly',
        'max_gyms' => 2, 'max_members' => 200, 'max_staff' => 8, 'max_trainers' => 4,
        'max_membership_plans' => 10, 'max_pt_packages' => 12, 'is_active' => true, 'sort_order' => 10,
    ];
    $this->actingAs($admin)->post(route('platform.saas-plans.store'), [...$payload, 'trial_days' => 14])
        ->assertSessionHasErrors('trial_days');
    $this->post(route('platform.saas-plans.store'), $payload)->assertSessionHasNoErrors();
    $plan = SaasPlan::query()->where('name', $payload['name'])->firstOrFail();
    expect($plan->trial_days)->toBe(0)->and($plan->max_trainers)->toBe(4)
        ->and($plan->max_membership_plans)->toBe(10)->and($plan->max_pt_packages)->toBe(12);
});

test('switching an outlet respects its trainer role', function () {
    $user = User::factory()->create();
    $gym = Gym::factory()->create();
    $gym->users()->attach($user, ['role' => GymRole::Trainer, 'status' => GymUserStatus::Active]);
    Trainer::factory()->for($gym)->create(['user_id' => $user->id]);
    $this->actingAs($user)->put(route('gyms.switch', $gym))
        ->assertRedirect(route('trainer.dashboard'))->assertSessionHas('current_gym_id', $gym->id);
});
