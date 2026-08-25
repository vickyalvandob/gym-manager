<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function attachPaymentUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('owner and front desk can view payments while trainer cannot', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();
    attachPaymentUser($gym, $owner, GymRole::Owner);
    attachPaymentUser($gym, $admin, GymRole::Admin);
    attachPaymentUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('payments.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('payments.index'))
        ->assertOk();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('payments.index'))
        ->assertForbidden();
});

test('assigning and renewing memberships create sequential pending invoices', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create(['price' => '275000.50']);
    attachPaymentUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $member), [
            'membership_plan_id' => $plan->getKey(),
            'start_date' => today($gym->timezone)->toDateString(),
        ])
        ->assertRedirect();

    $membership = $member->memberships()->firstOrFail();
    $firstPayment = $membership->payment()->firstOrFail();
    $renewalStart = $membership->end_date->copy()->addDay();

    $this->post(route('members.memberships.renew', [$member, $membership]), [
        'membership_plan_id' => $plan->getKey(),
        'start_date' => $renewalStart->toDateString(),
    ])->assertRedirect();

    $renewal = $member->memberships()->latest('id')->firstOrFail();
    $secondPayment = $renewal->payment()->firstOrFail();
    $invoicePrefix = 'INV-'.today($gym->timezone)->format('Ym').'-';

    expect($firstPayment->invoice_number)->toBe($invoicePrefix.'000001')
        ->and($firstPayment->amount)->toBe('275000.50')
        ->and($firstPayment->status)->toBe(PaymentStatus::Pending)
        ->and($firstPayment->method)->toBeNull()
        ->and($secondPayment->invoice_number)->toBe($invoicePrefix.'000002')
        ->and($gym->fresh()->next_invoice_sequence)->toBe(3)
        ->and(ActivityLog::query()->where('event', 'payment.created')->count())->toBe(2);
});

test('an invoice can be created once for legacy membership history', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $membership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create(['price' => '650000.00']);
    attachPaymentUser($gym, $user, GymRole::Admin);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('member-memberships.payment.store', $membership))
        ->assertRedirect();

    expect($membership->payment()->firstOrFail()->amount)->toBe('650000.00');

    $this->post(route('member-memberships.payment.store', $membership))
        ->assertSessionHasErrors('payment');

    expect($gym->payments()->count())->toBe(1)
        ->and($gym->fresh()->next_invoice_sequence)->toBe(2);
});

test('pending payment can be marked paid once with method receiver and audit trail', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $membership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create();
    $payment = Payment::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'amount' => $membership->price,
    ]);
    attachPaymentUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->patch(route('payments.paid', $payment), [
            'method' => PaymentMethod::BankTransfer->value,
            'notes' => ' Transfer terverifikasi. ',
        ])
        ->assertRedirect();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->method)->toBe(PaymentMethod::BankTransfer)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->notes)->toBe('Transfer terverifikasi.')
        ->and($payment->received_by_id)->toBe($user->getKey())
        ->and(ActivityLog::query()->where('event', 'payment.paid')->count())->toBe(1);

    $this->patch(route('payments.paid', $payment), [
        'method' => PaymentMethod::Cash->value,
    ])->assertSessionHasErrors('payment');

    expect(ActivityLog::query()->where('event', 'payment.paid')->count())->toBe(1);
});

test('payment reads and writes are isolated to the current gym', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $foreignMember = Member::factory()->for($foreignGym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    $foreignPlan = MembershipPlan::factory()->for($foreignGym)->create();
    $membership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create();
    $foreignMembership = MemberMembership::factory()
        ->for($foreignGym)
        ->for($foreignMember)
        ->for($foreignPlan, 'membershipPlan')
        ->create();
    $payment = Payment::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
    ]);
    $foreignPayment = Payment::factory()->for($foreignGym)->create([
        'member_id' => $foreignMember->getKey(),
        'member_membership_id' => $foreignMembership->getKey(),
    ]);
    attachPaymentUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('payments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.total', 1)
            ->where('payments.data.0.id', $payment->getKey()));

    $this->patch(route('payments.paid', $foreignPayment), [
        'method' => PaymentMethod::Cash->value,
    ])->assertNotFound();

    $this->post(route('member-memberships.payment.store', $foreignMembership))
        ->assertNotFound();

    expect($foreignPayment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('payment history filters and report summary use the same scoped dataset', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create([
        'member_number' => 'MBR-REPORT',
        'name' => 'Member Report',
    ]);
    $plan = MembershipPlan::factory()->for($gym)->create();
    attachPaymentUser($gym, $user, GymRole::Admin);

    $paidMembership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create(['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
    $pendingMembership = MemberMembership::factory()
        ->for($gym)
        ->for($member)
        ->for($plan, 'membershipPlan')
        ->create(['start_date' => '2026-09-01', 'end_date' => '2026-09-30']);
    Payment::factory()->for($gym)->paid()->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $paidMembership->getKey(),
        'invoice_number' => 'INV-REPORT-PAID',
        'amount' => '250000.00',
        'method' => PaymentMethod::Cash,
        'received_by_id' => $user->getKey(),
        'created_at' => '2026-08-10 03:00:00',
    ]);
    Payment::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $pendingMembership->getKey(),
        'invoice_number' => 'INV-REPORT-PENDING',
        'amount' => '300000.00',
        'created_at' => '2026-08-15 03:00:00',
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('payments.index', [
            'search' => 'MBR-REPORT',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/index')
            ->where('payments.total', 2)
            ->where('summary.paid_total', '250000.00')
            ->where('summary.outstanding_total', '300000.00')
            ->where('summary.paid_count', 1)
            ->where('summary.pending_count', 1));

    $this->get(route('payments.index', [
        'status' => PaymentStatus::Paid->value,
        'method' => PaymentMethod::Cash->value,
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('payments.total', 1)
        ->where('payments.data.0.invoice_number', 'INV-REPORT-PAID')
        ->where('summary.paid_total', '250000.00')
        ->where('summary.outstanding_total', '0.00'));
});

test('member detail exposes its membership invoice and payment state', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $member = Member::factory()->for($gym)->create();
    $plan = MembershipPlan::factory()->for($gym)->create();
    attachPaymentUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.memberships.store', $member), [
            'membership_plan_id' => $plan->getKey(),
            'start_date' => today($gym->timezone)->toDateString(),
        ])
        ->assertRedirect();

    $payment = $member->payments()->firstOrFail();

    $this->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeMembership.payment.id', $payment->getKey())
            ->where('activeMembership.payment.status', PaymentStatus::Pending->value)
            ->where('memberships.data.0.payment.invoice_number', $payment->invoice_number)
            ->has('paymentMethodOptions', 5));
});

test('development seeder creates idempotent payment history and advances invoice sequence', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();

    expect($gym->payments()->count())->toBe(5)
        ->and($gym->payments()->where('status', PaymentStatus::Paid)->count())->toBe(3)
        ->and($gym->payments()->where('status', PaymentStatus::Pending)->count())->toBe(2)
        ->and($gym->fresh()->next_invoice_sequence)->toBeGreaterThanOrEqual(6);
});
