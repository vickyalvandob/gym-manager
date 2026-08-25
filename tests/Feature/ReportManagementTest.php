<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReportPeriod;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachReportUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('only owner can access reports', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();
    attachReportUser($gym, $owner, GymRole::Owner);
    attachReportUser($gym, $admin, GymRole::Admin);
    attachReportUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index'))
        ->assertForbidden();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('report filters return scoped revenue member membership and check-in metrics', function () {
    $this->travelTo('2026-08-25 05:00:00');

    $gym = Gym::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'membership_expiry_warning_days' => 7,
    ]);
    $foreignGym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $owner = User::factory()->create();
    attachReportUser($gym, $owner, GymRole::Owner);
    $plan = MembershipPlan::factory()->for($gym)->create();
    $foreignPlan = MembershipPlan::factory()->for($foreignGym)->create();

    $activeMember = Member::factory()->for($gym)->create([
        'name' => 'Active Report Member',
        'status' => MemberStatus::Active,
        'created_at' => '2026-08-02 02:00:00',
    ]);
    $expiringMember = Member::factory()->for($gym)->create([
        'name' => 'Expiring Report Member',
        'status' => MemberStatus::Active,
        'created_at' => '2026-07-10 02:00:00',
    ]);
    $expiredMember = Member::factory()->for($gym)->create([
        'name' => 'Expired Report Member',
        'status' => MemberStatus::Active,
        'created_at' => '2026-07-01 02:00:00',
    ]);
    Member::factory()->for($gym)->create([
        'status' => MemberStatus::Inactive,
        'created_at' => '2026-08-20 02:00:00',
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
            'start_date' => '2026-08-15',
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

    Payment::factory()->for($gym)->paid()->create([
        'member_id' => $activeMember->getKey(),
        'member_membership_id' => $activeMembership->getKey(),
        'amount' => '100000.00',
        'method' => PaymentMethod::Cash,
        'paid_at' => '2026-08-10 02:00:00',
        'received_by_id' => $owner->getKey(),
    ]);
    Payment::factory()->for($gym)->paid()->create([
        'member_id' => $expiringMember->getKey(),
        'member_membership_id' => $expiringMembership->getKey(),
        'amount' => '200000.00',
        'method' => PaymentMethod::BankTransfer,
        'paid_at' => '2026-08-20 02:00:00',
        'received_by_id' => $owner->getKey(),
    ]);

    CheckIn::factory()->for($gym)->create([
        'member_id' => $activeMember->getKey(),
        'member_membership_id' => $activeMembership->getKey(),
        'created_by' => $owner->getKey(),
        'checked_in_at' => '2026-08-10 02:00:00',
    ]);
    CheckIn::factory()->for($gym)->create([
        'member_id' => $activeMember->getKey(),
        'member_membership_id' => $activeMembership->getKey(),
        'created_by' => $owner->getKey(),
        'checked_in_at' => '2026-08-11 02:00:00',
    ]);
    CheckIn::factory()->for($gym)->create([
        'member_id' => $expiringMember->getKey(),
        'member_membership_id' => $expiringMembership->getKey(),
        'created_by' => $owner->getKey(),
        'checked_in_at' => '2026-08-20 02:00:00',
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
    Payment::factory()->for($foreignGym)->paid()->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
        'amount' => '999000.00',
        'paid_at' => '2026-08-10 02:00:00',
    ]);
    CheckIn::factory()->for($foreignGym)->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
        'checked_in_at' => '2026-08-10 02:00:00',
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index', [
            'period' => ReportPeriod::Custom->value,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-25',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('range.date_from', '2026-08-01')
            ->where('range.date_to', '2026-08-25')
            ->missing('report')
            ->loadDeferredProps('reports', fn (Assert $reload) => $reload
                ->where('report.revenue.total', '300000.00')
                ->where('report.revenue.payment_count', 2)
                ->where('report.revenue.average', '150000.00')
                ->has('report.revenue.method_breakdown', 2)
                ->where('report.members.active', 3)
                ->where('report.members.inactive', 1)
                ->where('report.members.new_in_period', 2)
                ->where('report.memberships.active', 2)
                ->where('report.memberships.expired', 1)
                ->where('report.memberships.expiring_soon', 1)
                ->where('report.memberships.started_in_period', 2)
                ->where('report.check_ins.total', 3)
                ->where('report.check_ins.unique_members', 2)
                ->where('report.check_ins.daily_average', '0.1')
                ->where('report.check_ins.top_visitors.0.member.id', $activeMember->getKey())
                ->where('report.check_ins.top_visitors.0.visit_count', 2)));
});

test('report defaults to this month and rejects unsafe custom date ranges', function () {
    $this->travelTo('2026-08-25 05:00:00');

    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $owner = User::factory()->create();
    attachReportUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', ReportPeriod::ThisMonth->value)
            ->where('range.date_from', '2026-08-01')
            ->where('range.date_to', '2026-08-25'));

    $this->get(route('reports.index', [
        'period' => ReportPeriod::Custom->value,
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-26',
    ]))->assertSessionHasErrors('date_to');

    $this->get(route('reports.index', [
        'period' => ReportPeriod::Custom->value,
        'date_from' => '2025-01-01',
        'date_to' => '2026-08-25',
    ]))->assertSessionHasErrors('date_to');
});

test('report revenue ignores pending invoices even when they are in range', function () {
    $this->travelTo('2026-08-25 05:00:00');

    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $owner = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $membership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create();
    attachReportUser($gym, $owner, GymRole::Owner);
    Payment::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'amount' => '450000.00',
        'status' => PaymentStatus::Pending,
        'created_at' => '2026-08-20 02:00:00',
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('reports', fn (Assert $reload) => $reload
                ->where('report.revenue.total', '0.00')
                ->where('report.revenue.payment_count', 0)));
});
