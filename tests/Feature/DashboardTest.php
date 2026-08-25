<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachDashboardUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->withGym()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users without an active gym cannot visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertForbidden();
});

test('owner dashboard returns tenant scoped operational and revenue metrics', function () {
    $this->travelTo('2026-08-25 05:00:00');

    $gym = Gym::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'membership_expiry_warning_days' => 7,
    ]);
    $foreignGym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $owner = User::factory()->create();
    attachDashboardUser($gym, $owner, GymRole::Owner);
    $plan = MembershipPlan::factory()->for($gym)->create();
    $foreignPlan = MembershipPlan::factory()->for($foreignGym)->create();

    $activeMember = Member::factory()->for($gym)->create([
        'name' => 'Member Aktif',
        'created_at' => '2026-08-02 02:00:00',
    ]);
    $expiringMember = Member::factory()->for($gym)->create([
        'name' => 'Member Segera Berakhir',
        'created_at' => '2026-08-10 02:00:00',
    ]);
    $expiredMember = Member::factory()->for($gym)->create([
        'name' => 'Member Kedaluwarsa',
        'created_at' => '2026-07-01 02:00:00',
    ]);
    $activeMembership = MemberMembership::factory()
        ->for($gym)
        ->for($activeMember)
        ->for($plan, 'membershipPlan')
        ->create([
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
        ]);
    $expiringMembership = MemberMembership::factory()
        ->for($gym)
        ->for($expiringMember)
        ->for($plan, 'membershipPlan')
        ->create([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-28',
        ]);
    MemberMembership::factory()
        ->for($gym)
        ->for($expiredMember)
        ->for($plan, 'membershipPlan')
        ->create([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

    CheckIn::factory()->for($gym)->create([
        'member_id' => $activeMember->getKey(),
        'member_membership_id' => $activeMembership->getKey(),
        'created_by' => $owner->getKey(),
        'checked_in_at' => '2026-08-25 02:00:00',
    ]);
    CheckIn::factory()->for($gym)->create([
        'member_id' => $expiringMember->getKey(),
        'member_membership_id' => $expiringMembership->getKey(),
        'created_by' => $owner->getKey(),
        'checked_in_at' => '2026-08-24 18:00:00',
    ]);
    Payment::factory()->for($gym)->paid()->create([
        'member_id' => $activeMember->getKey(),
        'member_membership_id' => $activeMembership->getKey(),
        'amount' => '100000.00',
        'method' => PaymentMethod::Cash,
        'paid_at' => '2026-08-25 02:30:00',
        'received_by_id' => $owner->getKey(),
    ]);
    Payment::factory()->for($gym)->paid()->create([
        'member_id' => $expiringMember->getKey(),
        'member_membership_id' => $expiringMembership->getKey(),
        'amount' => '200000.00',
        'method' => PaymentMethod::BankTransfer,
        'paid_at' => '2026-08-05 02:30:00',
        'received_by_id' => $owner->getKey(),
    ]);

    $foreignMember = Member::factory()->for($foreignGym)->create();
    $foreignMembership = MemberMembership::factory()
        ->for($foreignGym)
        ->for($foreignMember)
        ->for($foreignPlan, 'membershipPlan')
        ->create([
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
        ]);
    CheckIn::factory()->for($foreignGym)->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
        'checked_in_at' => '2026-08-25 02:00:00',
    ]);
    Payment::factory()->for($foreignGym)->paid()->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
        'amount' => '999000.00',
        'paid_at' => '2026-08-25 02:00:00',
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->missing('snapshot')
            ->loadDeferredProps('dashboard', fn (Assert $reload) => $reload
                ->where('snapshot.metrics.active_members', 2)
                ->where('snapshot.metrics.expired_members', 1)
                ->where('snapshot.metrics.expiring_soon', 1)
                ->where('snapshot.metrics.new_members_this_month', 2)
                ->where('snapshot.metrics.check_ins_today', 2)
                ->where('snapshot.metrics.revenue_today', '100000.00')
                ->where('snapshot.metrics.revenue_this_month', '300000.00')
                ->has('snapshot.recent_check_ins', 2)
                ->has('snapshot.recent_payments', 2)));
});

test('trainer dashboard omits financial and identifiable operational activity', function () {
    $gym = Gym::factory()->create();
    $trainer = User::factory()->create();
    attachDashboardUser($gym, $trainer, GymRole::Trainer);
    Member::factory()->for($gym)->create(['status' => MemberStatus::Inactive]);

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('dashboard', fn (Assert $reload) => $reload
                ->where('snapshot.metrics.revenue_today', null)
                ->where('snapshot.metrics.revenue_this_month', null)
                ->has('snapshot.recent_check_ins', 0)
                ->has('snapshot.recent_payments', 0)));
});
