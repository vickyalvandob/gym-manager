<?php

namespace App\Actions\Trainers;

use App\Models\Member;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMemberFromTrainer
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly Request $request,
    ) {}

    public function handle(Trainer $trainer, Member $member): void
    {
        DB::transaction(function () use ($trainer, $member): void {
            $lockedTrainer = $this->gymContext->gym()->trainers()
                ->whereKey($trainer->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMember = $this->gymContext->gym()->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $assignment = TrainerMember::query()
                ->where('gym_id', $this->gymContext->gymId())
                ->where('trainer_id', $lockedTrainer->getKey())
                ->where('member_id', $lockedMember->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $assignment instanceof TrainerMember) {
                throw ValidationException::withMessages([
                    'member' => 'Assignment member tidak ditemukan pada trainer ini.',
                ]);
            }

            TrainerMember::query()
                ->where('gym_id', $this->gymContext->gymId())
                ->where('trainer_id', $lockedTrainer->getKey())
                ->where('member_id', $lockedMember->getKey())
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_at' => now(),
                    'ended_by' => $this->request->user()?->getKey(),
                    'updated_at' => now(),
                ]);

            $this->activityLogger->record('trainer_member.changed', $lockedTrainer, [
                'member_id' => $lockedMember->getKey(),
                'member_number' => $lockedMember->member_number,
                'member_name' => $lockedMember->name,
                'previous_trainer_id' => $lockedTrainer->getKey(),
                'trainer_id' => null,
            ]);
        }, 3);
    }
}
