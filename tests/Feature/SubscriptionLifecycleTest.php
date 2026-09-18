<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;

test('subscription lifecycle controls every operational area without disabling accounts', function (string $status, int $periodDays, bool $allowed) {
    config(['subscriptions.grace_days' => 7]);
    $owner = User::factory()->create();
    $subscription = Subscription::factory()->for($owner, 'subscriber')->create([
        'status' => $status, 'current_period_ends_at' => now()->addDays($periodDays),
        'trial_ends_at' => now()->addDays($periodDays),
    ]);
    $gym = Gym::factory()->create(['subscription_id' => $subscription->id]);
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $frontDesk = User::factory()->create();
    $gym->users()->attach($frontDesk, ['role' => GymRole::Admin, 'status' => GymUserStatus::Active]);
    $trainer = User::factory()->create();
    $gym->users()->attach($trainer, ['role' => GymRole::Trainer, 'status' => GymUserStatus::Active]);
    Trainer::factory()->for($gym)->create(['user_id' => $trainer->id]);
    $memberUser = User::factory()->create();
    Member::factory()->for($gym)->create(['user_id' => $memberUser->id]);

    $ownerResponse = $this->actingAs($owner)->get('/app');
    $allowed ? $ownerResponse->assertOk() : $ownerResponse->assertRedirect(route('subscription.show'));
    $this->get('/app/subscription')->assertOk();
    $frontResponse = $this->actingAs($frontDesk)->get('/app');
    $allowed ? $frontResponse->assertOk() : $frontResponse->assertForbidden();
    $trainerResponse = $this->actingAs($trainer)->get('/trainer');
    $allowed ? $trainerResponse->assertOk() : $trainerResponse->assertForbidden();
    $memberResponse = $this->actingAs($memberUser)->get('/my');
    $allowed ? $memberResponse->assertOk() : $memberResponse->assertStatus(503);
    expect($owner->fresh()->is_active)->toBeTrue()->and($memberUser->fresh()->is_active)->toBeTrue();
})->with([
    ['trialing', 1, true], ['trialing', -1, false], ['active', 1, true], ['active', -1, false],
    ['past_due', -2, true], ['past_due', -8, false], ['suspended', 10, false],
    ['cancelled', 2, true], ['cancelled', -1, false], ['expired', 10, false],
]);

test('missing subscription fails closed and owner retains billing access', function () {
    $gym = Gym::factory()->create(['subscription_id' => null]);
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $this->actingAs($owner)->get('/app')->assertRedirect(route('subscription.show'));
    $this->get('/app/subscription')->assertOk();
    $memberUser = User::factory()->create();
    Member::factory()->for($gym)->create(['user_id' => $memberUser->id]);
    $this->actingAs($memberUser)->get('/my')->assertStatus(503);
});

test('free subscriptions remain active without an end date and grace has an exact boundary', function () {
    $free = Subscription::factory()->create(['status' => SubscriptionStatus::Active, 'current_period_ends_at' => null]);
    expect($free->grantsAccess())->toBeTrue();
    $this->travelTo('2026-09-15 00:00:00');
    $pastDue = Subscription::factory()->create(['status' => SubscriptionStatus::PastDue, 'current_period_ends_at' => now()->subDays(7)]);
    expect($pastDue->grantsAccess())->toBeFalse()->and($pastDue->effectiveStatus())->toBe(SubscriptionStatus::Expired);
});
