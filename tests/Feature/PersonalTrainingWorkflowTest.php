<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberPtPackageStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PtSessionStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberPtPackage;
use App\Models\Payment;
use App\Models\PtPackage;
use App\Models\PtSession;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Models\User;

function attachPersonalTrainingUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

function assignPersonalTrainingMember(
    Gym $gym,
    Trainer $trainer,
    Member $member,
    ?User $actor = null,
): void {
    TrainerMember::query()->create([
        'gym_id' => $gym->getKey(),
        'trainer_id' => $trainer->getKey(),
        'member_id' => $member->getKey(),
        'assigned_at' => now(),
        'is_active' => true,
        'assigned_by' => $actor?->getKey(),
    ]);
}

test('front desk purchase atomically assigns trainer activates PT and records payment', function () {
    $this->travelTo('2026-08-26 05:00:00');
    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $frontDesk = User::factory()->create();
    attachPersonalTrainingUser($gym, $frontDesk, GymRole::Admin);
    $trainerUser = User::factory()->create();
    attachPersonalTrainingUser($gym, $trainerUser, GymRole::Trainer);
    $trainer = Trainer::factory()->for($gym)->create(['user_id' => $trainerUser->getKey()]);
    $member = Member::factory()->for($gym)->create();
    $ptPackage = PtPackage::factory()->for($gym)->create([
        'name' => 'PT Regular',
        'session_count' => 8,
        'validity_days' => 60,
        'price' => '900000.00',
    ]);

    $this->actingAs($frontDesk)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.pt-packages.store', $member), [
            'pt_package_id' => $ptPackage->getKey(),
            'trainer_id' => $trainer->getKey(),
            'start_date' => '2026-08-26',
            'payment_method' => PaymentMethod::BankTransfer->value,
        ])
        ->assertRedirect(route('members.show', $member));

    $purchase = $gym->memberPtPackages()->firstOrFail();
    $payment = $gym->payments()->firstOrFail();

    expect($purchase->status)->toBe(MemberPtPackageStatus::Active)
        ->and($purchase->payment_status)->toBe(PaymentStatus::Paid)
        ->and($purchase->total_sessions)->toBe(8)
        ->and($purchase->expires_at?->toDateString())->toBe('2026-10-24')
        ->and($payment->type)->toBe(PaymentType::PersonalTraining)
        ->and($payment->member_membership_id)->toBeNull()
        ->and($payment->member_pt_package_id)->toBe($purchase->getKey())
        ->and($payment->amount)->toBe('900000.00')
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($member->trainers()->firstOrFail()->is($trainer))->toBeTrue()
        ->and(ActivityLog::query()->where('event', 'member_pt_package.created')->count())->toBe(1);
});

test('trainer schedules only assigned PT clients and overlap is rejected', function () {
    $this->travelTo('2026-08-26 05:00:00');
    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $trainerUser = User::factory()->create();
    attachPersonalTrainingUser($gym, $trainerUser, GymRole::Trainer);
    $trainer = Trainer::factory()->for($gym)->create(['user_id' => $trainerUser->getKey()]);
    $member = Member::factory()->for($gym)->create();
    assignPersonalTrainingMember($gym, $trainer, $member);
    $package = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'start_date' => '2026-08-26',
        'expires_at' => '2026-10-24',
    ]);

    $this->actingAs($trainerUser)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('pt-sessions.store'), [
            'member_pt_package_id' => $package->getKey(),
            'date' => '2026-08-28',
            'start_time' => '18:00',
            'duration_minutes' => 60,
        ])
        ->assertRedirect();

    $session = $gym->ptSessions()->firstOrFail();
    expect($session->scheduled_at->toIso8601String())->toBe('2026-08-28T11:00:00+00:00')
        ->and($package->fresh()->used_sessions)->toBe(0);

    $this->post(route('pt-sessions.store'), [
        'member_pt_package_id' => $package->getKey(),
        'date' => '2026-08-28',
        'start_time' => '18:30',
        'duration_minutes' => 60,
    ])->assertSessionHasErrors('start_time');

    expect($gym->ptSessions()->count())->toBe(1);
});

