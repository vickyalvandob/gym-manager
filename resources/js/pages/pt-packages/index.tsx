import { Head, Link, router, usePage } from '@inertiajs/react';
import { Dumbbell, Eye, FilterX, Pencil, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PtPackageController from '@/actions/App/Http/Controllers/PtPackageController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/formatters';
import type { PaginatedPtPackages } from '@/types';

type Filters = { search: string; status: string; per_page: number };

export default function PtPackagesIndex({
    ptPackages,
    filters,
    canCreate,
}: {
    ptPackages: PaginatedPtPackages;
    filters: Filters;
    canCreate: boolean;
}) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const hasFilters = filters.search !== '' || filters.status !== '';

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            PtPackageController.index.url(),
            { search, status, per_page: filters.per_page },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Paket PT" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {ptPackages.total} paket
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">
                            Paket Personal Training
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Produk sesi privat yang dapat dibeli dan dijadwalkan
                            untuk member.
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild>
                            <Link href={PtPackageController.create()}>
                                <Plus />
                                Tambah paket
                            </Link>
                        </Button>
                    )}
                </header>

                <form
                    onSubmit={submit}
                    className="grid gap-3 border-y py-4 sm:grid-cols-[1fr_12rem_auto]"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="pl-9"
                            placeholder="Cari paket PT"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <div className="flex gap-2">
                        <Button type="submit" variant="outline">
                            <Search />
                            Terapkan
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                title="Hapus filter"
                                onClick={() => {
                                    setSearch('');
                                    setStatus('');
                                    router.get(
                                        PtPackageController.index.url(),
                                        {},
                                        { replace: true },
                                    );
                                }}
                            >
                                <FilterX />
                                <span className="sr-only">Hapus filter</span>
                            </Button>
                        )}
                    </div>
                </form>

                {ptPackages.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 text-center">
                        <Dumbbell className="size-8 text-muted-foreground" />
                        <p className="mt-3 text-sm font-medium">
                            {hasFilters
                                ? 'Paket PT tidak ditemukan'
                                : 'Belum ada paket PT'}
                        </p>
                        <p className="mt-1 max-w-sm text-xs leading-5 text-muted-foreground">
                            {hasFilters
                                ? 'Ubah kata pencarian atau status untuk melihat hasil lain.'
                                : 'Tambahkan paket agar Front Desk dapat menjual sesi Personal Training.'}
                        </p>
                        {canCreate && !hasFilters && (
                            <Button className="mt-4" size="sm" asChild>
                                <Link href={PtPackageController.create()}>
                                    <Plus />
                                    Tambah paket
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {ptPackages.data.map((item) => (
                            <article
                                key={item.id}
                                className="rounded-lg border p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <Link
                                            href={PtPackageController.show(
                                                item.id,
                                            )}
                                            className="font-semibold hover:text-primary"
                                        >
                                            {item.name}
                                        </Link>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {item.session_count} sesi ·{' '}
                                            {item.validity_days
                                                ? `${item.validity_days} hari`
                                                : 'tanpa batas'}
                                        </p>
                                    </div>
                                    <span
                                        className={`rounded-full px-2 py-1 text-xs font-medium ${item.is_active ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                    >
                                        {item.status_label}
                                    </span>
                                </div>
                                <p className="mt-5 text-lg font-semibold tabular-nums">
                                    {formatCurrency(
                                        item.price,
                                        auth.currentGym?.currency ?? 'IDR',
                                    )}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {item.sales_count} penjualan
                                </p>
                                <div className="mt-5 flex justify-end gap-1 border-t pt-3">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Lihat paket"
                                        asChild
                                    >
                                        <Link
                                            href={PtPackageController.show(
                                                item.id,
                                            )}
                                        >
                                            <Eye />
                                            <span className="sr-only">
                                                Lihat
                                            </span>
                                        </Link>
                                    </Button>
                                    {canCreate && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Edit paket"
                                            asChild
                                        >
                                            <Link
                                                href={PtPackageController.edit(
                                                    item.id,
                                                )}
                                            >
                                                <Pencil />
                                                <span className="sr-only">
                                                    Edit
                                                </span>
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                )}
                {ptPackages.last_page > 1 && (
                    <nav className="flex justify-between border-t pt-4">
                        <span className="text-sm text-muted-foreground">
                            {ptPackages.from}-{ptPackages.to} dari{' '}
                            {ptPackages.total}
                        </span>
                        <div className="flex gap-2">
                            {[ptPackages.links[0], ptPackages.links.at(-1)].map(
                                (link, index) => (
                                    <Button
                                        key={index}
                                        variant="outline"
                                        size="sm"
                                        disabled={!link?.url}
                                        asChild={Boolean(link?.url)}
                                    >
                                        {link?.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                            >
                                                {index === 0
                                                    ? 'Sebelumnya'
                                                    : 'Berikutnya'}
                                            </Link>
                                        ) : (
                                            <span>
                                                {index === 0
                                                    ? 'Sebelumnya'
                                                    : 'Berikutnya'}
                                            </span>
                                        )}
                                    </Button>
                                ),
                            )}
                        </div>
                    </nav>
                )}
            </div>
        </>
    );
}

PtPackagesIndex.layout = {
    breadcrumbs: [{ title: 'Paket PT', href: PtPackageController.index() }],
};
