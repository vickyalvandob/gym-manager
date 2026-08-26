<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('current gym is resolved only from active user memberships', function () {
    $user = User::factory()->create();
    $ownedGym = Gym::factory()->create(['name' => 'Gym Milik User']);
    $foreignGym = Gym::factory()->create(['name' => 'Gym Tenant Lain']);

    $user->gyms()->attach($ownedGym, [
        'role' => GymRole::Admin->value,
        'status' => GymUserStatus::Active->value,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_gym_id' => $foreignGym->getKey()])
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSessionHas('current_gym_id', $ownedGym->getKey())
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('auth.currentGym.id', $ownedGym->getKey())
            ->where('auth.currentGym.name', 'Gym Milik User')
            ->where('auth.role', GymRole::Admin->value)
            ->where('auth.roleLabel', 'Front Desk')
            ->where('auth.permissions.manage_gym', false)
            ->where('auth.permissions.operate_front_desk', true));
});

test('gym policy prevents cross tenant access and enforces roles', function () {
    $gym = Gym::factory()->create();
    $otherGym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $gym->users()->attach($owner, [
        'role' => GymRole::Owner->value,
        'status' => GymUserStatus::Active->value,
    ]);
    $gym->users()->attach($admin, [
        'role' => GymRole::Admin->value,
        'status' => GymUserStatus::Active->value,
    ]);

    expect($owner->can('view', $gym))->toBeTrue()
        ->and($owner->can('update', $gym))->toBeTrue()
        ->and($admin->can('view', $gym))->toBeTrue()
        ->and($admin->can('update', $gym))->toBeFalse()
        ->and($admin->can('operateFrontDesk', $gym))->toBeTrue()
        ->and($owner->can('view', $otherGym))->toBeFalse();
});

test('development seeder creates the role based demo accounts', function () {
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();
    $roles = $gym->users()
        ->orderBy('gym_user.role')
        ->pluck('gym_user.role', 'users.email');

    expect($roles)->toHaveCount(5)
        ->and($roles->get('owner@gym.test'))->toBe(GymRole::Owner->value)
        ->and($roles->get('frontdesk@gym.test'))->toBe(GymRole::Admin->value)
        ->and($roles->get('andi@gym.test'))->toBe(GymRole::Trainer->value)
        ->and($roles->get('budi@gym.test'))->toBe(GymRole::Trainer->value)
        ->and($roles->get('rina@gym.test'))->toBe(GymRole::Trainer->value);
});
