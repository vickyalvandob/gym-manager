<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'gym_name' => 'Gym Test Jakarta',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $gym = $user->gyms()->firstOrFail();
    $activityLog = ActivityLog::query()->whereBelongsTo($gym)->firstOrFail();

    expect($gym->name)->toBe('Gym Test Jakarta')
        ->and($gym->getRelation('pivot')->getAttribute('role'))->toBe(GymRole::Owner->value)
        ->and($gym->getRelation('pivot')->getAttribute('status'))->toBe(GymUserStatus::Active->value)
        ->and($activityLog->event)->toBe('user.created')
        ->and($activityLog->subject_type)->toBe(User::class)
        ->and($activityLog->subject_id)->toBe($user->getKey());
});

test('gym name is required and registration does not create partial data', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('gym_name');
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
