<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\PtPackage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachPtPackageUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('owner manages tenant scoped PT packages while front desk has read only access', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $frontDesk = User::factory()->create();
    attachPtPackageUser($gym, $owner, GymRole::Owner);
    attachPtPackageUser($gym, $frontDesk, GymRole::Admin);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('pt-packages.store'), [
            'name' => 'PT Regular',
            'session_count' => 8,
            'validity_days' => 60,
            'price' => '900000',
            'description' => 'Delapan sesi personal training.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $ptPackage = $gym->ptPackages()->firstOrFail();
    expect($ptPackage->price)->toBe('900000.00')
        ->and($ptPackage->session_count)->toBe(8)
        ->and(ActivityLog::query()->where('event', 'pt_package.created')->count())->toBe(1);

    $this->patch(route('pt-packages.update', $ptPackage), [
        'name' => 'PT Regular Plus',
        'session_count' => 10,
        'validity_days' => 75,
        'price' => '1100000',
        'description' => null,
        'is_active' => false,
    ])->assertRedirect(route('pt-packages.show', $ptPackage));

    expect($ptPackage->fresh()->name)->toBe('PT Regular Plus')
        ->and($ptPackage->fresh()->is_active)->toBeFalse()
        ->and(ActivityLog::query()->where('event', 'pt_package.updated')->count())->toBe(1);

    $this->actingAs($frontDesk)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('pt-packages.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('pt-packages/index')
            ->where('ptPackages.total', 1)
            ->where('canCreate', false));

    $this->get(route('pt-packages.create'))->assertForbidden();
});

test('PT package names and records are isolated per gym', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachPtPackageUser($gym, $owner, GymRole::Owner);
    PtPackage::factory()->for($foreignGym)->create(['name' => 'PT Regular']);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('pt-packages.store'), [
            'name' => 'PT Regular',
            'session_count' => 8,
            'validity_days' => 60,
            'price' => '900000',
            'is_active' => true,
        ])
        ->assertRedirect();

    $foreignPackage = $foreignGym->ptPackages()->firstOrFail();
    $this->get(route('pt-packages.show', $foreignPackage))->assertNotFound();

    expect($gym->ptPackages()->where('name', 'PT Regular')->count())->toBe(1);
});
