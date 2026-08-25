<?php

namespace App\Http\Controllers;

use App\Actions\Payments\MarkPaymentPaid;
use App\Enums\PaymentMethod;
use App\Http\Requests\MarkPaymentPaidRequest;
use App\Models\User;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MarkPaymentPaidController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        MarkPaymentPaidRequest $request,
        int $payment,
        MarkPaymentPaid $markPaymentPaid,
    ): RedirectResponse {
        $paymentModel = $this->gymContext->gym()->payments()
            ->whereKey($payment)
            ->firstOrFail();
        Gate::authorize('update', $paymentModel);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $paymentModel = $markPaymentPaid->handle(
            $paymentModel,
            PaymentMethod::from((string) $request->validated('method')),
            is_string($request->validated('notes'))
                ? $request->validated('notes')
                : null,
            $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Pembayaran {$paymentModel->invoice_number} berhasil dicatat.",
        ]);

        return back();
    }
}
