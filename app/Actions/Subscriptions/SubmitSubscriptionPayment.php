<?php

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionPaymentStatus;
use App\Models\PlatformBillingSetting;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\SubscriptionQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitSubscriptionPayment
{
    public function __construct(private readonly SubscriptionQuota $subscriptionQuota) {}

    public function handle(
        User $subscriber,
        Subscription $subscription,
        SaasPlan $selectedPlan,
        string $referenceNumber,
        UploadedFile $proof,
    ): SubscriptionPayment {
        if (! PlatformBillingSetting::query()->find(1)?->isConfigured()) {
            throw ValidationException::withMessages([
                'proof' => 'Rekening pembayaran belum dikonfigurasi oleh Platform Admin.',
            ]);
        }

        $proofPath = $proof->store('subscription-payments/'.$subscription->getKey(), 'local');

        if (! is_string($proofPath)) {
            throw ValidationException::withMessages([
                'proof' => 'Bukti pembayaran gagal disimpan. Silakan coba lagi.',
            ]);
        }

        try {
            return DB::transaction(function () use ($subscriber, $subscription, $selectedPlan, $referenceNumber, $proofPath): SubscriptionPayment {
                $lockedSubscription = Subscription::query()
                    ->with('plan')
                    ->whereKey($subscription->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedSubscription->subscriber_id !== $subscriber->getKey()) {
                    abort(403);
                }

                if ($lockedSubscription->payments()
                    ->where('status', SubscriptionPaymentStatus::Pending)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'proof' => 'Masih ada pembayaran yang menunggu persetujuan Platform Admin.',
                    ]);
                }

                $plan = SaasPlan::query()
                    ->whereKey($selectedPlan->getKey())
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $plan->price <= 0) {
                    throw ValidationException::withMessages([
                        'proof' => 'Paket gratis tidak memerlukan pembayaran manual.',
                    ]);
                }

                $this->subscriptionQuota->ensurePlanCoversUsage($lockedSubscription, $plan);

                return $lockedSubscription->payments()->create([
                    'saas_plan_id' => $plan->getKey(),
                    'plan_name' => $plan->name,
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'billing_interval' => $plan->billing_interval,
                    'reference_number' => $referenceNumber,
                    'proof_path' => $proofPath,
                    'status' => SubscriptionPaymentStatus::Pending,
                    'submitted_at' => now(),
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proofPath);

            throw $exception;
        }
    }
}
