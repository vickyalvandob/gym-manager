<?php

namespace App\Actions\PersonalTraining;

use App\Actions\Trainers\AssignMemberToTrainer;
use App\Enums\MemberPtPackageStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\TrainerStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberPtPackage;
use App\Models\PtPackage;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasePtPackage
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly AssignMemberToTrainer $assignMemberToTrainer,
    ) {}

    public function handle(
        Member $member,
        PtPackage $ptPackage,
        Trainer $trainer,
        CarbonImmutable $startDate,
        PaymentMethod $paymentMethod,
        ?string $notes,
        User $createdBy,
    ): MemberPtPackage {
        return DB::transaction(function () use (
            $member,
            $ptPackage,
            $trainer,
            $startDate,
            $paymentMethod,
            $notes,
            $createdBy,
        ): MemberPtPackage {
            $lockedGym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMember = $lockedGym->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedTrainer = $lockedGym->trainers()
                ->whereKey($trainer->getKey())
                ->where('status', TrainerStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPtPackage = $lockedGym->ptPackages()
                ->whereKey($ptPackage->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $today = CarbonImmutable::now($lockedGym->timezone)->startOfDay();

            if ($lockedMember->status !== MemberStatus::Active) {
                throw ValidationException::withMessages([
                    'member' => 'Hanya member aktif yang dapat membeli paket PT.',
                ]);
            }

            if ($startDate->lessThan($today)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal mulai PT tidak boleh sebelum hari ini.',
                ]);
            }

            $hasActivePackage = $lockedGym->memberPtPackages()
                ->where('member_id', $lockedMember->getKey())
                ->where('status', MemberPtPackageStatus::Active->value)
                ->where(function ($query) use ($today): void {
                    $query->whereNull('expires_at')
                        ->orWhereDate('expires_at', '>=', $today->toDateString());
                })
                ->lockForUpdate()
                ->exists();

            if ($hasActivePackage) {
                throw ValidationException::withMessages([
                    'pt_package_id' => 'Member masih memiliki paket PT aktif.',
                ]);
            }

            $currentTrainerId = TrainerMember::query()
                ->where('gym_id', $lockedGym->getKey())
                ->where('member_id', $lockedMember->getKey())
                ->where('is_active', true)
                ->value('trainer_id');

            if ((int) $currentTrainerId !== $lockedTrainer->getKey()) {
                $this->assignMemberToTrainer->handle($lockedTrainer, $lockedMember);
            }

            $expiresAt = $lockedPtPackage->validity_days === null
                ? null
                : $startDate->addDays($lockedPtPackage->validity_days - 1);
            $memberPtPackage = $lockedGym->memberPtPackages()->create([
                'member_id' => $lockedMember->getKey(),
                'trainer_id' => $lockedTrainer->getKey(),
                'pt_package_id' => $lockedPtPackage->getKey(),
                'total_sessions' => $lockedPtPackage->session_count,
                'used_sessions' => 0,
                'start_date' => $startDate->toDateString(),
                'expires_at' => $expiresAt?->toDateString(),
                'price' => $lockedPtPackage->price,
                'status' => MemberPtPackageStatus::Active,
                'payment_status' => PaymentStatus::Paid,
                'notes' => $notes,
                'created_by' => $createdBy->getKey(),
            ]);
            $invoiceNumber = sprintf(
                'INV-%s-%06d',
                CarbonImmutable::now($lockedGym->timezone)->format('Ym'),
                $lockedGym->next_invoice_sequence,
            );
            $payment = $lockedGym->payments()->create([
                'member_id' => $lockedMember->getKey(),
                'type' => PaymentType::PersonalTraining,
                'member_pt_package_id' => $memberPtPackage->getKey(),
                'invoice_number' => $invoiceNumber,
                'amount' => $lockedPtPackage->price,
                'method' => $paymentMethod,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'received_by_id' => $createdBy->getKey(),
            ]);
            $lockedGym->forceFill([
                'next_invoice_sequence' => $lockedGym->next_invoice_sequence + 1,
            ])->save();

            $this->activityLogger->record('member_pt_package.created', $memberPtPackage, [
                'member_id' => $lockedMember->getKey(),
                'trainer_id' => $lockedTrainer->getKey(),
                'pt_package_id' => $lockedPtPackage->getKey(),
                'total_sessions' => $memberPtPackage->total_sessions,
                'price' => $memberPtPackage->price,
            ]);
            $this->activityLogger->record('payment.paid', $payment, [
                'invoice_number' => $payment->invoice_number,
                'type' => PaymentType::PersonalTraining->value,
                'member_id' => $lockedMember->getKey(),
                'member_pt_package_id' => $memberPtPackage->getKey(),
                'amount' => $payment->amount,
                'method' => $paymentMethod->value,
            ]);

            return $memberPtPackage;
        }, 3);
    }
}
