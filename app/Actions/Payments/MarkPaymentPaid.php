<?php

namespace App\Actions\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkPaymentPaid
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(
        Payment $payment,
        PaymentMethod $paymentMethod,
        ?string $notes,
        User $receivedBy,
    ): Payment {
        return DB::transaction(function () use ($payment, $paymentMethod, $notes, $receivedBy): Payment {
            $lockedPayment = $this->gymContext->gym()->payments()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'Invoice ini sudah lunas dan tidak dapat dibayar ulang.',
                ]);
            }

            $lockedPayment->update([
                'method' => $paymentMethod,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'notes' => $notes === null ? null : trim($notes),
                'received_by_id' => $receivedBy->getKey(),
            ]);

            $this->activityLogger->record('payment.paid', $lockedPayment, [
                'invoice_number' => $lockedPayment->invoice_number,
                'member_id' => $lockedPayment->member_id,
                'member_membership_id' => $lockedPayment->member_membership_id,
                'amount' => $lockedPayment->amount,
                'method' => $paymentMethod->value,
                'received_by_id' => $receivedBy->getKey(),
            ]);

            return $lockedPayment;
        }, 3);
    }
}
