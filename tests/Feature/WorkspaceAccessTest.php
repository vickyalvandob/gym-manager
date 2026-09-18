<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Trainer;
use App\Models\User;

test('workspace routes enforce the authenticated gym role', function (GymRole $role, string $path, bool $allowed) {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $gym->users()->attach($user, ['role' => $role, 'status' => GymUserStatus::Active]);
    if ($role === GymRole::Trainer) {
        Trainer::factory()->for($gym)->create(['user_id' => $user->id]);
    }
    $response = $this->actingAs($user)->withSession(['current_gym_id' => $gym->id])->get($path);
    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with([
    [GymRole::Owner, '/app', true],
    [GymRole::Admin, '/app', true],
    [GymRole::Trainer, '/trainer', true],
    [GymRole::Trainer, '/app', false],
    [GymRole::Admin, '/trainer', false],
    [GymRole::Owner, '/trainer', false],
    [GymRole::Admin, '/app/subscription', false],
    [GymRole::Owner, '/app/subscription', true],
    [GymRole::Trainer, '/app/pt-sessions', false],
]);

test('login selects the role workspace and discards an intended management URL for trainers', function (GymRole $role, string $destination) {
    $gym = Gym::factory()->create();
    $user = User::factory()->create();
    $gym->users()->attach($user, ['role' => $role, 'status' => GymUserStatus::Active]);
    if ($role === GymRole::Trainer) {
        Trainer::factory()->for($gym)->create(['user_id' => $user->id]);
    }
    $this->withSession(['url.intended' => url('/app/staff')])->post(route('login.store'), [
        'email' => $user->email, 'password' => 'password',
    ])->assertRedirect($destination);
})->with([[GymRole::Owner, '/app'], [GymRole::Admin, '/app'], [GymRole::Trainer, '/trainer']]);

test('a member cannot access management or trainer routes', function () {
    $member = Member::factory()->create(['user_id' => User::factory()->create()->id]);
    $this->actingAs($member->user)->get('/app')->assertForbidden();
    $this->get('/trainer')->assertForbidden();
});
