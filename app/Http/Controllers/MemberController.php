<?php

namespace App\Http\Controllers;

use App\Actions\Members\CreateMember;
use App\Actions\Members\UpdateMember;
use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\IndexMemberRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexMemberRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');
        $today = $this->today();

        $members = $this->gymContext->gym()->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.email',
                'members.photo',
                'members.status',
                'members.created_at',
            ])
            ->with(['memberships' => fn ($query) => $query
                ->select([
                    'member_memberships.id',
                    'member_memberships.member_id',
                    'member_memberships.membership_plan_id',
                    'member_memberships.renewed_from_id',
                    'member_memberships.plan_name',
                    'member_memberships.duration',
                    'member_memberships.duration_unit',
                    'member_memberships.price',
                    'member_memberships.start_date',
                    'member_memberships.end_date',
                    'member_memberships.created_at',
                ])
                ->where('member_memberships.gym_id', $this->gymContext->gymId())
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->latest('start_date')
                ->latest('id')])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('member_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(is_string($status), fn ($query) => $query->where('status', $status))
            ->latest('members.id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (Member $member): array => $this->listMemberData($member, $today));

        return Inertia::render('members/index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Member::class);

        return Inertia::render('members/create', [
            'genderOptions' => $this->genderOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreMemberRequest $request, CreateMember $createMember): RedirectResponse
    {
        $photo = $request->file('photo');
        $member = $createMember->handle(
            $request->safe()->except('photo'),
            $photo instanceof UploadedFile ? $photo : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Member {$member->member_number} berhasil ditambahkan.",
        ]);

        return to_route('members.show', $member->getKey());
    }

    public function show(int $member): Response
    {
        $memberModel = $this->findMember($member);
        Gate::authorize('view', $memberModel);
        $today = $this->today();
        $activePlans = $this->activeMembershipPlans();
        $activeMembership = $memberModel->memberships()
            ->with(['payment.receivedBy:id,name'])
            ->where('gym_id', $this->gymContext->gymId())
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->latest('start_date')
            ->latest('id')
            ->first();
        $upcomingMembership = $memberModel->memberships()
            ->with(['payment.receivedBy:id,name'])
            ->where('gym_id', $this->gymContext->gymId())
            ->whereDate('start_date', '>', $today->toDateString())
            ->oldest('start_date')
            ->oldest('id')
            ->first();
        $renewalSource = $memberModel->memberships()
            ->where('gym_id', $this->gymContext->gymId())
            ->latest('end_date')
            ->latest('id')
            ->first();
        $memberships = $memberModel->memberships()
            ->with(['payment.receivedBy:id,name'])
            ->where('gym_id', $this->gymContext->gymId())
            ->latest('start_date')
            ->latest('id')
            ->paginate(10, ['*'], 'membership_page')
            ->withQueryString()
            ->through(fn (MemberMembership $membership): array => $this->membershipData(
                $membership,
                $today,
            ));

        return Inertia::render('members/show', [
            'member' => $this->detailMemberData($memberModel, $today),
            'activeMembership' => $activeMembership instanceof MemberMembership
                ? $this->membershipData($activeMembership, $today)
                : null,
            'upcomingMembership' => $upcomingMembership instanceof MemberMembership
                ? $this->membershipData($upcomingMembership, $today)
                : null,
            'memberships' => $memberships,
            'membershipPlans' => $activePlans,
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'membershipDefaults' => [
                'assign_start_date' => $today->toDateString(),
                'renewal_source_id' => $renewalSource?->getKey(),
                'renewal_plan_id' => $this->renewalPlanId($renewalSource, $activePlans),
                'renewal_start_date' => $renewalSource instanceof MemberMembership
                    ? CarbonImmutable::parse(
                        $renewalSource->end_date->toDateString(),
                        $this->gymContext->gym()->timezone,
                    )
                        ->addDay()
                        ->max($today)
                        ->toDateString()
                    : $today->toDateString(),
            ],
        ]);
    }

    public function edit(int $member): Response
    {
        $memberModel = $this->findMember($member);
        Gate::authorize('update', $memberModel);

        return Inertia::render('members/edit', [
            'member' => $this->detailMemberData($memberModel, $this->today()),
            'genderOptions' => $this->genderOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(
        UpdateMemberRequest $request,
        int $member,
        UpdateMember $updateMember,
    ): RedirectResponse {
        $memberModel = $this->findMember($member);
        Gate::authorize('update', $memberModel);

        $photo = $request->file('photo');
        $memberModel = $updateMember->handle(
            $memberModel,
            $request->safe()->except('photo'),
            $photo instanceof UploadedFile ? $photo : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data {$memberModel->member_number} berhasil diperbarui.",
        ]);

        return to_route('members.show', $memberModel->getKey());
    }

    private function findMember(int $member): Member
    {
        return $this->gymContext->gym()->members()
            ->whereKey($member)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function listMemberData(Member $member, CarbonImmutable $today): array
    {
        $membership = $member->relationLoaded('memberships')
            ? $member->memberships->first()
            : null;

        return [
            'id' => $member->getKey(),
            'member_number' => $member->member_number,
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email,
            'photo_url' => is_string($member->photo)
                ? route('members.photo', $member->getKey())
                : null,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'membership' => $membership instanceof MemberMembership
                ? $this->membershipData($membership, $today)
                : null,
            'created_at' => $member->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function detailMemberData(Member $member, CarbonImmutable $today): array
    {
        return [
            ...$this->listMemberData($member, $today),
            'gender' => $member->gender?->value,
            'gender_label' => $member->gender?->label(),
            'birth_date' => $member->birth_date?->toDateString(),
            'address' => $member->address,
            'emergency_contact' => $member->emergency_contact,
            'notes' => $member->notes,
            'updated_at' => $member->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(
            fn (MemberStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            MemberStatus::cases(),
        );
    }

    /** @return array<int, array{value: string, label: string}> */
    private function genderOptions(): array
    {
        return array_map(
            fn (MemberGender $gender): array => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ],
            MemberGender::cases(),
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function activeMembershipPlans(): array
    {
        return $this->gymContext->gym()->membershipPlans()
            ->select([
                'membership_plans.id',
                'membership_plans.name',
                'membership_plans.duration',
                'membership_plans.duration_unit',
                'membership_plans.price',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (MembershipPlan $membershipPlan): array => [
                'id' => $membershipPlan->getKey(),
                'name' => $membershipPlan->name,
                'duration' => $membershipPlan->duration,
                'duration_unit' => $membershipPlan->duration_unit->value,
                'duration_label' => "{$membershipPlan->duration} {$membershipPlan->duration_unit->label()}",
                'price' => $membershipPlan->price,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function membershipData(
        MemberMembership $membership,
        CarbonImmutable $today,
    ): array {
        $status = $membership->statusOn($today);
        $endDate = CarbonImmutable::parse(
            $membership->end_date->toDateString(),
            $this->gymContext->gym()->timezone,
        )->startOfDay();
        $daysRemaining = $status->value === 'active'
            ? (int) $today->diffInDays($endDate)
            : null;

        return [
            'id' => $membership->getKey(),
            'membership_plan_id' => $membership->membership_plan_id,
            'renewed_from_id' => $membership->renewed_from_id,
            'plan_name' => $membership->plan_name,
            'duration' => $membership->duration,
            'duration_unit' => $membership->duration_unit->value,
            'duration_unit_label' => $membership->duration_unit->label(),
            'duration_label' => "{$membership->duration} {$membership->duration_unit->label()}",
            'price' => $membership->price,
            'start_date' => $membership->start_date->toDateString(),
            'end_date' => $membership->end_date->toDateString(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'days_remaining' => $daysRemaining,
            'is_expiring_soon' => $daysRemaining !== null
                && $daysRemaining <= $this->gymContext->gym()->membership_expiry_warning_days,
            'payment' => $membership->relationLoaded('payment')
                && $membership->payment instanceof Payment
                    ? $this->paymentData($membership->payment)
                    : null,
            'created_at' => $membership->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function paymentData(Payment $payment): array
    {
        return [
            'id' => $payment->getKey(),
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'method' => $payment->method?->value,
            'method_label' => $payment->method?->label(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'notes' => $payment->notes,
            'received_by' => $payment->relationLoaded('receivedBy')
                && $payment->receivedBy !== null
                    ? [
                        'id' => $payment->receivedBy->getKey(),
                        'name' => $payment->receivedBy->name,
                    ]
                    : null,
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function paymentMethodOptions(): array
    {
        return array_map(
            fn (PaymentMethod $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
            ],
            PaymentMethod::cases(),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $activePlans
     */
    private function renewalPlanId(
        ?MemberMembership $renewalSource,
        array $activePlans,
    ): ?int {
        $activePlanIds = array_column($activePlans, 'id');

        if (
            $renewalSource instanceof MemberMembership
            && in_array($renewalSource->membership_plan_id, $activePlanIds, true)
        ) {
            return $renewalSource->membership_plan_id;
        }

        $firstPlanId = $activePlanIds[0] ?? null;

        return is_int($firstPlanId) ? $firstPlanId : null;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->gymContext->gym()->timezone)->startOfDay();
    }
}
