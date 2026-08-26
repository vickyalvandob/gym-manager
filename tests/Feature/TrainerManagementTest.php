<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Enums\TrainerStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function attachTrainerRoleUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

/** @return array<string, mixed> */
function validTrainerPayload(array $overrides = []): array
{
    return [
        'name' => 'Raka Pratama',
        'phone' => '081288880001',
        'email' => 'raka@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'specialization' => 'Strength & Conditioning',
        'status' => TrainerStatus::Active->value,
        'notes' => 'Trainer untuk program kekuatan dasar.',
        ...$overrides,
    ];
}

test('owner can manage trainer profiles with tenant scoped audit logs', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachTrainerRoleUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('trainers.store'), validTrainerPayload([
            'account_mode' => 'none',
            'user_id' => $owner->getKey(),
        ]))
        ->assertRedirect();

    $trainer = $gym->trainers()->firstOrFail();
    $trainerAccount = User::query()->where('email', 'raka@example.test')->firstOrFail();
    expect($trainer->name)->toBe('Raka Pratama')
        ->and($trainer->user_id)->toBe($trainerAccount->getKey());

    $this->get(route('trainers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('trainers/index')
            ->where('trainers.total', 1)
            ->where('trainers.data.0.name', 'Raka Pratama')
            ->where('canCreate', true));

    $this->patch(route('trainers.update', $trainer), validTrainerPayload([
        'name' => 'Raka Utama',
        'email' => 'raka.utama@example.test',
        'status' => TrainerStatus::Inactive->value,
    ]))->assertRedirect(route('trainers.show', $trainer));

    expect($trainer->fresh()->name)->toBe('Raka Utama')
        ->and($trainer->fresh()->status)->toBe(TrainerStatus::Inactive)
        ->and($trainerAccount->fresh()->name)->toBe('Raka Utama')
        ->and($trainerAccount->fresh()->email)->toBe('raka.utama@example.test');

    $this->assertModelExists($trainer);
    $this->assertModelExists($trainerAccount);
    $this->assertDatabaseHas('gym_user', [
        'gym_id' => $gym->getKey(),
        'user_id' => $trainerAccount->getKey(),
        'role' => GymRole::Trainer->value,
        'status' => GymUserStatus::Inactive->value,
    ]);
    expect(ActivityLog::query()->where('event', 'trainer.created')->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'trainer.updated')->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'trainer.deactivated')->count())->toBe(1);
});

test('front desk can view trainers and manage assignments but cannot change profiles', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $admin = User::factory()->create();
    attachTrainerRoleUser($gym, $admin, GymRole::Admin);
    $trainer = Trainer::factory()->for($gym)->create();
    $member = Member::factory()->for($gym)->create(['status' => MemberStatus::Active]);
    $foreignMember = Member::factory()->for($foreignGym)->create(['status' => MemberStatus::Active]);

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('trainers.show', $trainer))
        ->assertOk();

    $this->get(route('trainers.create'))->assertForbidden();
    $this->get(route('trainers.edit', $trainer))->assertForbidden();

    $this->post(route('trainers.members.store', $trainer), [
        'member_id' => $member->getKey(),
    ])->assertRedirect();

    expect($trainer->members()->whereKey($member->getKey())->exists())->toBeTrue();
    $this->assertDatabaseHas('trainer_members', [
        'gym_id' => $gym->getKey(),
        'trainer_id' => $trainer->getKey(),
        'member_id' => $member->getKey(),
        'assigned_by' => $admin->getKey(),
        'is_active' => true,
    ]);

    $this->post(route('trainers.members.store', $trainer), [
        'member_id' => $foreignMember->getKey(),
    ])->assertSessionHasErrors('member_id');

    $this->delete(route('trainers.members.destroy', [$trainer, $member]))
        ->assertRedirect();

    expect($trainer->members()->whereKey($member->getKey())->exists())->toBeFalse();
    $this->assertDatabaseHas('trainer_members', [
        'gym_id' => $gym->getKey(),
        'member_id' => $member->getKey(),
        'is_active' => false,
    ]);

    expect(ActivityLog::query()->where('event', 'trainer_member.assigned')->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'trainer_member.changed')->count())->toBe(1);
});

