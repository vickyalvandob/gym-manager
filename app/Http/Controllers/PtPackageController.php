<?php

namespace App\Http\Controllers;

use App\Actions\PtPackages\CreatePtPackage;
use App\Actions\PtPackages\UpdatePtPackage;
use App\Http\Requests\IndexPtPackageRequest;
use App\Http\Requests\StorePtPackageRequest;
use App\Http\Requests\UpdatePtPackageRequest;
use App\Models\PtPackage;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PtPackageController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexPtPackageRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');
        $ptPackages = $this->gymContext->gym()->ptPackages()
            ->select([
                'pt_packages.id',
                'pt_packages.name',
                'pt_packages.session_count',
                'pt_packages.validity_days',
                'pt_packages.price',
                'pt_packages.description',
                'pt_packages.is_active',
                'pt_packages.created_at',
            ])
            ->withCount('memberPtPackages')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('pt_packages.id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (PtPackage $ptPackage): array => $this->ptPackageData($ptPackage));

        return Inertia::render('pt-packages/index', [
            'ptPackages' => $ptPackages,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'canCreate' => Gate::allows('create', PtPackage::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', PtPackage::class);

        return Inertia::render('pt-packages/create');
    }

    public function store(
        StorePtPackageRequest $request,
        CreatePtPackage $createPtPackage,
    ): RedirectResponse {
        $ptPackage = $createPtPackage->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$ptPackage->name} berhasil ditambahkan.",
        ]);

        return to_route('pt-packages.show', $ptPackage->getKey());
    }

    public function show(int $pt_package): Response
    {
        $ptPackage = $this->findPtPackage($pt_package);
        Gate::authorize('view', $ptPackage);

        return Inertia::render('pt-packages/show', [
            'ptPackage' => $this->ptPackageData($ptPackage),
            'canEdit' => Gate::allows('update', $ptPackage),
        ]);
    }

    public function edit(int $pt_package): Response
    {
        $ptPackage = $this->findPtPackage($pt_package);
        Gate::authorize('update', $ptPackage);

        return Inertia::render('pt-packages/edit', [
            'ptPackage' => $this->ptPackageData($ptPackage),
        ]);
    }

    public function update(
        UpdatePtPackageRequest $request,
        int $pt_package,
        UpdatePtPackage $updatePtPackage,
    ): RedirectResponse {
        $ptPackage = $this->findPtPackage($pt_package);
        Gate::authorize('update', $ptPackage);
        $ptPackage = $updatePtPackage->handle($ptPackage, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$ptPackage->name} berhasil diperbarui.",
        ]);

        return to_route('pt-packages.show', $ptPackage->getKey());
    }

    private function findPtPackage(int $ptPackage): PtPackage
    {
        return $this->gymContext->gym()->ptPackages()
            ->withCount('memberPtPackages')
            ->whereKey($ptPackage)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function ptPackageData(PtPackage $ptPackage): array
    {
        return [
            'id' => $ptPackage->getKey(),
            'name' => $ptPackage->name,
            'session_count' => $ptPackage->session_count,
            'validity_days' => $ptPackage->validity_days,
            'price' => $ptPackage->price,
            'description' => $ptPackage->description,
            'is_active' => $ptPackage->is_active,
            'status_label' => $ptPackage->is_active ? 'Aktif' : 'Nonaktif',
            'sales_count' => (int) ($ptPackage->getAttribute('member_pt_packages_count') ?? 0),
            'created_at' => $ptPackage->created_at?->toIso8601String(),
            'updated_at' => $ptPackage->updated_at?->toIso8601String(),
        ];
    }
}
