<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionPaymentProofController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SubscriptionPayment $subscriptionPayment): StreamedResponse
    {
        $user = $request->user();
        $subscriptionPayment->loadMissing('subscription:id,subscriber_id');

        abort_unless(
            $user !== null && (
                $user->is_platform_admin
                || $subscriptionPayment->subscription->subscriber_id === $user->getKey()
            ),
            403,
        );
        abort_unless(Storage::disk('local')->exists($subscriptionPayment->proof_path), 404);

        return Storage::disk('local')->response($subscriptionPayment->proof_path);
    }
}
