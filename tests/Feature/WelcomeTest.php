<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\SaasPlan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('landing pricing publishes only active paid plans using current catalogue prices', function () {
    SaasPlan::factory()->create(['slug' => 'free', 'price' => 0]);
    SaasPlan::factory()->create(['name' => 'Hidden', 'is_active' => false]);
    $plan = SaasPlan::factory()->create(['name' => 'Growth', 'price' => '650000.00', 'max_trainers' => 12]);

    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('welcome')->has('plans', 1)
        ->where('plans.0.id', $plan->id)
        ->where('plans.0.price', '650000.00')
        ->where('plans.0.max_trainers', 12)
        ->missing('plans.0.trial_days')->where('workspace', null));

    $plan->update(['price' => '750000.00']);
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('plans.0.price', '750000.00'));
});

test('landing remains available when no paid plans are published', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('plans', 0));
});

test('landing resolves the correct workspace outside the gym middleware', function (GymRole $role, string $workspace) {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $gym->users()->attach($user, ['role' => $role, 'status' => GymUserStatus::Active]);
    if ($role === GymRole::Owner) {
        $gym->subscription->update(['subscriber_id' => $user->id]);
    }
    $this->actingAs($user)->withSession(['current_gym_id' => $gym->id])
        ->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('workspace', $workspace)->where('canChoosePlan', $role === GymRole::Owner));
})->with([
    [GymRole::Owner, 'app.home'], [GymRole::Admin, 'app.home'], [GymRole::Trainer, 'trainer.dashboard'],
]);

test('member and platform admin receive their own landing destinations', function () {
    $user = User::factory()->create();
    Member::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user)->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('workspace', 'my.show'));
    $this->actingAs(User::factory()->platformAdmin()->create())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('workspace', 'platform.dashboard'));
});
