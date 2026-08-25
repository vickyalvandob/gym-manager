<?php

namespace App\Http\Controllers;

use App\Actions\Payments\CreateMembershipPayment;
use App\Models\Payment;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CreateMembershipPaymentController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        int $member_membership,
        CreateMembershipPayment $createMembershipPayment,
    ): RedirectResponse {
        Gate::authorize('create', Payment::class);

        $membership = $this->gymContext->gym()->memberMemberships()
            ->whereKey($member_membership)
            ->firstOrFail();
        $payment = $createMembershipPayment->handle($membership);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Invoice {$payment->invoice_number} berhasil dibuat.",
        ]);

        return back();
    }
}
