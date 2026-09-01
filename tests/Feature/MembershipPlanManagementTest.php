<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MembershipDurationUnit;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function validMembershipPlanPayload(array $overrides = []): array
{
    return [
        'name' => 'Monthly Premium',
        'duration' => 1,
        'duration_unit' => MembershipDurationUnit::Month->value,
        'price' => '275000.50',
        'description' => 'Akses seluruh fasilitas gym.',
        'is_active' => true,
        ...$overrides,
    ];
}

function attachMembershipPlanUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('owner and front desk can manage membership plans while trainer cannot', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();

    attachMembershipPlanUser($gym, $owner, GymRole::Owner);
    attachMembershipPlanUser($gym, $admin, GymRole::Admin);
    attachMembershipPlanUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('membership-plans.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('membership-plans.create'))
        ->assertOk();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('membership-plans.index'))
        ->assertForbidden();
});

test('membership plans are created in the current gym with decimal price and audit log', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('membership-plans.store'), validMembershipPlanPayload([
            'name' => '  Monthly   Premium  ',
        ]))
        ->assertRedirect();

    $membershipPlan = $gym->membershipPlans()->firstOrFail();
    $activityLog = ActivityLog::query()
        ->where('event', 'membership_plan.created')
        ->firstOrFail();

    expect($membershipPlan->name)->toBe('Monthly Premium')
        ->and($membershipPlan->price)->toBe('275000.50')
        ->and($membershipPlan->duration_unit)->toBe(MembershipDurationUnit::Month)
        ->and($activityLog->gym_id)->toBe($gym->getKey())
        ->and($activityLog->properties['price'])->toBe('275000.50');
});

test('membership plan names are unique per gym and reusable by another gym', function () {
    $firstGym = Gym::factory()->create();
    $secondGym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($firstGym, $user, GymRole::Admin);
    attachMembershipPlanUser($secondGym, $user, GymRole::Admin);

    MembershipPlan::factory()->for($firstGym)->create(['name' => 'Monthly']);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $firstGym->getKey()])
        ->post(route('membership-plans.store'), validMembershipPlanPayload([
            'name' => 'Monthly',
        ]))
        ->assertSessionHasErrors('name');

    $this->withSession(['current_gym_id' => $secondGym->getKey()])
        ->post(route('membership-plans.store'), validMembershipPlanPayload([
            'name' => 'Monthly',
        ]))
        ->assertRedirect();

    expect($firstGym->membershipPlans()->count())->toBe(1)
        ->and($secondGym->membershipPlans()->where('name', 'Monthly')->count())->toBe(1);
});

test('membership plan list searches filters and paginates only inside the current gym', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($gym, $user, GymRole::Admin);

    MembershipPlan::factory()->for($gym)->create([
        'name' => 'Paket Pelajar',
        'description' => 'Latihan hemat untuk pelajar.',
        'is_active' => false,
    ]);
    MembershipPlan::factory()->for($gym)->count(11)->create(['is_active' => true]);
    MembershipPlan::factory()->for($foreignGym)->create([
        'name' => 'Paket Pelajar Tenant Lain',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('membership-plans.index', [
            'search' => 'Pelajar',
            'status' => 'inactive',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('membership-plans/index')
            ->where('membershipPlans.total', 1)
            ->has('membershipPlans.data', 1)
            ->where('membershipPlans.data.0.name', 'Paket Pelajar'));

    $this->get(route('membership-plans.index', ['per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('membershipPlans.total', 12)
            ->where('membershipPlans.per_page', 10)
            ->where('membershipPlans.last_page', 2)
            ->has('membershipPlans.data', 10));
});

test('cross tenant membership plan records consistently return not found', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($gym, $user, GymRole::Owner);
    $foreignPlan = MembershipPlan::factory()->for($foreignGym)->create();

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('membership-plans.show', $foreignPlan))
        ->assertNotFound();

    $this->get(route('membership-plans.edit', $foreignPlan))->assertNotFound();
    $this->patch(
        route('membership-plans.update', $foreignPlan),
        validMembershipPlanPayload(),
    )->assertNotFound();
    $this->patch(route('membership-plans.status.update', $foreignPlan), [
        'is_active' => false,
    ])->assertNotFound();
    $this->delete(route('membership-plans.destroy', $foreignPlan))->assertNotFound();

    $this->assertModelExists($foreignPlan);
});

test('membership plan update status change and deletion are audited', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($gym, $user, GymRole::Owner);
    $membershipPlan = MembershipPlan::factory()->for($gym)->create([
        'name' => 'Paket Lama',
        'duration' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->patch(route('membership-plans.update', $membershipPlan), validMembershipPlanPayload([
            'name' => 'Paket Baru',
            'duration' => 3,
        ]))
        ->assertRedirect(route('membership-plans.show', $membershipPlan));

    $this->patch(route('membership-plans.status.update', $membershipPlan), [
        'is_active' => false,
    ])->assertRedirect();

    $membershipPlan->refresh();
    expect($membershipPlan->name)->toBe('Paket Baru')
        ->and($membershipPlan->duration)->toBe(3)
        ->and($membershipPlan->is_active)->toBeFalse()
        ->and(ActivityLog::query()->where('event', 'membership_plan.updated')->firstOrFail()->properties['fields'])
        ->toContain('name', 'duration')
        ->and(ActivityLog::query()->where('event', 'membership_plan.status_changed')->firstOrFail()->properties)
        ->toMatchArray(['from' => true, 'to' => false]);

    $this->delete(route('membership-plans.destroy', $membershipPlan))
        ->assertRedirect(route('membership-plans.index'));

    $this->assertModelMissing($membershipPlan);
    expect(ActivityLog::query()->where('event', 'membership_plan.deleted')->count())->toBe(1);
});

test('membership plan validation rejects invalid operational data', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachMembershipPlanUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('membership-plans.store'), validMembershipPlanPayload([
            'name' => '',
            'duration' => 0,
            'duration_unit' => 'decade',
            'price' => '1.999',
            'description' => str_repeat('a', 2001),
            'is_active' => 'sometimes',
        ]))
        ->assertSessionHasErrors([
            'name',
            'duration',
            'duration_unit',
            'price',
            'description',
            'is_active',
        ]);

    expect($gym->membershipPlans()->count())->toBe(0);
});

test('development seeder creates idempotent membership plan examples', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymlo-demo')->firstOrFail();

    expect($gym->membershipPlans()->count())->toBe(5)
        ->and($gym->membershipPlans()->where('is_active', false)->count())->toBe(1)
        ->and($gym->membershipPlans()->where('name', 'Monthly')->value('price'))
        ->toBe('250000.00');
});

test('root remains publicly accessible', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
        );
});
