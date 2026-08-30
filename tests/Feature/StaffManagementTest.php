<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function attachStaffManagementUser(Gym $gym, User $user, GymRole $role, GymUserStatus $status = GymUserStatus::Active): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => $status->value,
    ]);
}

function createStaffManagementSubscription(Gym $gym, User $owner, int $maxStaff = 5): Subscription
{
    $plan = SaasPlan::factory()->create([
        'max_gyms' => 3,
        'max_staff' => $maxStaff,
    ]);
    $subscription = Subscription::factory()
        ->for($owner, 'subscriber')
        ->for($plan, 'plan')
        ->create();
    $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    return $subscription;
}

test('owner can create and update front desk for the active gym', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachStaffManagementUser($gym, $owner, GymRole::Owner);
    createStaffManagementSubscription($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('staff.store'), [
            'name' => '  Rina   Front Desk ',
            'email' => 'RINA@EXAMPLE.TEST',
            'password' => 'password',
            'password_confirmation' => 'password',
            'status' => GymUserStatus::Active->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('staff.index'));

    $frontDesk = User::query()->where('email', 'rina@example.test')->firstOrFail();
    $this->assertDatabaseHas('gym_user', [
        'gym_id' => $gym->getKey(),
        'user_id' => $frontDesk->getKey(),
        'role' => GymRole::Admin->value,
        'status' => GymUserStatus::Active->value,
    ]);

    $this->get(route('staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/index')
            ->where('staff.total', 1)
            ->where('staff.data.0.name', 'Rina Front Desk')
            ->where('staff.data.0.role', GymRole::Admin->value)
            ->where('quota.used', 1));

    $this->patch(route('staff.update', $frontDesk), [
        'name' => 'Rina Utama',
        'email' => 'rina.utama@example.test',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
        'status' => GymUserStatus::Inactive->value,
    ])->assertSessionHasNoErrors()->assertRedirect(route('staff.index'));

    expect($frontDesk->refresh()->name)->toBe('Rina Utama')
        ->and($frontDesk->email)->toBe('rina.utama@example.test')
        ->and(Hash::check('password-baru', $frontDesk->password))->toBeTrue()
        ->and(ActivityLog::query()->where('event', 'staff.created')->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'staff.updated')->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'staff.deactivated')->count())->toBe(1);
    $this->assertDatabaseHas('gym_user', [
        'gym_id' => $gym->getKey(),
        'user_id' => $frontDesk->getKey(),
        'status' => GymUserStatus::Inactive->value,
    ]);
});

test('staff page lists trainers but trainer accounts stay managed by trainer workflow', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $trainerUser = User::factory()->create(['name' => 'Trainer Akun']);
    attachStaffManagementUser($gym, $owner, GymRole::Owner);
    attachStaffManagementUser($gym, $trainerUser, GymRole::Trainer);
    createStaffManagementSubscription($gym, $owner);
    $trainer = Trainer::factory()->for($gym)->create([
        'user_id' => $trainerUser->getKey(),
        'trainer_code' => 'TRN-000001',
    ]);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('staff.index', ['role' => GymRole::Trainer->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('staff.total', 1)
            ->where('staff.data.0.id', $trainerUser->getKey())
            ->where('staff.data.0.trainer_id', $trainer->getKey())
            ->where('staff.data.0.trainer_code', 'TRN-000001'));

    $this->get(route('staff.edit', $trainerUser))->assertNotFound();
});

test('front desk management is owner only and cross tenant records return not found', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $owner = User::factory()->create();
    $frontDesk = User::factory()->create();
    $foreignFrontDesk = User::factory()->create();
    attachStaffManagementUser($gym, $owner, GymRole::Owner);
    attachStaffManagementUser($gym, $frontDesk, GymRole::Admin);
    attachStaffManagementUser($foreignGym, $foreignFrontDesk, GymRole::Admin);
    createStaffManagementSubscription($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('staff.edit', $foreignFrontDesk))
        ->assertNotFound();

    $this->actingAs($frontDesk)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('staff.index'))
        ->assertForbidden();
});

test('front desk creation enforces aggregate subscriber staff quota', function () {
    $firstGym = Gym::factory()->create();
    $secondGym = Gym::factory()->create();
    $owner = User::factory()->create();
    $existingStaff = User::factory()->create();
    attachStaffManagementUser($firstGym, $owner, GymRole::Owner);
    attachStaffManagementUser($secondGym, $owner, GymRole::Owner);
    attachStaffManagementUser($secondGym, $existingStaff, GymRole::Admin);
    $subscription = createStaffManagementSubscription($firstGym, $owner, 1);
    $secondGym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $firstGym->getKey()])
        ->post(route('staff.store'), [
            'name' => 'Staf Melebihi Kuota',
            'email' => 'over-limit@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'status' => GymUserStatus::Active->value,
        ])
        ->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'over-limit@example.test')->exists())->toBeFalse();
});
