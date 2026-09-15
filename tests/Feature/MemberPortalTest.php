<?php

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MemberPtPackage;
use App\Models\Payment;
use App\Models\PtPackage;
use App\Models\PtSession;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function attachMemberPortalOperator(Gym $gym, User $user, GymRole $role = GymRole::Owner): void
{
    $gym->users()->attach($user, [
        'role' => $role->value,
        'status' => GymUserStatus::Active->value,
    ]);
}

function createPortalMember(Gym $gym, User $user): Member
{
    return Member::factory()->for($gym)->create([
        'user_id' => $user->getKey(),
        'email' => $user->email,
        'portal_qr_token' => fake()->uuid(),
        'portal_enabled_at' => now(),
    ]);
}

test('owner can activate and resend member portal access without creating a gym staff role', function () {
    Notification::fake();
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $member = Member::factory()->for($gym)->create(['email' => 'member.portal@example.test']);
    attachMemberPortalOperator($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $member), [
            'access_method' => 'invitation',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $member->refresh();
    $portalUser = User::query()->where('email', 'member.portal@example.test')->firstOrFail();

    expect($member->user_id)->toBe($portalUser->getKey())
        ->and($member->portal_qr_token)->not->toBeNull()
        ->and($member->portal_enabled_at)->not->toBeNull()
        ->and($gym->users()->whereKey($portalUser->getKey())->exists())->toBeFalse();
    Notification::assertSentTo($portalUser, ResetPassword::class);
    $this->assertDatabaseHas('activity_logs', ['event' => 'member.portal_activated']);

    $this->travel(61)->seconds();
    $this->post(route('members.portal-access.store', $member), [
        'access_method' => 'invitation',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(User::query()->where('email', 'member.portal@example.test')->count())->toBe(1);
    Notification::assertSentToTimes($portalUser, ResetPassword::class, 2);
    $this->assertDatabaseHas('activity_logs', ['event' => 'member.portal_access_resent']);
});

test('portal activation enforces operator role email requirement and active gym scope', function () {
    Notification::fake();
    $gym = Gym::factory()->create();
    $foreignGym = Gym::factory()->create();
    $owner = User::factory()->create();
    $trainer = User::factory()->create();
    $memberWithoutEmail = Member::factory()->for($gym)->create(['email' => null]);
    $foreignMember = Member::factory()->for($foreignGym)->create();
    attachMemberPortalOperator($gym, $owner);
    attachMemberPortalOperator($gym, $trainer, GymRole::Trainer);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $memberWithoutEmail), [
            'access_method' => 'invitation',
        ])
        ->assertSessionHasErrors('portal_access');

    $this->post(route('members.portal-access.store', $foreignMember), [
        'access_method' => 'invitation',
    ])
        ->assertNotFound();

    $this->actingAs($trainer)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $memberWithoutEmail), [
            'access_method' => 'invitation',
        ])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('owner can activate a member with an initial password and member can login immediately', function () {
    Notification::fake();
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $member = Member::factory()->for($gym)->create([
        'email' => 'member.direct@example.test',
    ]);
    attachMemberPortalOperator($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $member), [
            'access_method' => 'password',
            'password' => 'initial-password',
            'password_confirmation' => 'initial-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $portalUser = User::query()->where('email', 'member.direct@example.test')->firstOrFail();

    expect(Hash::check('initial-password', $portalUser->password))->toBeTrue()
        ->and($member->refresh()->user_id)->toBe($portalUser->getKey());
    Notification::assertNothingSent();
    $this->assertDatabaseHas('activity_logs', [
        'event' => 'member.portal_activated',
        'subject_id' => $member->getKey(),
    ]);

    $this->post(route('logout'));
    $this->post(route('login.store'), [
        'email' => $portalUser->email,
        'password' => 'initial-password',
    ])->assertRedirect(route('member-portal.index'));
    $this->assertAuthenticatedAs($portalUser);
});

test('owner can reset a standalone member password but cannot reset a shared staff account', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $portalUser = User::factory()->create(['password' => 'old-member-password']);
    $member = createPortalMember($gym, $portalUser);
    attachMemberPortalOperator($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $member), [
            'access_method' => 'password',
            'password' => 'new-member-password',
            'password_confirmation' => 'new-member-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Hash::check('new-member-password', $portalUser->refresh()->password))->toBeTrue();
    $this->assertDatabaseHas('activity_logs', [
        'event' => 'member.portal_password_reset',
        'subject_id' => $member->getKey(),
    ]);

    $staffUser = User::factory()->create([
        'email' => 'shared.staff@example.test',
        'password' => 'staff-password',
    ]);
    attachMemberPortalOperator($gym, $staffUser, GymRole::Admin);
    $sharedMember = Member::factory()->for($gym)->create([
        'email' => $staffUser->email,
    ]);

    $this->post(route('members.portal-access.store', $sharedMember), [
        'access_method' => 'password',
        'password' => 'hijacked-password',
        'password_confirmation' => 'hijacked-password',
    ])->assertSessionHasErrors('portal_access');

    expect($sharedMember->refresh()->user_id)->toBeNull()
        ->and(Hash::check('staff-password', $staffUser->refresh()->password))->toBeTrue();
});

test('direct portal activation validates password and confirmation', function () {
    $gym = Gym::factory()->create();
    $owner = User::factory()->create();
    $member = Member::factory()->for($gym)->create([
        'email' => 'member.validation@example.test',
    ]);
    attachMemberPortalOperator($gym, $owner);

    $this->actingAs($owner)
        ->withSession(['current_gym_id' => $gym->getKey()])
        ->post(route('members.portal-access.store', $member), [
            'access_method' => 'password',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
        ->assertSessionHasErrors('password');

    expect($member->refresh()->user_id)->toBeNull()
        ->and(User::query()->where('email', 'member.validation@example.test')->exists())->toBeFalse();
});

test('member login is redirected to the member portal', function () {
    $gym = Gym::factory()->create();
    $portalUser = User::factory()->create([
        'email' => 'member.login@example.test',
        'password' => 'member-password',
    ]);
    $member = createPortalMember($gym, $portalUser);

    $this->post(route('login.store'), [
        'email' => $portalUser->email,
        'password' => 'member-password',
    ])->assertRedirect(route('member-portal.index'));

    $this->assertAuthenticatedAs($portalUser);
    $this->get(route('member-portal.index'))
        ->assertRedirect(route('member-portal.show', $member));
});

test('member portal separates overview check-ins payments and personal training data', function () {
    $gym = Gym::factory()->create(['name' => 'Gymlo Utama']);
    $portalUser = User::factory()->create();
    $member = createPortalMember($gym, $portalUser);
    $membership = MemberMembership::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'plan_name' => 'Gold Bulanan',
        'start_date' => today()->subWeek(),
        'end_date' => today()->addWeeks(3),
    ]);
    $payment = Payment::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'invoice_number' => 'INV-PORTAL-001',
    ]);
    $checkIn = CheckIn::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'member_membership_id' => $membership->getKey(),
        'checked_in_at' => now()->subHour(),
    ]);
    $trainer = Trainer::factory()->for($gym)->create(['name' => 'Coach Naya']);
    $ptPackage = PtPackage::factory()->for($gym)->create(['name' => 'Strength 8x']);
    $memberPtPackage = MemberPtPackage::factory()->for($gym)->create([
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
        'pt_package_id' => $ptPackage->getKey(),
    ]);
    $ptSession = PtSession::factory()->for($gym)->create([
        'member_pt_package_id' => $memberPtPackage->getKey(),
        'member_id' => $member->getKey(),
        'trainer_id' => $trainer->getKey(),
    ]);

    $this->actingAs($portalUser)
        ->get(route('member-portal.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member-portal/overview')
            ->where('member.name', $member->name)
            ->where('member.gym.name', 'Gymlo Utama')
            ->where('membership.plan_name', 'Gold Bulanan')
            ->where('active_pt_package.name', 'Strength 8x')
            ->where('qr_code_data_url', fn ($value): bool => is_string($value)
                && str_starts_with($value, 'data:image/svg+xml;base64,'))
            ->missing('checkIns')
            ->missing('payments')
            ->missing('ptPackages')
            ->missing('ptSessions'));

    $this->get(route('member-portal.check-ins', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member-portal/check-ins')
            ->where('member.id', $member->getKey())
            ->missing('checkIns')
            ->missing('membership')
            ->loadDeferredProps('memberPortalCheckIns', fn (Assert $reload) => $reload
                ->where('checkIns.data.0.id', $checkIn->getKey())
                ->where('checkIns.total', 1)));

    $this->get(route('member-portal.payments', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member-portal/payments')
            ->missing('payments')
            ->missing('membership')
            ->loadDeferredProps('memberPortalPayments', fn (Assert $reload) => $reload
                ->where('payments.data.0.id', $payment->getKey())
                ->where('payments.total', 1)));

    $this->get(route('member-portal.personal-training', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member-portal/personal-training')
            ->missing('ptPackages')
            ->missing('ptSessions')
            ->missing('membership')
            ->loadDeferredProps('memberPortalTraining', fn (Assert $reload) => $reload
                ->where('ptPackages.data.0.id', $memberPtPackage->getKey())
                ->where('ptSessions.data.0.id', $ptSession->getKey())));

    $this->get(route('member-portal.settings', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member-portal/settings')
            ->where('member.email', $portalUser->email)
            ->has('passwordRules')
            ->missing('membership'));
});

test('member can update contact and password with current password confirmation', function () {
    $gym = Gym::factory()->create();
    $portalUser = User::factory()->create([
        'email' => 'member.old@example.test',
        'password' => 'current-password',
    ]);
    $member = createPortalMember($gym, $portalUser);

    $this->actingAs($portalUser)
        ->patch(route('member-portal.contact.update', $member), [
            'phone' => '081234567890',
            'email' => 'member.new@example.test',
            'address' => 'Jl. Gymlo No. 1',
            'emergency_contact' => '081298765432',
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect($portalUser->refresh()->email)->toBe('member.old@example.test');

    $this->actingAs($portalUser)
        ->patch(route('member-portal.contact.update', $member), [
            'phone' => '081234567890',
            'email' => 'member.new@example.test',
            'address' => 'Jl. Gymlo No. 1',
            'emergency_contact' => '081298765432',
            'current_password' => 'current-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($member->refresh()->phone)->toBe('081234567890')
        ->and($member->email)->toBe('member.new@example.test')
        ->and($portalUser->refresh()->email)->toBe('member.new@example.test');
    $this->assertDatabaseHas('activity_logs', ['event' => 'member.portal_contact_updated']);

    $this->put(route('user-password.update'), [
        'current_password' => 'current-password',
        'password' => 'new-member-password',
        'password_confirmation' => 'new-member-password',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-member-password', $portalUser->refresh()->password))->toBeTrue();
});

test('member cannot open another profile and expired gym subscription revokes portal access', function () {
    $portalUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $gym = Gym::factory()->create();
    $member = createPortalMember($gym, $portalUser);
    $otherMember = createPortalMember($gym, $otherUser);

    $portalRoutes = [
        'member-portal.show',
        'member-portal.check-ins',
        'member-portal.payments',
        'member-portal.personal-training',
        'member-portal.settings',
    ];

    $this->actingAs($portalUser);

    foreach ($portalRoutes as $portalRoute) {
        $this->get(route($portalRoute, $otherMember))->assertForbidden();
    }

    $plan = SaasPlan::factory()->create();
    $subscription = Subscription::factory()
        ->for(User::factory(), 'subscriber')
        ->for($plan, 'plan')
        ->create([
            'status' => SubscriptionStatus::Expired,
            'current_period_ends_at' => now()->subDay(),
        ]);
    $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

    foreach ($portalRoutes as $portalRoute) {
        $this->get(route($portalRoute, $member))->assertForbidden();
    }

    $this->get(route('member-portal.index'))->assertForbidden();
});

test('member photo remains private to its linked portal account', function () {
    Storage::fake('local');
    Storage::disk('local')->put('members/private-photo.jpg', 'private-image');

    $gym = Gym::factory()->create();
    $portalUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $member = createPortalMember($gym, $portalUser);
    $member->forceFill(['photo' => 'members/private-photo.jpg'])->save();

    $this->actingAs($otherUser)
        ->get(route('member-portal.photo', $member))
        ->assertForbidden();

    $this->actingAs($portalUser)
        ->get(route('member-portal.photo', $member))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=300, private');
});
