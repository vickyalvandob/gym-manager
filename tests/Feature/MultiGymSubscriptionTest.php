<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TrainerStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array{owner: User, gym: Gym, plan: SaasPlan, subscription: Subscription} */
function subscribedGymWorkspace(array $planAttributes = []): array
{
    $owner = User::factory()->create();
    $gym = Gym::factory()->create();
    $plan = SaasPlan::factory()->create([
        'max_gyms' => 2,
        'max_members' => 100,
        'max_staff' => 10,
        ...$planAttributes,
    ]);
    $gym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($plan, 'plan')
        ->create();
    $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    return compact('owner', 'gym', 'plan', 'subscription');
}

/** @return array<string, mixed> */
function multiGymMemberPayload(): array
{
    return [
        'name' => 'Member Baru',
        'phone' => '081234567890',
        'email' => 'member-baru@example.test',
        'gender' => 'male',
        'birth_date' => '1995-06-15',
        'address' => 'Jakarta',
        'emergency_contact' => '081298765432',
        'status' => MemberStatus::Active->value,
        'notes' => null,
    ];
}

/** @return array<string, mixed> */
function multiGymTrainerPayload(): array
{
    return [
        'name' => 'Trainer Baru',
        'phone' => '081288880001',
        'email' => 'trainer-baru@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'specialization' => 'Strength',
        'status' => TrainerStatus::Active->value,
        'notes' => null,
    ];
}

test('subscriber creates and switches gyms under one subscription', function () {
    ['owner' => $owner, 'gym' => $primaryGym, 'subscription' => $subscription] = subscribedGymWorkspace();

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $primaryGym->getKey()])
        ->get(route('gyms.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('gyms/index')
            ->where('subscription.usage.gyms', 1)
            ->where('subscription.can_create_gym', true));

    $this->post(route('gyms.store'), ['name' => 'Gym Cabang Bandung'])
        ->assertRedirect(route('onboarding.edit'));

    $secondaryGym = Gym::query()->where('name', 'Gym Cabang Bandung')->firstOrFail();

    expect($secondaryGym->subscription_id)->toBe($subscription->getKey())
        ->and($subscription->gyms()->count())->toBe(2)
        ->and($owner->gyms()->whereKey($secondaryGym->getKey())->exists())->toBeTrue();

    $this->put(route('gyms.switch', $primaryGym))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('current_gym_id', $primaryGym->getKey());
});

test('free plan blocks multi gym after one gym', function () {
    ['owner' => $owner, 'gym' => $gym, 'subscription' => $subscription] = subscribedGymWorkspace([
        'name' => 'Free',
        'price' => '0.00',
        'trial_days' => 0,
        'max_gyms' => 1,
        'max_members' => 20,
        'max_staff' => 5,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('gyms.store'), ['name' => 'Gym Kedua'])
        ->assertSessionHasErrors('name');

    expect($subscription->gyms()->count())->toBe(1);
});

test('member and staff limits are aggregated across subscription gyms', function () {
    ['owner' => $owner, 'gym' => $primaryGym, 'subscription' => $subscription] = subscribedGymWorkspace([
        'max_gyms' => 2,
        'max_members' => 1,
        'max_staff' => 1,
    ]);
    $secondaryGym = Gym::factory()->create(['subscription_id' => $subscription->getKey()]);
    $secondaryGym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);
    Member::factory()->for($primaryGym)->create();
    $existingStaff = User::factory()->create();
    $primaryGym->users()->attach($existingStaff, [
        'role' => GymRole::Admin->value,
        'status' => GymUserStatus::Active->value,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $secondaryGym->getKey()])
        ->post(route('members.store'), multiGymMemberPayload())
        ->assertSessionHasErrors('name');

    $this->post(route('trainers.store'), multiGymTrainerPayload())
        ->assertSessionHasErrors('email');

    expect($secondaryGym->members()->count())->toBe(0)
        ->and($secondaryGym->trainers()->count())->toBe(0)
        ->and(User::query()->where('email', 'trainer-baru@example.test')->doesntExist())->toBeTrue();
});

test('platform cannot downgrade a shared subscription below current gym usage', function () {
    ['owner' => $owner, 'gym' => $primaryGym, 'subscription' => $subscription] = subscribedGymWorkspace();
    $secondaryGym = Gym::factory()->create(['subscription_id' => $subscription->getKey()]);
    $secondaryGym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);
    $freePlan = SaasPlan::factory()->create(['max_gyms' => 1]);
    $platformAdmin = User::factory()->platformAdmin()->create();

    $this->actingAs($platformAdmin)
        ->put(route('platform.gyms.subscription.update', $primaryGym), [
            'saas_plan_id' => $freePlan->getKey(),
            'status' => SubscriptionStatus::Active->value,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->toDateTimeString(),
            'current_period_ends_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('saas_plan_id');

    expect($subscription->fresh()->saas_plan_id)->not->toBe($freePlan->getKey());
});

test('platform cannot shrink a plan below subscriber usage', function () {
    ['owner' => $owner, 'gym' => $primaryGym, 'plan' => $plan, 'subscription' => $subscription] = subscribedGymWorkspace();
    $secondaryGym = Gym::factory()->create(['subscription_id' => $subscription->getKey()]);
    $secondaryGym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);
    $platformAdmin = User::factory()->platformAdmin()->create();

    $this->actingAs($platformAdmin)
        ->patch(route('platform.saas-plans.update', $plan), [
            'name' => $plan->name,
            'description' => $plan->description,
            'price' => $plan->price,
            'currency' => 'IDR',
            'billing_interval' => $plan->billing_interval->value,
            'trial_days' => $plan->trial_days,
            'max_gyms' => 1,
            'max_members' => $plan->max_members,
            'max_staff' => $plan->max_staff,
            'is_active' => true,
            'sort_order' => $plan->sort_order,
        ])
        ->assertSessionHasErrors('max_gyms');

    expect($plan->fresh()->max_gyms)->toBe(2);
});
