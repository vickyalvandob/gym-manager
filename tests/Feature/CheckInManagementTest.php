<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Models\ActivityLog;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function attachCheckInUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

function createActiveCheckInMembership(Gym $gym, Member $member): MemberMembership
{
    $plan = MembershipPlan::factory()->for($gym)->create();

    return MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create([
            'start_date' => today($gym->timezone)->subWeek(),
            'end_date' => today($gym->timezone)->addMonth(),
        ]);
}

test('owner and front desk can access check-ins while trainer cannot', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();
    attachCheckInUser($gym, $owner, GymRole::Owner);
    attachCheckInUser($gym, $admin, GymRole::Admin);
    attachCheckInUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('check-ins.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('check-ins.index'))
        ->assertOk();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('check-ins.index'))
        ->assertForbidden();
});

test('active member with active membership can check in with audit trail', function () {
    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $membership = createActiveCheckInMembership($gym, $member);
    attachCheckInUser($gym, $user, GymRole::Admin);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.check-ins.store', $member))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $checkIn = $gym->checkIns()->firstOrFail();

    expect($checkIn->member_id)->toBe($member->getKey())
        ->and($checkIn->member_membership_id)->toBe($membership->getKey())
        ->and($checkIn->created_by)->toBe($user->getKey())
        ->and($checkIn->checked_in_at)->not->toBeNull()
        ->and(ActivityLog::query()->where('event', 'checkin.created')->count())->toBe(1);
});

test('inactive and expired members cannot check in', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $inactiveMember = Member::factory()->for($gym)->create([
        'status' => MemberStatus::Inactive,
    ]);
    $expiredMember = Member::factory()->for($gym)->create();
    createActiveCheckInMembership($gym, $inactiveMember);
    $expiredPlan = MembershipPlan::factory()->for($gym)->create();
    MemberMembership::factory()
        ->for($gym)
        ->for($expiredMember)
        ->for($expiredPlan, 'membershipPlan')
        ->create([
            'start_date' => today($gym->timezone)->subMonths(2),
            'end_date' => today($gym->timezone)->subMonth(),
        ]);
    attachCheckInUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.check-ins.store', $inactiveMember))
        ->assertSessionHasErrors('check_in');

    $this->post(route('members.check-ins.store', $expiredMember))
        ->assertSessionHasErrors('check_in');

    expect($gym->checkIns()->count())->toBe(0)
        ->and(ActivityLog::query()->where('event', 'checkin.created')->count())->toBe(0);
});

test('duplicate check-in is blocked for five minutes but not for the whole day', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    createActiveCheckInMembership($gym, $member);
    attachCheckInUser($gym, $user, GymRole::Admin);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.check-ins.store', $member))
        ->assertRedirect();

    $this->post(route('members.check-ins.store', $member))
        ->assertSessionHasErrors('check_in');

    expect($gym->checkIns()->count())->toBe(1);

    $this->travel(6)->minutes();

    $this->post(route('members.check-ins.store', $member))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gym->checkIns()->count())->toBe(2);
});

test('check-in reads and writes are isolated to the current gym', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $foreignMember = Member::factory()->for($foreignGym)->create();
    $membership = createActiveCheckInMembership($gym, $member);
    $foreignMembership = createActiveCheckInMembership($foreignGym, $foreignMember);
    attachCheckInUser($gym, $user, GymRole::Owner);
    $checkIn = CheckIn::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'created_by' => $user->getKey(),
    ]);
    CheckIn::factory()->for($foreignGym)->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('check-ins.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('history.total', 1)
            ->where('history.data.0.id', $checkIn->getKey())
            ->has('recentCheckIns', 1));

    $this->post(route('members.check-ins.store', $foreignMember))
        ->assertNotFound();

    expect($foreignGym->checkIns()->count())->toBe(1);
});

test('member search validates membership and history filters are server side', function () {
    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create([
        'member_number' => 'MBR-CHECKIN',
        'name' => 'Alpha Member',
        'phone' => '081200001111',
    ]);
    $membership = createActiveCheckInMembership($gym, $member);
    attachCheckInUser($gym, $user, GymRole::Admin);
    $checkIn = CheckIn::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'created_by' => $user->getKey(),
        'checked_in_at' => '2026-08-25 03:00:00',
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('check-ins.index', [
            'member_search' => '081200001111',
            'history_search' => 'MBR-CHECKIN',
            'date_from' => '2026-08-25',
            'date_to' => '2026-08-25',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('check-ins/index')
            ->has('memberSearchResults', 1)
            ->where('memberSearchResults.0.id', $member->getKey())
            ->where('memberSearchResults.0.membership.id', $membership->getKey())
            ->where('memberSearchResults.0.eligibility.can_check_in', true)
            ->where('history.total', 1)
            ->where('history.data.0.id', $checkIn->getKey()));
});

test('member detail exposes check-in eligibility and paginated history', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $membership = createActiveCheckInMembership($gym, $member);
    attachCheckInUser($gym, $user, GymRole::Owner);
    $checkIn = CheckIn::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'created_by' => $user->getKey(),
        'checked_in_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('checkInEligibility.can_check_in', true)
            ->missing('checkIns')
            ->loadDeferredProps('memberHistory', fn (Assert $reload) => $reload
                ->where('checkIns.total', 1)
                ->where('checkIns.data.0.id', $checkIn->getKey())
                ->where('checkIns.data.0.membership.id', $membership->getKey())));
});

test('trainer cannot create check-ins', function () {
    $gym = Gym::factory()->create();
    $trainer = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    createActiveCheckInMembership($gym, $member);
    attachCheckInUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.check-ins.store', $member))
        ->assertForbidden();

    expect($gym->checkIns()->count())->toBe(0);
});

test('development seeder creates idempotent check-in history', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();

    expect($gym->checkIns()->count())->toBe(3);
});
