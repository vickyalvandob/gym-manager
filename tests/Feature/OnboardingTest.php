<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gym = Gym::factory()->create(['onboarding_completed_at' => null]);
    $this->owner = User::factory()->create();
    $this->gym->users()->attach($this->owner, [
        'role' => GymRole::Owner,
        'status' => GymUserStatus::Active,
    ]);
    $this->actingAs($this->owner)->withSession(['current_gym_id' => $this->gym->getKey()]);
});

test('onboarding prefills the current gym without fetching plan data or exposing currency controls', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->get(route('onboarding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/edit')
            ->where('gym.id', $this->gym->getKey())
            ->where('gym.name', $this->gym->name)
            ->where('gym.timezone', 'Asia/Jakarta')
            ->has('timezoneOptions', 3)
            ->missing('currencyOptions')
            ->missing('gym.currency')
            ->missing('subscription'));

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->filter(fn (string $query): bool => str_contains($query, 'saas_plans')))->toBeEmpty();
});

test('owner can finish with only the required profile fields', function (string $timezone) {
    $this->put(route('onboarding.update'), [
        'name' => '  Gym   Baru  ',
        'timezone' => $timezone,
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

    $this->gym->refresh();

    expect($this->gym->name)->toBe('Gym Baru')
        ->and($this->gym->timezone)->toBe($timezone)
        ->and($this->gym->currency)->toBe('IDR')
        ->and($this->gym->phone)->toBeNull()
        ->and($this->gym->email)->toBeNull()
        ->and($this->gym->address)->toBeNull()
        ->and($this->gym->onboarding_completed_at)->not->toBeNull();

    $this->get(route('onboarding.edit'))->assertRedirect(route('dashboard'));
})->with(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura']);

test('onboarding fixes currency to rupiah and ignores foreign gym and protected fields', function () {
    $otherGym = Gym::factory()->create(['onboarding_completed_at' => null]);
    $originalOtherGym = $otherGym->fresh()->getAttributes();
    $originalSlug = $this->gym->slug;
    $this->gym->update(['currency' => 'USD']);

    $this->put(route('onboarding.update'), [
        'gym_id' => $otherGym->getKey(),
        'name' => 'Gym Owner',
        'timezone' => 'Asia/Jakarta',
        'currency' => 'USD',
        'slug' => 'tampered',
        'subscription_id' => $otherGym->subscription_id,
        'phone' => ' 08123456789 ',
        'email' => ' HALO@GYM.TEST ',
        'address' => ' Jakarta Selatan ',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

    $this->gym->refresh();
    $audit = ActivityLog::query()->where('event', 'gym.onboarding_completed')->sole();

    expect($this->gym->currency)->toBe('IDR')
        ->and($this->gym->slug)->toBe($originalSlug)
        ->and($this->gym->subscription_id)->not->toBe($otherGym->subscription_id)
        ->and($this->gym->phone)->toBe('08123456789')
        ->and($this->gym->email)->toBe('halo@gym.test')
        ->and($this->gym->address)->toBe('Jakarta Selatan')
        ->and($audit->properties['currency'])->toBe('IDR')
        ->and($audit->gym_id)->toBe($this->gym->getKey())
        ->and($otherGym->fresh()->getAttributes())->toBe($originalOtherGym);
});

test('repeated completion keeps the first profile timestamp and a single audit event', function () {
    $payload = ['name' => 'Gym Pertama', 'timezone' => 'Asia/Jakarta'];
    $this->put(route('onboarding.update'), $payload)->assertRedirect(route('dashboard'));
    $completedAt = $this->gym->fresh()->onboarding_completed_at;

    $this->travel(10)->minutes();
    $this->put(route('onboarding.update'), [
        ...$payload,
        'name' => 'Gym Ditimpa',
        'timezone' => 'Asia/Jayapura',
        'email' => 'changed@gym.test',
    ])->assertRedirect(route('dashboard'));

    $this->gym->refresh();

    expect($this->gym->name)->toBe('Gym Pertama')
        ->and($this->gym->timezone)->toBe('Asia/Jakarta')
        ->and($this->gym->email)->toBeNull()
        ->and($this->gym->onboarding_completed_at->equalTo($completedAt))->toBeTrue()
        ->and(ActivityLog::query()->where('event', 'gym.onboarding_completed')->count())->toBe(1);
});

test('invalid onboarding leaves the gym incomplete with actionable validation errors', function (array $changes, string $field, string $message) {
    $this->from(route('onboarding.edit'))->put(route('onboarding.update'), [
        'name' => 'Gym Baru',
        'timezone' => 'Asia/Jakarta',
        ...$changes,
    ])->assertRedirect(route('onboarding.edit'))->assertSessionHasErrors([$field => $message]);

    expect($this->gym->fresh()->onboarding_completed_at)->toBeNull()
        ->and(ActivityLog::query()->where('event', 'gym.onboarding_completed')->exists())->toBeFalse();
})->with([
    'blank name' => [['name' => '   '], 'name', 'Masukkan nama gym Anda.'],
    'invalid timezone' => [['timezone' => 'not-a-timezone'], 'timezone', 'Pilih zona waktu yang valid.'],
    'invalid email' => [['email' => 'not-an-email'], 'email', 'Masukkan alamat email yang valid.'],
    'long phone' => [['phone' => str_repeat('1', 31)], 'phone', 'Nomor telepon maksimal 30 karakter.'],
    'long address' => [['address' => str_repeat('a', 1001)], 'address', 'Alamat maksimal 1.000 karakter.'],
]);

test('non owners cannot view or complete onboarding', function (GymRole $role) {
    $staff = User::factory()->create();
    $this->gym->users()->attach($staff, ['role' => $role, 'status' => GymUserStatus::Active]);

    $this->actingAs($staff)->get(route('onboarding.edit'))->assertForbidden();
    $this->put(route('onboarding.update'), [
        'name' => 'Tidak Diizinkan',
        'timezone' => 'Asia/Jakarta',
    ])->assertForbidden();

    expect($this->gym->fresh()->onboarding_completed_at)->toBeNull();
})->with([GymRole::Admin, GymRole::Trainer]);

test('failed audit rolls back profile changes and onboarding completion', function () {
    $originalName = $this->gym->name;
    $this->mock(ActivityLogger::class)
        ->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('Audit unavailable'));

    $this->put(route('onboarding.update'), [
        'name' => 'Gym Baru',
        'timezone' => 'Asia/Jayapura',
    ])->assertServerError();

    $this->gym->refresh();

    expect($this->gym->name)->toBe($originalName)
        ->and($this->gym->timezone)->toBe('Asia/Jakarta')
        ->and($this->gym->onboarding_completed_at)->toBeNull();
});
