<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MembershipDurationUnit;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function attachMemberMembershipUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('owner and front desk can assign memberships while trainer cannot', function () {
    $gym = Gym::factory()->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();
    $ownerMember = Member::factory()->for($gym)->create();
    $adminMember = Member::factory()->for($gym)->create();
    $trainerMember = Member::factory()->for($gym)->create();

    attachMemberMembershipUser($gym, $owner, GymRole::Owner);
    attachMemberMembershipUser($gym, $admin, GymRole::Admin);
    attachMemberMembershipUser($gym, $trainer, GymRole::Trainer);

    $payload = [
        'membership_plan_id' => $plan->getKey(),
        'start_date' => today()->toDateString(),
    ];

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $ownerMember), $payload)
        ->assertRedirect();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $adminMember), $payload)
        ->assertRedirect();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $trainerMember), $payload)
        ->assertForbidden();

    expect($gym->memberMemberships()->count())->toBe(2);
});

test('assignment calculates an inclusive end date snapshots the plan and writes an audit log', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create([
        'name' => 'Monthly Premium',
        'duration' => 1,
        'duration_unit' => MembershipDurationUnit::Month,
        'price' => '275000.50',
    ]);
    attachMemberMembershipUser($gym, $user, GymRole::Owner);
    $startDate = today();

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $member), [
            'membership_plan_id' => $plan->getKey(),
            'start_date' => $startDate->toDateString(),
        ])
        ->assertRedirect();

    $membership = $member->memberships()->firstOrFail();
    $activityLog = ActivityLog::query()
        ->where('event', 'membership.assigned')
        ->firstOrFail();

    expect($membership->gym_id)->toBe($gym->getKey())
        ->and($membership->plan_name)->toBe('Monthly Premium')
        ->and($membership->price)->toBe('275000.50')
        ->and($membership->start_date->toDateString())->toBe($startDate->toDateString())
        ->and($membership->end_date->toDateString())
        ->toBe($startDate->copy()->addMonthNoOverflow()->subDay()->toDateString())
        ->and($activityLog->gym_id)->toBe($gym->getKey())
        ->and($activityLog->properties)->toMatchArray([
            'member_id' => $member->getKey(),
            'membership_plan_id' => $plan->getKey(),
            'plan_name' => 'Monthly Premium',
        ]);

    $this->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeMembership.id', $membership->getKey())
            ->where('activeMembership.status', 'active'));

    $plan->update(['name' => 'Nama Paket Baru', 'price' => '300000.00']);

    expect($membership->fresh()->plan_name)->toBe('Monthly Premium')
        ->and($membership->fresh()->price)->toBe('275000.50');
});

test('member pages expose active upcoming expired and expiry warning states', function () {
    $gym = Gym::factory()->create(['membership_expiry_warning_days' => 7]);
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    attachMemberMembershipUser($gym, $user, GymRole::Admin);

    MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create([
            'plan_name' => 'Expired Plan',
            'start_date' => today()->subMonths(2),
            'end_date' => today()->subMonth(),
        ]);
    MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create([
            'plan_name' => 'Active Plan',
            'start_date' => today()->subWeeks(2),
            'end_date' => today()->addDays(3),
        ]);
    MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create([
            'plan_name' => 'Upcoming Plan',
            'start_date' => today()->addDays(4),
            'end_date' => today()->addMonth(),
        ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/show')
            ->where('activeMembership.plan_name', 'Active Plan')
            ->where('activeMembership.status', 'active')
            ->where('activeMembership.is_expiring_soon', true)
            ->where('upcomingMembership.plan_name', 'Upcoming Plan')
            ->where('upcomingMembership.status', 'upcoming')
            ->where('hasMembershipHistory', true)
            ->missing('memberships')
            ->loadDeferredProps('memberHistory', fn (Assert $reload) => $reload
                ->where('memberships.total', 3)
                ->where('memberships.data.2.status', 'expired')
                ->has('ptPackageHistory')
                ->has('upcomingPtSessions')
                ->has('ptSessionHistory')
                ->has('checkIns')));

    $this->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.data.0.membership.plan_name', 'Active Plan')
            ->where('members.data.0.membership.status', 'active'));
});

test('renewal creates a linked period and rejects overlapping dates', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $monthlyPlan = MembershipPlan::factory()->for($gym)->create([
        'duration' => 1,
        'duration_unit' => MembershipDurationUnit::Month,
    ]);
    $quarterlyPlan = MembershipPlan::factory()->for($gym)->create([
        'duration' => 3,
        'duration_unit' => MembershipDurationUnit::Month,
    ]);
    attachMemberMembershipUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $member), [
            'membership_plan_id' => $monthlyPlan->getKey(),
            'start_date' => today()->toDateString(),
        ])
        ->assertRedirect();

    $previousMembership = $member->memberships()->firstOrFail();
    $renewalStart = $previousMembership->end_date->copy()->addDay();

    $this->post(route('members.memberships.renew', [$member, $previousMembership]), [
        'membership_plan_id' => $quarterlyPlan->getKey(),
        'start_date' => $renewalStart->toDateString(),
    ])->assertRedirect();

    $renewal = $member->memberships()->latest('id')->firstOrFail();

    expect($member->memberships()->count())->toBe(2)
        ->and($renewal->renewed_from_id)->toBe($previousMembership->getKey())
        ->and($renewal->start_date->toDateString())->toBe($renewalStart->toDateString())
        ->and(ActivityLog::query()->where('event', 'membership.renewed')->count())->toBe(1);

    $this->post(route('members.memberships.renew', [$member, $renewal]), [
        'membership_plan_id' => $monthlyPlan->getKey(),
        'start_date' => $renewal->end_date->toDateString(),
    ])->assertSessionHasErrors('start_date');

    expect($member->memberships()->count())->toBe(2);
});

test('membership writes are gym scoped and reject foreign records and plans', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $foreignMember = Member::factory()->for($foreignGym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $foreignPlan = MembershipPlan::factory()->for($foreignGym)->create();
    attachMemberMembershipUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $foreignMember), [
            'membership_plan_id' => $plan->getKey(),
            'start_date' => today()->toDateString(),
        ])
        ->assertNotFound();

    $this->post(route('members.memberships.store', $member), [
        'membership_plan_id' => $foreignPlan->getKey(),
        'start_date' => today()->toDateString(),
    ])->assertSessionHasErrors('membership_plan_id');

    expect(MemberMembership::query()->count())->toBe(0);
});

test('membership plans with history cannot be deleted', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    attachMemberMembershipUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $member), [
            'membership_plan_id' => $plan->getKey(),
            'start_date' => today()->toDateString(),
        ])
        ->assertRedirect();

    $this->delete(route('membership-plans.destroy', $plan))
        ->assertSessionHasErrors('membership_plan');

    $this->assertModelExists($plan);
});

test('development seeder creates idempotent membership history examples', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();

    expect($gym->memberMemberships()->count())->toBe(5)
        ->and($gym->memberMemberships()->whereNotNull('renewed_from_id')->count())->toBe(1);
});
