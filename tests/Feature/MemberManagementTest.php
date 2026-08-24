<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DemoGymSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function validMemberPayload(array $overrides = []): array
{
    return [
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'email' => 'budi@example.test',
        'gender' => 'male',
        'birth_date' => '1995-06-15',
        'address' => 'Jakarta Selatan',
        'emergency_contact' => 'Siti - 081298765432',
        'status' => MemberStatus::Active->value,
        'notes' => 'Member pagi.',
        ...$overrides,
    ];
}

function attachGymUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('owner and front desk can manage members while trainer cannot', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();

    attachGymUser($gym, $owner, GymRole::Owner);
    attachGymUser($gym, $admin, GymRole::Admin);
    attachGymUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.create'))
        ->assertOk();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.index'))
        ->assertForbidden();

    expect(Route::has('members.destroy'))->toBeFalse();
});

test('member numbers are sequential and independent for every gym', function () {
    $user = User::factory()->create();
    $firstGym = Gym::factory()->create();
    $secondGym = Gym::factory()->create();
    attachGymUser($firstGym, $user, GymRole::Owner);
    attachGymUser($secondGym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $firstGym->getKey()])
        ->post(route('members.store'), validMemberPayload())
        ->assertRedirect();

    $this->withSession(['current_gym_id' => $firstGym->getKey()])
        ->post(route('members.store'), validMemberPayload([
            'name' => 'Citra Lestari',
            'phone' => '081234567891',
        ]))
        ->assertRedirect();

    $this->withSession(['current_gym_id' => $secondGym->getKey()])
        ->post(route('members.store'), validMemberPayload([
            'name' => 'Dedi Pratama',
            'phone' => '081234567892',
        ]))
        ->assertRedirect();

    expect($firstGym->members()->orderBy('id')->pluck('member_number')->all())
        ->toBe(['MBR-000001', 'MBR-000002'])
        ->and($secondGym->members()->pluck('member_number')->all())
        ->toBe(['MBR-000001'])
        ->and($firstGym->fresh()->next_member_sequence)->toBe(3)
        ->and($secondGym->fresh()->next_member_sequence)->toBe(2);

    expect(ActivityLog::query()->where('event', 'member.created')->count())->toBe(3);
});

test('member list searches filters and paginates only inside the current gym', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    attachGymUser($gym, $user, GymRole::Admin);

    Member::factory()->for($gym)->create([
        'member_number' => 'MBR-000001',
        'name' => 'Siti Rahma',
        'phone' => '081211111111',
        'status' => MemberStatus::Inactive,
    ]);
    Member::factory()->for($gym)->count(11)->sequence(
        fn ($sequence) => [
            'member_number' => sprintf('MBR-%06d', $sequence->index + 2),
            'status' => MemberStatus::Active,
        ],
    )->create();
    Member::factory()->for($foreignGym)->create([
        'member_number' => 'MBR-000001',
        'name' => 'Siti Tenant Lain',
        'phone' => '081211111111',
        'status' => MemberStatus::Inactive,
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.index', [
            'search' => 'Siti',
            'status' => MemberStatus::Inactive->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->where('members.total', 1)
            ->has('members.data', 1)
            ->where('members.data.0.name', 'Siti Rahma'));

    $this->get(route('members.index', ['per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.total', 12)
            ->where('members.per_page', 10)
            ->where('members.last_page', 2)
            ->has('members.data', 10));
});

test('cross tenant member records consistently return not found', function () {
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $user = User::factory()->create();
    attachGymUser($gym, $user, GymRole::Owner);
    $foreignMember = Member::factory()->for($foreignGym)->create();

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('members.show', $foreignMember))
        ->assertNotFound();

    $this->get(route('members.edit', $foreignMember))->assertNotFound();
    $this->patch(route('members.update', $foreignMember), validMemberPayload())
        ->assertNotFound();
    $this->patch(route('members.status.update', $foreignMember), [
        'status' => MemberStatus::Inactive->value,
    ])->assertNotFound();
    $this->get(route('members.photo', $foreignMember))->assertNotFound();
});

test('member profile update and status change are audited without deleting data', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachGymUser($gym, $user, GymRole::Owner);
    $member = Member::factory()->for($gym)->create([
        'member_number' => 'MBR-000001',
        'name' => 'Nama Lama',
        'status' => MemberStatus::Active,
    ]);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->patch(route('members.update', $member), validMemberPayload([
            'name' => 'Nama Baru',
        ]))
        ->assertRedirect(route('members.show', $member));

    $this->patch(route('members.status.update', $member), [
        'status' => MemberStatus::Inactive->value,
    ])->assertRedirect();

    $member->refresh();
    $updateLog = ActivityLog::query()->where('event', 'member.updated')->firstOrFail();
    $statusLog = ActivityLog::query()->where('event', 'member.status_changed')->firstOrFail();

    expect($member->name)->toBe('Nama Baru')
        ->and($member->status)->toBe(MemberStatus::Inactive)
        ->and($updateLog->properties['fields'])->toContain('name')
        ->and($statusLog->properties)->toMatchArray([
            'from' => MemberStatus::Active->value,
            'to' => MemberStatus::Inactive->value,
        ]);

    $this->assertModelExists($member);
});

test('member photos are validated stored privately replaced and authorization protected', function () {
    Storage::fake('local');

    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachGymUser($gym, $user, GymRole::Admin);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.store'), validMemberPayload([
            'photo' => UploadedFile::fake()->image('member-lama.jpg', 600, 600),
        ]))
        ->assertRedirect();

    $member = $gym->members()->firstOrFail();
    $oldPhoto = $member->photo;
    expect($oldPhoto)->toBeString();
    Storage::disk('local')->assertExists($oldPhoto);

    $this->get(route('members.photo', $member))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=300, private');

    $this->post(route('members.update', $member), [
        ...validMemberPayload(['name' => 'Foto Baru']),
        '_method' => 'PATCH',
        'photo' => UploadedFile::fake()->image('member-baru.png', 800, 800),
    ])->assertRedirect(route('members.show', $member));

    $member->refresh();
    expect($member->photo)->not->toBe($oldPhoto);
    Storage::disk('local')->assertMissing($oldPhoto);
    Storage::disk('local')->assertExists($member->photo);

    $this->post(route('members.store'), validMemberPayload([
        'phone' => '081234567899',
        'photo' => UploadedFile::fake()->createWithContent('bukan-foto.jpg', 'not-an-image'),
    ]))->assertSessionHasErrors('photo');
});

test('member validation rejects invalid operational data', function () {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    attachGymUser($gym, $user, GymRole::Owner);

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.store'), validMemberPayload([
            'name' => '',
            'phone' => 'abc',
            'email' => 'invalid',
            'birth_date' => now()->addDay()->toDateString(),
            'status' => 'deleted',
        ]))
        ->assertSessionHasErrors(['name', 'phone', 'email', 'birth_date', 'status']);

    expect($gym->members()->count())->toBe(0);
});

test('development seeder creates idempotent member examples and advances the sequence', function () {
    $this->seed(DemoGymSeeder::class);
    $this->seed(DemoGymSeeder::class);

    $gym = Gym::query()->where('slug', 'gymflow-demo')->firstOrFail();

    expect($gym->members()->count())->toBe(8)
        ->and($gym->members()->where('status', MemberStatus::Inactive->value)->count())->toBe(2)
        ->and($gym->fresh()->next_member_sequence)->toBe(9);
});
