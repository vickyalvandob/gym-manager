<?php

namespace App\Http\Controllers;

use App\Actions\Platform\UpdatePlatformBillingSetting;
use App\Enums\SubscriptionPaymentStatus;
use App\Http\Requests\UpdatePlatformBillingSettingRequest;
use App\Models\PlatformBillingSetting;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformBillingController extends Controller
{
    public function index(Request $request): Response
    {
        $activeTab = in_array($request->string('tab')->toString(), ['pending', 'history', 'settings'], true)
            ? $request->string('tab')->toString()
            : 'pending';
        $settings = PlatformBillingSetting::query()->with('updatedBy:id,name')->find(1);
        $payments = SubscriptionPayment::query()
            ->with(['subscription.subscriber:id,name,email', 'reviewer:id,name'])
            ->when(
                $activeTab === 'pending',
                fn ($query) => $query->where('status', SubscriptionPaymentStatus::Pending),
                fn ($query) => $query->where('status', '!=', SubscriptionPaymentStatus::Pending),
            )
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SubscriptionPayment $payment): array => [
                'id' => $payment->getKey(),
                'subscriber_id' => $payment->subscription->subscriber_id,
                'subscriber_name' => $payment->subscription->subscriber->name,
                'subscriber_email' => $payment->subscription->subscriber->email,
                'plan_name' => $payment->plan_name,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'billing_interval_label' => $payment->billing_interval->label(),
                'reference_number' => $payment->reference_number,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'submitted_at' => $payment->submitted_at->toIso8601String(),
                'reviewed_at' => $payment->reviewed_at?->toIso8601String(),
                'reviewer_name' => $payment->reviewer?->name,
                'review_notes' => $payment->review_notes,
                'period_ends_at' => $payment->period_ends_at?->toIso8601String(),
            ]);

        return Inertia::render('platform/billing/index', [
            'activeTab' => $activeTab,
            'settings' => [
                'bank_name' => $settings?->bank_name,
                'account_name' => $settings?->account_name,
                'account_number' => $settings?->account_number,
                'instructions' => $settings?->instructions,
                'is_configured' => $settings?->isConfigured() ?? false,
                'updated_at' => $settings?->updated_at?->toIso8601String(),
                'updated_by' => $settings?->updatedBy?->name,
            ],
            'payments' => $payments,
        ]);
    }

    public function update(
        UpdatePlatformBillingSettingRequest $request,
        UpdatePlatformBillingSetting $updatePlatformBillingSetting,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor !== null, 401);

        $instructions = $request->string('instructions')->squish()->toString();
        $updatePlatformBillingSetting->handle([
            'bank_name' => $request->string('bank_name')->squish()->toString(),
            'account_name' => $request->string('account_name')->squish()->toString(),
            'account_number' => $request->string('account_number')->squish()->toString(),
            'instructions' => $instructions !== '' ? $instructions : null,
        ], $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Rekening pembayaran berhasil disimpan.',
        ]);

        return to_route('platform.billing.index', ['tab' => 'settings']);
    }
}
