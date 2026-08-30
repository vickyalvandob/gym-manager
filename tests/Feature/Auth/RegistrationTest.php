<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ActivityLog;
use App\Models\PlatformActivityLog;
use App\Models\SaasPlan;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->saasPlan = SaasPlan::factory()->create([
        'name' => 'Starter',
        'trial_days' => 14,
    ]);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'gym_name' => 'Gym Test Jakarta',
        'saas_plan_id' => $this->saasPlan->getKey(),
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $gym = $user->gyms()->firstOrFail();
    $activityLog = ActivityLog::query()->whereBelongsTo($gym)->firstOrFail();
    $platformLog = PlatformActivityLog::query()->where('event', 'gym.registered')->firstOrFail();

    expect($gym->name)->toBe('Gym Test Jakarta')
        ->and($gym->getRelation('pivot')->getAttribute('role'))->toBe(GymRole::Owner->value)
        ->and($gym->getRelation('pivot')->getAttribute('status'))->toBe(GymUserStatus::Active->value)
        ->and($activityLog->event)->toBe('user.created')
        ->and($activityLog->subject_type)->toBe(User::class)
        ->and($activityLog->subject_id)->toBe($user->getKey())
        ->and($gym->onboarding_completed_at)->toBeNull()
        ->and($gym->subscription?->status)->toBe(SubscriptionStatus::Trialing)
        ->and($gym->subscription?->saas_plan_id)->toBe($this->saasPlan->getKey())
        ->and($gym->subscription?->subscriber_id)->toBe($user->getKey())
        ->and($gym->subscription?->trial_ends_at?->isFuture())->toBeTrue()
        ->and($platformLog->subject_id)->toBe($gym->getKey());
});

test('free registration creates an active non expiring single gym subscription', function () {
    $freePlan = SaasPlan::factory()->create([
        'name' => 'Free',
        'price' => '0.00',
        'trial_days' => 0,
        'max_gyms' => 1,
        'max_members' => 20,
        'max_staff' => 5,
    ]);

    $this->post(route('register.store'), [
        'name' => 'Free User',
        'gym_name' => 'Gym Free',
        'saas_plan_id' => $freePlan->getKey(),
        'email' => 'free@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'free@example.com')->firstOrFail();
    $subscription = $user->subscription()->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->trial_ends_at)->toBeNull()
        ->and($subscription->current_period_ends_at)->toBeNull()
        ->and($subscription->gyms()->count())->toBe(1);
});

test('gym name is required and registration does not create partial data', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'saas_plan_id' => $this->saasPlan->getKey(),
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('gym_name');
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
