<?php

namespace App\Actions\PersonalTraining;

use App\Enums\PtSessionStatus;
use App\Models\PtSession;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPtSession
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(PtSession $session, ?string $reason): PtSession
    {
        return DB::transaction(function () use ($session, $reason): PtSession {
            $lockedSession = $this->gymContext->gym()->ptSessions()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== PtSessionStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'session' => 'Hanya sesi terjadwal yang dapat dibatalkan.',
                ]);
            }

            $lockedSession->update([
                'status' => PtSessionStatus::Cancelled,
                'cancellation_reason' => $reason,
                'quota_consumed' => false,
            ]);
            $this->activityLogger->record('pt_session.cancelled', $lockedSession, [
                'member_id' => $lockedSession->member_id,
                'trainer_id' => $lockedSession->trainer_id,
                'cancellation_reason' => $reason,
            ]);

            return $lockedSession;
        }, 3);
    }
}
