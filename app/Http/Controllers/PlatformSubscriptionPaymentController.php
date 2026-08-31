<?php

namespace App\Http\Controllers;

use App\Actions\Platform\ReviewSubscriptionPayment;
use App\Enums\SubscriptionPaymentStatus;
use App\Http\Requests\ReviewSubscriptionPaymentRequest;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlatformSubscriptionPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ReviewSubscriptionPaymentRequest $request,
        SubscriptionPayment $subscriptionPayment,
        ReviewSubscriptionPayment $reviewSubscriptionPayment,
    ): RedirectResponse {
        $reviewer = $request->user();
        abort_unless($reviewer !== null, 401);

        $payment = $reviewSubscriptionPayment->handle(
            $subscriptionPayment,
            $reviewer,
            SubscriptionPaymentStatus::from($request->string('decision')->toString()),
            $request->string('review_notes')->squish()->toString() ?: null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pembayaran {$payment->subscription->subscriber->name} {$payment->status->label()}.",
        ]);

        return back();
    }
}
