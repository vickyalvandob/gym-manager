<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Http\Requests\IndexPaymentRequest;
use App\Models\Payment;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexPaymentRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');
        $method = $request->validated('method');
        $validatedType = $request->validated('type');
        $type = is_string($validatedType)
            ? $validatedType
            : PaymentType::Membership->value;
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');
        $timezone = $this->gymContext->gym()->timezone;
        $dateFromUtc = is_string($dateFrom)
            ? CarbonImmutable::parse($dateFrom, $timezone)->startOfDay()->utc()
            : null;
        $dateToUtc = is_string($dateTo)
            ? CarbonImmutable::parse($dateTo, $timezone)->addDay()->startOfDay()->utc()
            : null;

        $query = $this->filteredQuery(
            $search,
            is_string($status) ? $status : null,
            is_string($method) ? $method : null,
            $type,
            $dateFromUtc,
            $dateToUtc,
        );
        $summary = (clone $query)
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) AS paid_total, '
                .'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) AS outstanding_total, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS paid_count, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending_count',
                [
                    PaymentStatus::Paid->value,
                    PaymentStatus::Pending->value,
                    PaymentStatus::Paid->value,
                    PaymentStatus::Pending->value,
                ],
            )
            ->first();
        $payments = $query
            ->select([
                'payments.id',
                'payments.member_id',
                'payments.type',
                'payments.member_membership_id',
                'payments.member_pt_package_id',
                'payments.invoice_number',
                'payments.amount',
                'payments.method',
                'payments.status',
                'payments.paid_at',
                'payments.notes',
                'payments.received_by_id',
                'payments.created_at',
            ])
            ->with([
                'member:id,member_number,name,phone',
                'memberMembership:id,plan_name,start_date,end_date',
                'memberPtPackage:id,pt_package_id,trainer_id,total_sessions,start_date,expires_at',
                'memberPtPackage.ptPackage:id,name',
                'memberPtPackage.trainer:id,name',
                'receivedBy:id,name',
            ])
            ->latest('payments.id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Payment $payment): array => $this->paymentData($payment));

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'method' => is_string($method) ? $method : '',
                'type' => $type,
                'date_from' => is_string($dateFrom) ? $dateFrom : '',
                'date_to' => is_string($dateTo) ? $dateTo : '',
            ],
            'summary' => [
                'paid_total' => $this->decimalString($summary->paid_total),
                'outstanding_total' => $this->decimalString($summary->outstanding_total),
                'paid_count' => (int) ($summary->paid_count ?? 0),
                'pending_count' => (int) ($summary->pending_count ?? 0),
            ],
            'statusOptions' => $this->statusOptions(),
            'methodOptions' => $this->methodOptions(),
        ]);
    }

    /** @return Builder<Payment> */
    private function filteredQuery(
        string $search,
        ?string $status,
        ?string $method,
        string $type,
        ?CarbonImmutable $dateFromUtc,
        ?CarbonImmutable $dateToUtc,
    ): Builder {
        return $this->gymContext->gym()->payments()
            ->getQuery()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('member', function (Builder $query) use ($search): void {
                            $query->where(function (Builder $query) use ($search): void {
                                $query->where('member_number', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });
                        });
                });
            })
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($method !== null, fn (Builder $query) => $query->where('method', $method))
            ->where('type', $type)
            ->when($dateFromUtc !== null, fn (Builder $query) => $query->where('payments.created_at', '>=', $dateFromUtc))
            ->when($dateToUtc !== null, fn (Builder $query) => $query->where('payments.created_at', '<', $dateToUtc));
    }

    /** @return array<string, mixed> */
    private function paymentData(Payment $payment): array
    {
        return [
            'id' => $payment->getKey(),
            'invoice_number' => $payment->invoice_number,
            'type' => $payment->type->value,
            'type_label' => $payment->type->label(),
            'amount' => $payment->amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'method' => $payment->method?->value,
            'method_label' => $payment->method?->label(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'notes' => $payment->notes,
            'created_at' => $payment->created_at?->toIso8601String(),
            'member' => [
                'id' => $payment->member->getKey(),
                'member_number' => $payment->member->member_number,
                'name' => $payment->member->name,
                'phone' => $payment->member->phone,
            ],
            'membership' => $payment->memberMembership === null ? null : [
                'id' => $payment->memberMembership->getKey(),
                'plan_name' => $payment->memberMembership->plan_name,
                'start_date' => $payment->memberMembership->start_date->toDateString(),
                'end_date' => $payment->memberMembership->end_date->toDateString(),
            ],
            'personal_training' => $payment->memberPtPackage === null ? null : [
                'id' => $payment->memberPtPackage->getKey(),
                'package_name' => $payment->memberPtPackage->ptPackage->name,
                'trainer_name' => $payment->memberPtPackage->trainer->name,
                'total_sessions' => $payment->memberPtPackage->total_sessions,
                'start_date' => $payment->memberPtPackage->start_date->toDateString(),
                'expires_at' => $payment->memberPtPackage->expires_at?->toDateString(),
            ],
            'received_by' => $payment->receivedBy === null
                ? null
                : [
                    'id' => $payment->receivedBy->getKey(),
                    'name' => $payment->receivedBy->name,
                ],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(
            fn (PaymentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            PaymentStatus::cases(),
        );
    }

    /** @return array<int, array{value: string, label: string}> */
    private function methodOptions(): array
    {
        return array_map(
            fn (PaymentMethod $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
            ],
            PaymentMethod::cases(),
        );
    }

    private function decimalString(mixed $value): string
    {
        $decimal = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
