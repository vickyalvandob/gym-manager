<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function validGymSettingsPayload(array $overrides = []): array
{
    return [
        'name' => 'Gym Flow Jakarta',
        'phone' => '+62 812 3456 7890',
        'email' => 'halo@gymflow.test',
        'address' => 'Jl. Kebugaran No. 10, Jakarta',
        'membership_expiry_warning_days' => 10,
        ...$overrides,
    ];
}

function attachGymSettingsUser(Gym $gym, User $user, GymRole $role): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

test('only owner can open and update gym settings', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $trainer = User::factory()->create();

    attachGymSettingsUser($gym, $owner, GymRole::Owner);
    attachGymSettingsUser($gym, $admin, GymRole::Admin);
    attachGymSettingsUser($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('gym-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('gym-settings/edit')
            ->where('gym.id', $gym->getKey())
            ->where('gym.name', $gym->name)
            ->where('gym.logo_url', null)
            ->missing('timezoneOptions')
            ->missing('currencyOptions'));

    $this->actingAs($admin)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('gym-settings.edit'))
        ->assertForbidden();

    $this->patch(route('gym-settings.update'), validGymSettingsPayload())
        ->assertForbidden();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->get(route('gym-settings.edit'))
        ->assertForbidden();
});

test('owner updates editable settings while regional defaults stay fixed', function () {
    $gym = Gym::factory()->create(['name' => 'Gym Lama']);
    $foreignGym = Gym::factory()->create(['name' => 'Gym Tenant Lain']);
    $owner = User::factory()->create();
    attachGymSettingsUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->patch(route('gym-settings.update'), validGymSettingsPayload([
            'gym_id' => $foreignGym->getKey(),
            'name' => '  Gym   Flow   Surabaya  ',
            'phone' => '  +62 811 2222 3333  ',
            'email' => '  ADMIN@GYMFLOW.TEST ',
            'address' => "  Jl. Sehat No. 9\nSurabaya  ",
            'timezone' => 'Asia/Makassar',
            'currency' => 'USD',
            'membership_expiry_warning_days' => 14,
        ]))
        ->assertRedirect(route('gym-settings.edit'));

    $gym->refresh();
    $activityLog = ActivityLog::query()
        ->where('event', 'gym.settings_updated')
        ->firstOrFail();

    expect($gym->name)->toBe('Gym Flow Surabaya')
        ->and($gym->phone)->toBe('+62 811 2222 3333')
        ->and($gym->email)->toBe('admin@gymflow.test')
        ->and($gym->address)->toBe("Jl. Sehat No. 9\nSurabaya")
        ->and($gym->timezone)->toBe('Asia/Jakarta')
        ->and($gym->currency)->toBe('IDR')
        ->and($gym->membership_expiry_warning_days)->toBe(14)
        ->and($foreignGym->fresh()->name)->toBe('Gym Tenant Lain')
        ->and($activityLog->gym_id)->toBe($gym->getKey())
        ->and($activityLog->properties['fields'])->toContain(
            'name',
            'phone',
            'email',
            'address',
            'membership_expiry_warning_days',
        )
        ->and($activityLog->properties['fields'])->not->toContain(
            'timezone',
            'currency',
        );

    $this->get(route('gym-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.currentGym.name', 'Gym Flow Surabaya')
            ->where('auth.currentGym.timezone', 'Asia/Jakarta')
            ->where('auth.currentGym.currency', 'IDR')
            ->where('auth.currentGym.membership_expiry_warning_days', 14)
            ->where('gym.email', 'admin@gymflow.test'));
});

test('gym settings reject invalid editable profile warning and logo data', function () {
    Storage::fake('local');

    $gym = Gym::factory()->create(['name' => 'Gym Tetap']);
    $owner = User::factory()->create();
    attachGymSettingsUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('gym-settings.update'), validGymSettingsPayload([
            '_method' => 'PATCH',
            'name' => '',
            'phone' => '123',
            'email' => 'bukan-email',
            'address' => str_repeat('a', 1001),
            'timezone' => 'Mars/Olympus',
            'currency' => 'BTC',
            'membership_expiry_warning_days' => 0,
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]))
        ->assertSessionHasErrors([
            'name',
            'phone',
            'email',
            'address',
            'membership_expiry_warning_days',
            'logo',
        ])
        ->assertSessionDoesntHaveErrors(['timezone', 'currency']);

    expect($gym->fresh()->name)->toBe('Gym Tetap')
        ->and(ActivityLog::query()->where('event', 'gym.settings_updated')->count())->toBe(0);
});

test('owner can upload replace serve and remove the active gym logo', function () {
    Storage::fake('local');

    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    attachGymSettingsUser($gym, $owner, GymRole::Owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('gym-settings.update'), validGymSettingsPayload([
            '_method' => 'PATCH',
            'logo' => UploadedFile::fake()->image('logo-lama.png', 600, 300)->size(500),
        ]))
        ->assertRedirect(route('gym-settings.edit'));

    $firstLogoPath = $gym->fresh()->logo;
    expect($firstLogoPath)->toBeString();
    Storage::disk('local')->assertExists($firstLogoPath);

    $this->get(route('gym-logo.show'))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=300, private');

    $this->post(route('gym-settings.update'), validGymSettingsPayload([
        '_method' => 'PATCH',
        'logo' => UploadedFile::fake()->image('logo-baru.webp', 800, 400)->size(600),
    ]))->assertRedirect(route('gym-settings.edit'));

    $secondLogoPath = $gym->fresh()->logo;
    expect($secondLogoPath)->toBeString()->not->toBe($firstLogoPath);
    Storage::disk('local')->assertMissing($firstLogoPath);
    Storage::disk('local')->assertExists($secondLogoPath);

    $this->delete(route('gym-settings.logo.destroy'))
        ->assertRedirect(route('gym-settings.edit'));

    expect($gym->fresh()->logo)->toBeNull()
        ->and(ActivityLog::query()->where('event', 'gym.settings_updated')->count())->toBe(2)
        ->and(ActivityLog::query()->where('event', 'gym.logo_removed')->count())->toBe(1);
    Storage::disk('local')->assertMissing($secondLogoPath);

    $this->get(route('gym-logo.show'))->assertNotFound();
});

test('gym logo endpoint follows the active tenant instead of a submitted gym identifier', function () {
    Storage::fake('local');

    $firstGym = Gym::factory()->create(['logo' => 'gym-logos/first/logo.png']);
    $secondGym = Gym::factory()->create(['logo' => 'gym-logos/second/logo.png']);
    $user = User::factory()->create();
    attachGymSettingsUser($firstGym, $user, GymRole::Owner);
    attachGymSettingsUser($secondGym, $user, GymRole::Owner);
    Storage::disk('local')->put($firstGym->logo, 'first-logo');
    Storage::disk('local')->put($secondGym->logo, 'second-logo');

    $this->actingAs($user)
        ->withSession(['current_gym_id' => $firstGym->getKey()])
        ->get(route('gym-logo.show', ['gym_id' => $secondGym->getKey()]))
        ->assertOk()
        ->assertStreamedContent('first-logo');

    $this->withSession(['current_gym_id' => $secondGym->getKey()])
        ->get(route('gym-logo.show'))
        ->assertOk()
        ->assertStreamedContent('second-logo');
});