test('trainer dashboard exposes only the linked trainers assigned members', function () {
    $this->travelTo('2026-08-25 05:00:00');

    $gym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $foreignGym = Gym::factory()->create(['timezone' => 'Asia/Jakarta']);
    $trainerUser = User::factory()->create();
    attachTrainerRoleUser($gym, $trainerUser, GymRole::Trainer);
    $trainer = Trainer::factory()->for($gym)->create([
        'user_id' => $trainerUser->getKey(),
        'name' => 'Trainer Login',
    ]);
    $otherTrainer = Trainer::factory()->for($gym)->create();
    $foreignTrainer = Trainer::factory()->for($foreignGym)->create();
    $assignedMember = Member::factory()->for($gym)->create(['name' => 'Member Ditangani']);
    $unassignedMember = Member::factory()->for($gym)->create(['name' => 'Member Bukan Assignment']);
    $foreignMember = Member::factory()->for($foreignGym)->create(['name' => 'Member Gym Lain']);
    $plan = MembershipPlan::factory()->for($gym)->create();
    MemberMembership::factory()
        ->for($gym)
        ->for($assignedMember)
        ->for($plan, 'membershipPlan')
        ->create([
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
        ]);
    $trainer->members()->attach($assignedMember, [
        'gym_id' => $gym->getKey(),
        'assigned_by' => null,
    ]);
    $foreignTrainer->members()->attach($foreignMember, [
        'gym_id' => $foreignGym->getKey(),
        'assigned_by' => null,
    ]);

    $this->actingAs($trainerUser)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('snapshot')
            ->loadDeferredProps('dashboard', fn (Assert $reload) => $reload
                ->where('snapshot.metrics.revenue_today', null)
                ->where('snapshot.trainer_workspace.trainer.id', $trainer->getKey())
                ->where('snapshot.trainer_workspace.assigned_members_count', 1)
                ->where('snapshot.trainer_workspace.active_members_count', 1)
                ->has('snapshot.trainer_workspace.assigned_members', 1)
                ->where('snapshot.trainer_workspace.assigned_members.0.name', 'Member Ditangani')));

    $this->get(route('trainers.show', $trainer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('assignedMembers.total', 1)
            ->where('assignedMembers.data.0.name', 'Member Ditangani')
            ->where('canAssign', false));

    $this->get(route('trainers.index'))->assertForbidden();
    $this->get(route('trainers.show', $otherTrainer))->assertForbidden();
    $this->get(route('trainers.show', $foreignTrainer))->assertNotFound();

    $trainer->update(['status' => TrainerStatus::Inactive]);
    $this->get(route('trainers.show', $trainer))->assertForbidden();
    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('dashboard', fn (Assert $reload) => $reload
                ->where('snapshot.trainer_workspace.trainer', null)
                ->where('snapshot.trainer_workspace.assigned_members_count', 0)));

    expect($unassignedMember->name)->toBe('Member Bukan Assignment');
});

test('trainer validation rejects duplicate profile and login emails', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    User::factory()->create(['email' => 'login-used@example.test']);
    attachTrainerRoleUser($gym, $owner, GymRole::Owner);
    Trainer::factory()->for($gym)->create(['email' => 'used@example.test']);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('trainers.store'), validTrainerPayload([
            'email' => 'USED@example.test',
        ]))
        ->assertSessionHasErrors('email');

    $this->post(route('trainers.store'), validTrainerPayload([
        'email' => 'LOGIN-USED@example.test',
    ]))->assertSessionHasErrors('email');

    expect($gym->trainers()->count())->toBe(1);
});

test('owner always creates a trainer with a login account', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachTrainerRoleUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('trainers.store'), validTrainerPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $trainerWithAccount = $gym->trainers()->firstOrFail();
    $loginAccount = User::query()->where('email', 'raka@example.test')->firstOrFail();

    expect($trainerWithAccount->user_id)->toBe($loginAccount->getKey())
        ->and($trainerWithAccount->user_id)->not->toBe($owner->getKey())
        ->and($gym->users()->whereKey($loginAccount->getKey())->exists())->toBeTrue();

    $this->post(route('trainers.store'), validTrainerPayload([
        'name' => 'Dimas Trainer',
        'phone' => '081288880002',
        'email' => null,
        'password' => null,
        'password_confirmation' => null,
    ]))->assertSessionHasErrors(['email', 'password']);
});

test('cross tenant trainer records consistently return not found', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachTrainerRoleUser($gym, $owner, GymRole::Owner);
    $foreignTrainer = Trainer::factory()->for($foreignGym)->create();

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('trainers.show', $foreignTrainer))
        ->assertNotFound();

    $this->get(route('trainers.edit', $foreignTrainer))->assertNotFound();
    $this->patch(route('trainers.update', $foreignTrainer), validTrainerPayload())
        ->assertNotFound();
});

test('development seeder creates idempotent trainers and assignments', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();
    $trainerAccount = User::query()->where('email', 'andi@gym.test')->firstOrFail();
    $linkedTrainer = $gym->trainers()->where('user_id', $trainerAccount->getKey())->firstOrFail();

    expect($gym->trainers()->count())->toBe(3)
        ->and($linkedTrainer->members()->count())->toBe(2)
        ->and($gym->trainers()->withCount('members')->get()->sum('members_count'))->toBe(4)
        ->and($gym->ptPackages()->count())->toBe(3)
        ->and($gym->memberPtPackages()->count())->toBe(3)
        ->and($gym->ptSessions()->count())->toBe(6);
});
