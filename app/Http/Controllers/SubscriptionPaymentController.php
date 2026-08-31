<?php

namespace App\Http\Controllers;

use App\Actions\Subscriptions\SubmitSubscriptionPayment;
use App\Http\Requests\StoreSubscriptionPaymentRequest;
use App\Models\SaasPlan;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;

class SubscriptionPaymentController extends Controller
{
    public function store(
        StoreSubscriptionPaymentRequest $request,
        GymContext $gymContext,
        SubmitSubscriptionPayment $submitSubscriptionPayment,
    ): RedirectResponse {
        $subscription = $gymContext->gym()->subscription()->firstOrFail();
        $user = $request->user();
        $proof = $request->file('proof');
        $plan = SaasPlan::query()
            ->where('is_active', true)
            ->findOrFail((int) $request->validated('saas_plan_id'));

        abort_unless(
            $user !== null
            && $proof instanceof UploadedFile
            && $subscription->subscriber_id === $user->getKey(),
            403,
        );

        $submitSubscriptionPayment->handle(
            $user,
            $subscription,
            $plan,
            $request->string('reference_number')->squish()->toString(),
            $proof,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Bukti pembayaran dikirim dan sedang menunggu persetujuan Platform Admin.',
        ]);

        return to_route('subscription.show', ['tab' => 'payment', 'plan_id' => $plan->getKey()]);
    }
}
