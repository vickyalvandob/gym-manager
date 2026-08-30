<?php

namespace App\Actions\Members;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Subscription;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateMember
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, ?UploadedFile $photo): Member
    {
        $photoPath = $photo?->store('member-photos/'.$this->gymContext->gymId(), 'local');

        if ($photo !== null && ! is_string($photoPath)) {
            throw new RuntimeException('Foto member gagal disimpan.');
        }

        try {
            return DB::transaction(function () use ($attributes, $photoPath): Member {
                $subscriptionId = $this->gymContext->gym()->subscription_id;

                if ($subscriptionId !== null) {
                    $subscription = Subscription::query()
                        ->whereKey($subscriptionId)
                        ->with('plan')
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->subscriptionQuota->ensureCanCreateMember($subscription);
                }

                $gym = Gym::query()
                    ->whereKey($this->gymContext->gymId())
                    ->lockForUpdate()
                    ->firstOrFail();

                $sequence = $gym->next_member_sequence;
                $member = $gym->members()->create([
                    ...$attributes,
                    'member_number' => sprintf('MBR-%06d', $sequence),
                    'photo' => $photoPath,
                ]);

                $gym->forceFill(['next_member_sequence' => $sequence + 1])->save();

                $this->activityLogger->record('member.created', $member, [
                    'member_number' => $member->member_number,
                    'status' => $member->status->value,
                ]);

                return $member;
            }, 3);
        } catch (Throwable $exception) {
            if (is_string($photoPath)) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }
}
