<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Models\User;

test('a member cannot be double booked across trainers on create or reschedule', function () {
    $this->travelTo('2026-09-15 01:00:00');
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $member = Member::factory()->for($gym)->create();
    $trainers = Trainer::factory()->for($gym)->count(2)->create();
    $packages = $trainers->map(function (Trainer $trainer) use ($gym, $member) {
        TrainerMember::query()->create([
            'gym_id' => $gym->id, 'trainer_id' => $trainer->id, 'member_id' => $member->id,
            'assigned_at' => now(), 'is_active' => true,
        ]);

        return MemberPtPackage::factory()->for($gym)->create([
            'member_id' => $member->id, 'trainer_id' => $trainer->id,
        ]);
    });
    $this->actingAs($owner)->withSession(['current_gym_id' => $gym->id]);
    $payload = ['date' => '2026-09-16', 'start_time' => '10:00', 'duration_minutes' => 60];
    $this->post(route('pt-sessions.store'), [...$payload, 'member_pt_package_id' => $packages[0]->id])
        ->assertSessionHasNoErrors();
    $this->post(route('pt-sessions.store'), [...$payload, 'member_pt_package_id' => $packages[1]->id])
        ->assertSessionHasErrors('start_time');
    $this->post(route('pt-sessions.store'), [...$payload, 'start_time' => '11:00', 'member_pt_package_id' => $packages[1]->id])
        ->assertSessionHasNoErrors();
    $second = $gym->ptSessions()->latest('id')->firstOrFail();
    $this->patch(route('pt-sessions.reschedule', $second), [...$payload, 'start_time' => '10:30'])
        ->assertSessionHasErrors('start_time');
    expect($gym->ptSessions()->count())->toBe(2)
        ->and($second->fresh()->scheduled_at->format('H:i'))->toBe('04:00');
});

test('scheduling rejects inactive members and unpaid packages', function (bool $inactive) {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $member = Member::factory()->for($gym)->create(['status' => $inactive ? MemberStatus::Inactive : MemberStatus::Active]);
    $trainer = Trainer::factory()->for($gym)->create();
    TrainerMember::query()->create([
        'gym_id' => $gym->id, 'trainer_id' => $trainer->id, 'member_id' => $member->id,
        'assigned_at' => now(), 'is_active' => true,
    ]);
    $package = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->id, 'trainer_id' => $trainer->id,
        'payment_status' => $inactive ? PaymentStatus::Paid : PaymentStatus::Pending,
    ]);
    $this->actingAs($owner)->withSession(['current_gym_id' => $gym->id])
        ->post(route('pt-sessions.store'), [
            'member_pt_package_id' => $package->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '10:00', 'duration_minutes' => 60,
        ])->assertSessionHasErrors('member_pt_package_id');
    expect(PtSession::query()->count())->toBe(0);
})->with([true, false]);

test('owner can complete a tenant session exactly once', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $gym->users()->attach($owner, ['role' => GymRole::Owner, 'status' => GymUserStatus::Active]);
    $package = MemberPtPackage::factory()->for($gym)->create();
    $session = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $package->id, 'member_id' => $package->member_id,
        'trainer_id' => $package->trainer_id,
    ]);
    $this->actingAs($owner)->withSession(['current_gym_id' => $gym->id])
        ->patch(route('pt-sessions.complete', $session))->assertSessionHasNoErrors();
    $this->patch(route('pt-sessions.complete', $session))->assertSessionHasErrors('session');
    expect($package->fresh()->used_sessions)->toBe(1);
});
