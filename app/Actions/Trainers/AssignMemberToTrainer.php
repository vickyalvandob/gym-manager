<?php

namespace App\Actions\Trainers;

use App\Enums\MemberStatus;
use App\Enums\TrainerStatus;
use App\Models\Member;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignMemberToTrainer
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

            if ($lockedTrainer->status !== TrainerStatus::Active) {
                throw ValidationException::withMessages([
                    'trainer' => 'Aktifkan trainer sebelum menambahkan assignment member.',
                ]);
            }

            if ($lockedMember->status !== MemberStatus::Active) {
                throw ValidationException::withMessages([
                    'member_id' => 'Hanya member aktif yang dapat ditugaskan ke trainer.',
                ]);
            }

            $currentAssignment = TrainerMember::query()
                ->where('gym_id', $this->gymContext->gymId())
                ->where('member_id', $lockedMember->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($currentAssignment?->trainer_id === $lockedTrainer->getKey()) {
                throw ValidationException::withMessages([
                    'member_id' => 'Member sudah ditugaskan kepada trainer ini.',
                ]);
            }

            $actorId = $this->request->user()?->getKey();
            $previousTrainerId = $currentAssignment?->trainer_id;

            if ($currentAssignment instanceof TrainerMember) {
                TrainerMember::query()
                    ->where('gym_id', $this->gymContext->gymId())
                    ->where('member_id', $lockedMember->getKey())
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'ended_at' => now(),
                        'ended_by' => $actorId,
                        'updated_at' => now(),
                    ]);
            }

            TrainerMember::query()->create([
                'gym_id' => $this->gymContext->gymId(),
                'trainer_id' => $lockedTrainer->getKey(),
                'member_id' => $lockedMember->getKey(),
                'assigned_at' => now(),
                'is_active' => true,
                'assigned_by' => $actorId,
            ]);

            $this->activityLogger->record(
                $previousTrainerId === null
                    ? 'trainer_member.assigned'
                    : 'trainer_member.changed',
                $lockedTrainer,
                [
                    'member_id' => $lockedMember->getKey(),
                    'member_number' => $lockedMember->member_number,
                    'member_name' => $lockedMember->name,
                    'previous_trainer_id' => $previousTrainerId,
                    'trainer_id' => $lockedTrainer->getKey(),
                ],
            );
        }, 3);
    }
}