test('completion is idempotent cancellation is free and no show follows gym setting', function () {
    $this->travelTo('2026-08-26 05:00:00');
    $gym = Gym::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'count_pt_no_show_as_used_session' => true,
    ]);
    $trainerUser = User::factory()->create();
    attachPersonalTrainingUser($gym, $trainerUser, GymRole::Trainer);
    $trainer = Trainer::factory()->for($gym)->create(['user_id' => $trainerUser->getKey()]);
    $member = Member::factory()->for($gym)->create();
    assignPersonalTrainingMember($gym, $trainer, $member);
    $package = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'total_sessions' => 3,
    ]);
    $completed = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $package->getKey(),
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
    ]);
    $cancelled = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $package->getKey(),
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'scheduled_at' => now()->addDays(2),
    ]);
    $noShow = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $package->getKey(),
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'scheduled_at' => now()->addDays(3),
    ]);

    $this->actingAs($trainerUser)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->patch(route('pt-sessions.complete', $completed))
        ->assertRedirect();
    expect($package->fresh()->used_sessions)->toBe(1);

    $this->patch(route('pt-sessions.complete', $completed))->assertSessionHasErrors('session');
    expect($package->fresh()->used_sessions)->toBe(1);

    $this->patch(route('pt-sessions.cancel', $cancelled), [
        'cancellation_reason' => 'Member berhalangan.',
    ])->assertRedirect();
    expect($package->fresh()->used_sessions)->toBe(1)
        ->and($cancelled->fresh()->status)->toBe(PtSessionStatus::Cancelled);

    $this->patch(route('pt-sessions.no-show', $noShow))->assertRedirect();
    expect($package->fresh()->used_sessions)->toBe(2)
        ->and($noShow->fresh()->quota_consumed)->toBeTrue();
});

test('expired packages reserved quota and cross trainer access are enforced', function () {
    $this->travelTo('2026-08-26 05:00:00');
    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $trainerUser = User::factory()->create();
    $otherTrainerUser = User::factory()->create();
    attachPersonalTrainingUser($gym, $trainerUser, GymRole::Trainer);
    attachPersonalTrainingUser($gym, $otherTrainerUser, GymRole::Trainer);
    $trainer = Trainer::factory()->for($gym)->create(['user_id' => $trainerUser->getKey()]);
    $otherTrainer = Trainer::factory()->for($gym)->create(['user_id' => $otherTrainerUser->getKey()]);
    $member = Member::factory()->for($gym)->create();
    assignPersonalTrainingMember($gym, $trainer, $member);
    $expiredPackage = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'start_date' => '2026-07-01',
        'expires_at' => '2026-08-25',
    ]);

    $this->actingAs($trainerUser)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('pt-sessions.store'), [
            'member_pt_package_id' => $expiredPackage->getKey(),
            'date' => '2026-08-28',
            'start_time' => '18:00',
            'duration_minutes' => 60,
        ])
        ->assertSessionHasErrors('member_pt_package_id');

    $activePackage = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'total_sessions' => 1,
        'used_sessions' => 0,
    ]);
    $session = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $activePackage->getKey(),
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
    ]);

    $this->post(route('pt-sessions.store'), [
        'member_pt_package_id' => $activePackage->getKey(),
        'date' => '2026-08-29',
        'start_time' => '18:00',
        'duration_minutes' => 60,
    ])->assertSessionHasErrors('member_pt_package_id');

    $this->actingAs($otherTrainerUser)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('pt-sessions.show', $session))
        ->assertForbidden();

    expect($otherTrainer->members()->count())->toBe(0)
        ->and(Payment::query()->where('type', PaymentType::PersonalTraining)->count())->toBe(0);
});
