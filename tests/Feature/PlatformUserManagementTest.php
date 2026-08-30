<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachPlatformManagedUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('platform admin can browse registered users and inspect subscriber ownership', function () {
    $admin = User::factory()->platformAdmin()->create();
    $owner = User::factory()->create(['name' => 'Owner Subscriber']);
    $staff = User::factory()->create(['name' => 'Front Desk Gym']);
    $plan = SaasPlan::factory()->create(['name' => 'Growth']);
    $gym = Gym::factory()->create(['name' => 'Gym Utama']);
    attachPlatformManagedUser($gym, $owner, GymRole::Owner);
    attachPlatformManagedUser($gym, $staff, GymRole::Admin);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($plan, 'plan')
        ->create();
    $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    $this->actingAs($admin)
        ->get(route('platform.users.index', ['account_type' => 'subscriber']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/users/index')
            ->where('users.total', 1)
            ->where('users.data.0.id', $owner->getKey())
            ->where('users.data.0.account_type', 'subscriber')
            ->where('users.data.0.subscription.plan_name', 'Growth'));

    $this->get(route('platform.users.show', $owner))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/users/show')
            ->where('managedUser.id', $owner->getKey())
            ->where('managedUser.owned_gyms_count', 1)
            ->where('managedUser.gyms.0.role', GymRole::Owner->value)
            ->where('managedUser.subscription.usage.gyms', 1)
            ->where('managedUser.subscription.usage.staff', 1));
});

test('platform admin can update subscriber package from the user workspace', function () {
    $admin = User::factory()->platformAdmin()->create();
    $owner = User::factory()->create();
    $gym = Gym::factory()->create();
    $currentPlan = SaasPlan::factory()->create(['name' => 'Starter']);
    $newPlan = SaasPlan::factory()->create([
        'name' => 'Growth',
        'max_gyms' => 3,
        'max_members' => 2000,
        'max_staff' => 30,
    ]);
    attachPlatformManagedUser($gym, $owner, GymRole::Owner);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($currentPlan, 'plan')
        ->create();
    $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    $this->actingAs($admin)
        ->put(route('platform.users.subscription.update', $owner), [
            'saas_plan_id' => $newPlan->getKey(),
            'status' => SubscriptionStatus::Active->value,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->toDateTimeString(),
            'current_period_ends_at' => now()->addMonth()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($subscription->refresh()->saas_plan_id)->toBe($newPlan->getKey())
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and(PlatformActivityLog::query()->where('event', 'subscription.updated')->count())->toBe(1);
});

test('platform admin can deactivate a regular user but cannot deactivate a platform admin', function () {
    $admin = User::factory()->platformAdmin()->create();
    $user = User::factory()->create([
        'email' => 'managed@example.test',
        'password' => 'password',
    ]);

    $this->actingAs($admin)
        ->patch(route('platform.users.status.update', $user), ['is_active' => false])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($user->refresh()->is_active)->toBeFalse()
        ->and(PlatformActivityLog::query()->where('event', 'user.access_updated')->count())->toBe(1);

    $this->post(route('logout'))->assertRedirect();
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($admin)
        ->patch(route('platform.users.status.update', $admin), ['is_active' => false])
        ->assertSessionHasErrors('is_active');

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('inactive authenticated users are logged out on their next request', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('gym users cannot access platform user management', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get(route('platform.users.index'))
        ->assertForbidden();
});
