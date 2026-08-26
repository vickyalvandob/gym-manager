import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    FilterX,
    Pencil,
    Plus,
    Search,
    Tickets,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import MembershipPlanController from '@/actions/App/Http/Controllers/MembershipPlanController';
import { MembershipPlanStatusBadge } from '@/components/membership-plans/membership-plan-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/formatters';
import type { PaginatedMembershipPlans } from '@/types';

type Filters = {
    search: string;
    status: string;
    per_page: number;
};

export default function MembershipPlansIndex({
    membershipPlans,
    filters,
}: {
    membershipPlans: PaginatedMembershipPlans;
    filters: Filters;
}) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [perPage, setPerPage] = useState(String(filters.per_page));
    const hasFilters = filters.search !== '' || filters.status !== '';

    function submitFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            MembershipPlanController.index.url(),
            { search, status, per_page: perPage },
            { preserveState: true, replace: true },
        );
    }

    function clearFilters() {
        setSearch('');
        setStatus('');
        setPerPage('15');
        router.get(
            MembershipPlanController.index.url(),
            {},
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Paket Membership" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {membershipPlans.total} total
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                            Paket Membership
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Atur paket yang dipilih saat pendaftaran dan
                            perpanjangan member.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={MembershipPlanController.create()}>
                            <Plus />
                            Tambah paket
                        </Link>
                    </Button>
                </header>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 border-y py-4 md:grid-cols-[minmax(14rem,1fr)_11rem_8rem_auto]"
                    aria-label="Filter paket membership"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama atau deskripsi"
                            className="pl-9"
                            aria-label="Cari paket membership"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Filter status"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <select
                        value={perPage}
                        onChange={(event) => setPerPage(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Jumlah per halaman"
                    >
                        {[10, 15, 25, 50].map((value) => (
                            <option key={value} value={value}>
                                {value} / halaman
                            </option>
                        ))}
                    </select>
                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            variant="outline"
                            className="flex-1"
                        >
                            <Search />
                            Terapkan
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={clearFilters}
                                title="Hapus filter"
                            >
                                <FilterX />
                                <span className="sr-only">Hapus filter</span>
                            </Button>
                        )}
                    </div>
                </form>

                {membershipPlans.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 py-12 text-center">
                        <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <Tickets className="size-5" />
                        </div>
                        <p className="mt-4 text-sm font-medium">
                            {hasFilters
                                ? 'Paket tidak ditemukan'
                                : 'Belum ada paket membership'}
                        </p>
                        {hasFilters ? (
                            <Button
                                type="button"
                                className="mt-4"
                                size="sm"
                                variant="outline"
                                onClick={clearFilters}
                            >
                                <FilterX />
                                Hapus filter
                            </Button>
                        ) : (
                            <Button className="mt-4" size="sm" asChild>
                                <Link href={MembershipPlanController.create()}>
                                    <Plus />
                                    Tambah paket
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-lg border md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Paket
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Durasi
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Harga
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {membershipPlans.data.map(
                                        (membershipPlan) => (
                                            <tr
                                                key={membershipPlan.id}
                                                className="hover:bg-muted/30"
                                            >
                                                <td className="max-w-72 px-4 py-3">
                                                    <Link
                                                        href={MembershipPlanController.show(
                                                            membershipPlan.id,
                                                        )}
                                                        className="block truncate font-medium hover:text-primary"
                                                    >
                                                        {membershipPlan.name}
                                                    </Link>
                                                    <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                        {membershipPlan.description ??
                                                            'Tanpa deskripsi'}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {
                                                        membershipPlan.duration_label
                                                    }
                                                </td>
                                                <td className="px-4 py-3 tabular-nums">
                                                    {formatCurrency(
                                                        membershipPlan.price,
                                                        currency,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <MembershipPlanStatusBadge
                                                        isActive={
                                                            membershipPlan.is_active
                                                        }
                                                        label={
                                                            membershipPlan.status_label
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={MembershipPlanController.show(
                                                                    membershipPlan.id,
                                                                )}
                                                            >
                                                                Detail
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={MembershipPlanController.edit(
                                                                    membershipPlan.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="divide-y border-y md:hidden">
                            {membershipPlans.data.map((membershipPlan) => (
                                <article
                                    key={membershipPlan.id}
                                    className="py-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <Link
                                                href={MembershipPlanController.show(
                                                    membershipPlan.id,
                                                )}
                                                className="block truncate font-medium"
                                            >
                                                {membershipPlan.name}
                                            </Link>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {membershipPlan.duration_label}
                                            </p>
                                        </div>
                                        <MembershipPlanStatusBadge
                                            isActive={membershipPlan.is_active}
                                            label={membershipPlan.status_label}
                                        />
                                    </div>
                                    <p className="mt-3 text-base font-semibold tabular-nums">
                                        {formatCurrency(
                                            membershipPlan.price,
                                            currency,
                                        )}
                                    </p>
                                    <div className="mt-3 flex gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={MembershipPlanController.show(
                                                    membershipPlan.id,
                                                )}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={MembershipPlanController.edit(
                                                    membershipPlan.id,
                                                )}
                                            >
                                                <Pencil />
                                                Edit
                                            </Link>
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </>
                )}

                {membershipPlans.last_page > 1 && (
                    <nav
                        className="flex items-center justify-between gap-4 border-t pt-4"
                        aria-label="Pagination paket membership"
                    >
                        <p className="hidden text-sm text-muted-foreground sm:block">
                            {membershipPlans.from}-{membershipPlans.to} dari{' '}
                            {membershipPlans.total}
                        </p>
                        <div className="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">
                            <PaginationButton
                                url={membershipPlans.links[0]?.url ?? null}
                                label="Sebelumnya"
                                icon={<ChevronLeft />}
                            />
                            <div className="hidden gap-1 sm:flex">
                                {membershipPlans.links
                                    .slice(1, -1)
                                    .map((link) => (
                                        <Button
                                            key={link.label}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="icon"
                                            disabled={link.url === null}
                                            asChild={link.url !== null}
                                        >
                                            {link.url ? (
                                                <Link
                                                    href={link.url}
                                                    preserveScroll
                                                >
                                                    {link.label}
                                                </Link>
                                            ) : (
                                                <span>{link.label}</span>
                                            )}
                                        </Button>
                                    ))}
                            </div>
                            <PaginationButton
                                url={membershipPlans.links.at(-1)?.url ?? null}
                                label="Berikutnya"
                                icon={<ChevronRight />}
                                iconAfter
                            />
                        </div>
                    </nav>
                )}
            </div>
        </>
    );
}

function PaginationButton({
    url,
    label,
    icon,
    iconAfter = false,
}: {
    url: string | null;
    label: string;
    icon: ReactNode;
    iconAfter?: boolean;
}) {
    return (
        <Button
            variant="outline"
            size="sm"
            disabled={!url}
            asChild={Boolean(url)}
        >
            {url ? (
                <Link href={url} preserveScroll>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </Link>
            ) : (
                <span>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </span>
            )}
        </Button>
    );
}

MembershipPlansIndex.layout = {
    breadcrumbs: [
        {
            title: 'Paket Membership',
            href: MembershipPlanController.index(),
        },
    ],
};
