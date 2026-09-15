<?php

namespace App\Actions\Members;

use App\Enums\MemberPortalAccessMethod;
use App\Models\Member;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageMemberPortalAccess
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /** @return array{user: User, was_enabled: bool} */
    public function handle(
        Member $member,
        MemberPortalAccessMethod $accessMethod,
        ?string $password,
    ): array {
        return DB::transaction(function () use ($member, $accessMethod, $password): array {
            $lockedMember = Member::query()
                ->whereKey($member->getKey())
                ->where('gym_id', $member->gym_id)
                ->lockForUpdate()
                ->firstOrFail();
            $wasEnabled = $lockedMember->user_id !== null;
            $email = $this->portalEmail($lockedMember);
            $user = $lockedMember->user_id === null
                ? User::query()->where('email', $email)->lockForUpdate()->first()
                : User::query()->whereKey($lockedMember->user_id)->lockForUpdate()->firstOrFail();

            if ($user?->is_active === false) {
                throw ValidationException::withMessages([
                    'portal_access' => 'Akun dengan email ini sedang dinonaktifkan oleh Platform Admin.',
                ]);
            }

            if ($accessMethod === MemberPortalAccessMethod::Password) {
                $this->ensurePasswordMayBeManaged($lockedMember, $user);
            }

            $user ??= User::query()->create([
                'name' => $lockedMember->name,
                'email' => $email,
                'password' => $password ?? Str::password(40),
            ]);

            $emailAlreadyLinked = Member::query()
                ->where('gym_id', $lockedMember->gym_id)
                ->where('user_id', $user->getKey())
                ->whereKeyNot($lockedMember->getKey())
                ->exists();

            if ($emailAlreadyLinked) {
                throw ValidationException::withMessages([
                    'portal_access' => 'Email ini sudah terhubung ke member lain pada gym yang sama.',
                ]);
            }

            if ($accessMethod === MemberPortalAccessMethod::Password) {
                $this->replacePassword($user, $password);
            }

            $lockedMember->forceFill([
                'user_id' => $user->getKey(),
                'portal_qr_token' => $lockedMember->portal_qr_token ?? (string) Str::uuid(),
                'portal_enabled_at' => $lockedMember->portal_enabled_at ?? now(),
            ])->save();

            $event = match (true) {
                $wasEnabled && $accessMethod === MemberPortalAccessMethod::Invitation => 'member.portal_access_resent',
                $wasEnabled => 'member.portal_password_reset',
                default => 'member.portal_activated',
            };
            $this->activityLogger->record($event, $lockedMember, [
                'member_number' => $lockedMember->member_number,
                'user_id' => $user->getKey(),
                'access_method' => $accessMethod->value,
            ]);

            return ['user' => $user, 'was_enabled' => $wasEnabled];
        }, 3);
    }

    private function portalEmail(Member $member): string
    {
        if ($member->user_id !== null) {
            return User::query()->whereKey($member->user_id)->value('email')
                ?? throw ValidationException::withMessages([
                    'portal_access' => 'Akun portal member tidak ditemukan.',
                ]);
        }

        $email = is_string($member->email)
            ? Str::lower(Str::squish($member->email))
            : '';

        if ($email === '') {
            throw ValidationException::withMessages([
                'portal_access' => 'Tambahkan email member sebelum mengaktifkan portal.',
            ]);
        }

        return $email;
    }

    private function ensurePasswordMayBeManaged(Member $member, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        $isLinkedToCurrentMember = $member->user_id === $user->getKey();
        $hasAnotherIdentity = $user->is_platform_admin
            || $user->gyms()->exists()
            || $user->trainerProfiles()->exists()
            || $user->subscription()->exists()
            || $user->memberProfiles()->whereKeyNot($member->getKey())->exists();

        if (! $isLinkedToCurrentMember || $hasAnotherIdentity) {
            throw ValidationException::withMessages([
                'portal_access' => 'Password akun ini tidak boleh diubah oleh admin gym. Gunakan undangan agar pemilik email mengatur passwordnya sendiri.',
            ]);
        }
    }

    private function replacePassword(User $user, ?string $password): void
    {
        if (! is_string($password) || $password === '') {
            throw ValidationException::withMessages([
                'password' => 'Password baru wajib diisi.',
            ]);
        }

        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();
        Password::broker()->deleteToken($user);

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }
    }
}
