<?php

namespace App\Actions\Members;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateMemberPortalContact
{
    public function __construct(private readonly Request $request) {}

    /** @param array{phone: string, email: string, address?: string|null, emergency_contact?: string|null} $attributes */
    public function handle(Member $member, User $user, array $attributes): Member
    {
        return DB::transaction(function () use ($member, $user, $attributes): Member {
            $email = Str::lower(Str::squish($attributes['email']));
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $linkedMembers = $lockedUser->memberProfiles()->lockForUpdate()->get();
            $lockedMember = $linkedMembers->firstWhere('id', $member->getKey());

            abort_unless($lockedMember instanceof Member, 403);

            $lockedMember->fill([
                'phone' => $attributes['phone'],
                'email' => $email,
                'address' => $attributes['address'] ?? null,
                'emergency_contact' => $attributes['emergency_contact'] ?? null,
            ]);
            $changedFields = array_keys($lockedMember->getDirty());
            $lockedMember->save();

            if ($lockedUser->email !== $email) {
                $lockedUser->forceFill([
                    'email' => $email,
                    'email_verified_at' => null,
                ])->save();

                $lockedUser->memberProfiles()
                    ->whereKeyNot($lockedMember->getKey())
                    ->update(['email' => $email]);
            }

            ActivityLog::query()->create([
                'gym_id' => $lockedMember->gym_id,
                'user_id' => $lockedUser->getKey(),
                'event' => 'member.portal_contact_updated',
                'subject_type' => $lockedMember->getMorphClass(),
                'subject_id' => $lockedMember->getKey(),
                'properties' => ['fields' => $changedFields],
                'ip_address' => $this->request->ip(),
            ]);

            return $lockedMember;
        }, 3);
    }
}
