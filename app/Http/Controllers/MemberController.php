<?php

namespace App\Http\Controllers;

use App\Actions\Members\CreateMember;
use App\Actions\Members\UpdateMember;
use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use App\Http\Requests\IndexMemberRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Support\GymContext;
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
            ->through(fn (Member $member): array => $this->listMemberData($member));

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

        return Inertia::render('members/show', [
            'member' => $this->detailMemberData($memberModel),
        ]);
    }

    public function edit(int $member): Response
    {
        $memberModel = $this->findMember($member);
        Gate::authorize('update', $memberModel);

        return Inertia::render('members/edit', [
            'member' => $this->detailMemberData($memberModel),
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
    private function listMemberData(Member $member): array
    {
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
            'created_at' => $member->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function detailMemberData(Member $member): array
    {
        return [
            ...$this->listMemberData($member),
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
}
