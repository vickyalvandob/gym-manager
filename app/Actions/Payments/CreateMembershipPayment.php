<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Gym;
use App\Models\MemberMembership;
use App\Models\Payment;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMembershipPayment
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(MemberMembership $memberMembership): Payment
    {
        return DB::transaction(function () use ($memberMembership): Payment {
            $lockedGym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMembership = $lockedGym->memberMemberships()
                ->whereKey($memberMembership->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMembership->payment()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Invoice untuk periode membership ini sudah tersedia.',
                ]);
            }

            $invoiceNumber = sprintf(
                'INV-%s-%06d',
                CarbonImmutable::now($lockedGym->timezone)->format('Ym'),
                $lockedGym->next_invoice_sequence,
            );

            $payment = $lockedMembership->payment()->create([
                'gym_id' => $lockedGym->getKey(),
                'member_id' => $lockedMembership->member_id,
                'type' => PaymentType::Membership,
                'invoice_number' => $invoiceNumber,
                'amount' => $lockedMembership->price,
                'status' => PaymentStatus::Pending,
            ]);

            $lockedGym->forceFill([
                'next_invoice_sequence' => $lockedGym->next_invoice_sequence + 1,
            ])->save();

            $this->activityLogger->record('payment.created', $payment, [
                'invoice_number' => $payment->invoice_number,
                'member_id' => $payment->member_id,
                'member_membership_id' => $payment->member_membership_id,
                'type' => $payment->type->value,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
            ]);

            return $payment;
        }, 3);
    }
}
